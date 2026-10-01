<?php

namespace App\Services\Nesai;

use App\Ai\Agents\SchoolAssistantAgent;
use App\Jobs\PruneChatMessages;
use App\Models\ChatSession;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Messages\Message;
use Throwable;

class NesaiService
{
    public function __construct(
        private IntentService $intent,
        private RetrievalService $retrieval,
        private LlmService $llm,
        private FastPathService $fastPath,
        private GeminiCircuitBreaker $circuit,
        private GeminiConcurrencyGuard $concurrency,
    ) {}

    /**
     * Handle incoming user message and generate a structured response.
     *
     * @return array{
     *     answer: string,
     *     intent: string,
     *     sources: array<string>,
     *     actions: array<array{type: string, path: string, title: string}>,
     *     mode: string
     * }
     */
    public function respond(string $message, array $context = [], ?string $sessionId = null): array
    {
        $component = 'chat.persistence';
        $providerStartedAt = null;
        $providerSucceeded = false;
        $componentStartedAt = hrtime(true);

        if ($sessionId === null) {
            throw new \InvalidArgumentException('A server-side Laravel session ID is required.');
        }

        // [CORE-LOGIC: IDEMPOTENCY-GUARD]
        // Mencegah double-submit / auto-retry menghabiskan kuota provider AI berbayar.
        // Pesan identik dari sesi yang sama dalam jendela singkat mengembalikan respons
        // cache (bila sudah selesai) atau ditolak sementara (bila masih diproses).
        $idempotency = $this->beginIdempotentRequest($sessionId, $message);
        if ($idempotency['short_circuit'] !== null) {
            return $idempotency['short_circuit'];
        }
        $idempotencyKey = $idempotency['key'];
        $idempotencyLock = $idempotency['lock'];

        try {
            $chatSession = ChatSession::firstOrCreate(['session_id' => $sessionId]);
            $historyLimit = max(1, (int) config('chat.history_limit', 20));
            $history = $chatSession->messages()
                ->orderByDesc('id')
                ->limit(max(0, $historyLimit - 1))
                ->get()
                ->reverse()
                ->map(fn ($item) => new Message($item->role === 'model' ? 'assistant' : 'user', $item->content))
                ->values()
                ->all();
            $userMessage = $chatSession->messages()->create(['role' => 'user', 'content' => $message]);
            // Perbarui updated_at sesi agar pruning berbasis aktivitas (bukan pembuatan) akurat.
            $chatSession->touch();
            Log::debug('nesai.component', [
                'component' => 'chat.persistence',
                'duration_ms' => round((hrtime(true) - $componentStartedAt) / 1_000_000, 2),
                'history_messages' => count($history),
            ]);
        } catch (Throwable $e) {
            $this->releaseIdempotency($idempotencyKey, null, $idempotencyLock);
            throw $e;
        }
        $intent = 'school_information';
        $sources = [];
        $actions = [];

        try {
            $component = 'intent';
            $componentStartedAt = hrtime(true);
            $intent = $this->intent->detect($message);
            Log::debug('nesai.component', [
                'component' => 'intent',
                'duration_ms' => round((hrtime(true) - $componentStartedAt) / 1_000_000, 2),
            ]);

            // [CORE-LOGIC: DATABASE-FAST-PATH]
            // Untuk pertanyaan faktual-deterministik (PPDB, profil/kontak/alamat, fasilitas,
            // daftar jurusan), jawab langsung dari database TANPA menyentuh Gemini. Ini
            // melindungi kuota provider dan menjaga latency rendah saat stress test online.
            // Bila fast-path tidak yakin, ia mengembalikan null → lanjut ke jalur AI.
            if ((bool) config('chat.fastpath_enabled', true)) {
                $component = 'fastpath';
                $componentStartedAt = hrtime(true);
                $fast = $this->fastPath->respond($message);

                if ($fast !== null) {
                    $chatSession->messages()->create(['role' => 'model', 'content' => $fast['answer']]);
                    $chatSession->touch();

                    PruneChatMessages::dispatch($chatSession->id)
                        ->onQueue(config('chat.prune_queue', 'default'));

                    Log::info('nesai.fastpath', [
                        'intent' => $fast['intent'],
                        'duration_ms' => round((hrtime(true) - $componentStartedAt) / 1_000_000, 2),
                    ]);

                    $this->releaseIdempotency($idempotencyKey, $fast, $idempotencyLock);

                    return $fast;
                }
            }

            $component = 'retrieval';
            $componentStartedAt = hrtime(true);
            // [CORE-LOGIC: RETRIEVAL-HOOK]
            // Retrieval (RAG) bersifat opsional & saat ini belum memiliki sumber dokumen,
            // sehingga hanya menambahkan sumber bila memang mengembalikan hasil. Pemanggilan
            // tanpa hasil tidak lagi dicatat sebagai komponen terpisah untuk menghindari log/latensi palsu.
            $retrievedSources = $this->retrieval->retrieve($message);
            if (! empty($retrievedSources)) {
                $sources = array_merge($sources, $retrievedSources);
                Log::debug('nesai.component', [
                    'component' => 'retrieval',
                    'duration_ms' => round((hrtime(true) - $componentStartedAt) / 1_000_000, 2),
                    'sources' => count($retrievedSources),
                ]);
            }

            // [CORE-LOGIC: AI-AGENT-INVOCATION]
            // Memanggil SchoolAssistantAgent (laravel/ai) secara non-streaming untuk tahap MVP.
            // Agent secara otomatis menentukan apakah perlu memanggil GetJurusanInfoTool atau NavigateToPageTool.
            //
            // Dilindungi oleh dua guard agar server tidak jenuh saat provider bermasalah:
            // 1. Circuit breaker — bila Gemini sedang OPEN (kuota habis/rate limit/timeout
            //    berulang), LEWATI Gemini sepenuhnya dan balas fallback cepat.
            // 2. Concurrency cap — bila slot serentak penuh, jangan mengantre panjang;
            //    lewati jalur AI (fallback cepat).
            $component = 'gemini';
            $providerStartedAt = hrtime(true);

            if (! $this->circuit->allow()) {
                $providerStartedAt = null;
                throw new \RuntimeException('Gemini circuit is OPEN — skipping provider call.');
            }

            $response = $this->concurrency->run(function () use ($history, $message) {
                $agent = new SchoolAssistantAgent($history);

                return $agent->prompt($message, timeout: (int) config('chat.provider_timeout', 10));
            });

            if ($response === null) {
                $providerStartedAt = null;
                throw new \RuntimeException('Gemini concurrency limit reached — skipping provider call.');
            }

            // [CORE-LOGIC: PROVIDER-HEALTH-SIGNAL]
            // Gemini SUKSES → tandai agar catch TIDAK mencatat kegagalan provider bila
            // langkah setelah ini (extractToolArtifacts/persist) yang error. recordSuccess()
            // menutup circuit dan mereset counter kegagalan. (Lihat QA/QC P1-1.)
            $providerSucceeded = true;
            $this->circuit->recordSuccess();

            Log::info('nesai.provider', [
                'provider' => 'gemini',
                'model' => config('ai.providers.gemini.models.text.default'),
                'status' => 'success',
                'duration_ms' => round((hrtime(true) - $providerStartedAt) / 1_000_000, 2),
            ]);
            $answer = $response->text;

            // [CORE-LOGIC: TOOL-OUTPUT-EXTRACTION]
            // Mengekstrak aksi navigasi & atribusi sumber data dari hasil eksekusi tool.
            // Sumber utama: toolResults (hasil eksekusi). Fallback: toolCalls (argumen permintaan tool)
            // bila provider hanya mengembalikan rencana pemanggilan tanpa hasilnya.
            [$toolSources, $toolActions, $toolIntent] = $this->extractToolArtifacts($response, $message);
            $sources = array_merge($sources, $toolSources);
            $actions = array_merge($actions, $toolActions);
            if ($toolIntent !== null) {
                $intent = $toolIntent;
            }

            // [CORE-LOGIC: INTENT-NORMALIZATION]
            // Jika agent memutuskan ada halaman yang harus dikunjungi pengguna, selaraskan intent ke 'school_navigation'.
            if (! empty($actions) && $intent === 'school_information') {
                $intent = 'school_navigation';
            }

            $sources = array_values(array_unique($sources));
            $actions = array_values(array_unique($actions, SORT_REGULAR));

            $component = 'chat.persistence';
            $componentStartedAt = hrtime(true);
            $chatSession->messages()->create(['role' => 'model', 'content' => $answer]);
            $chatSession->touch();

            // [CORE-LOGIC: DEFERRED-PRUNING]
            // Pruning pesan lama TIDAK lagi dijalankan sinkron di jalur respons (mahal:
            // subquery WHERE NOT IN pada tabel yang sama tiap request). Dijalankan sebagai
            // job terjadwal / queued oleh perintah `chat:prune-messages`.
            PruneChatMessages::dispatch($chatSession->id)
                ->onQueue(config('chat.prune_queue', 'default'));

            Log::debug('nesai.component', [
                'component' => 'chat.persistence',
                'operation' => 'store_assistant_message',
                'duration_ms' => round((hrtime(true) - $componentStartedAt) / 1_000_000, 2),
            ]);

            $result = [
                'answer' => $answer,
                'intent' => $intent,
                'sources' => $sources,
                'actions' => $actions,
                'mode' => 'ai-agent',
            ];

            $this->releaseIdempotency($idempotencyKey, $result, $idempotencyLock);

            return $result;
        } catch (Throwable $e) {
            // [CORE-LOGIC: RESILIENT-FALLBACK-GUARD]
            // Mencegah HTTP 500 error ke pengguna saat presentasi juri jika kuota Gemini habis (rate limit 429) atau koneksi timeout.
            // Mengembalikan respons ramah beserta opsi tombol navigasi darurat ke halaman penting sekolah.
            $providerStatus = $e instanceof RateLimitedException ? 429 : null;
            for ($cause = $e; $cause !== null; $cause = $cause->getPrevious()) {
                if ($cause instanceof RequestException && $cause->response !== null) {
                    $providerStatus = $cause->response->status();
                    break;
                }
            }

            Log::error('nesai.component_failed', [
                'component' => $component,
                'exception' => $e::class,
                'provider' => $component === 'gemini' ? 'gemini' : null,
                'provider_status' => $providerStatus,
                'error_kind' => match (true) {
                    $e instanceof RateLimitedException => 'rate_limited',
                    $e instanceof ConnectionException, $e instanceof ProviderConnectionException => 'connection_or_timeout',
                    $e instanceof ProviderOverloadedException => 'provider_overloaded',
                    $e instanceof InsufficientCreditsException => 'quota_or_credit_exhausted',
                    $providerStatus !== null => 'provider_http_error',
                    default => $component === 'gemini' ? 'provider_error' : $component.'_error',
                },
                'duration_ms' => $component === 'gemini' && $providerStartedAt !== null
                    ? round((hrtime(true) - $providerStartedAt) / 1_000_000, 2)
                    : null,
            ]);

            // [CORE-LOGIC: GEMINI-CIRCUIT-FEEDBACK]
            // Catat kegagalan provider HANYA bila benar-benar memanggil Gemini DAN Gemini
            // belum sempat sukses (bukan saat kita sengaja melewatinya karena circuit
            // OPEN / slot penuh, dan bukan saat error terjadi SETELAH Gemini sukses).
            // Ini mencegah error non-provider (mis. parsing tool) membuka circuit palsu.
            if ($component === 'gemini' && $providerStartedAt !== null && $providerSucceeded === false) {
                $this->circuit->recordFailure();
            }

            $fallbackAnswer = 'Halo! Saya NESAI, asisten virtual SMKN 1 Subang. Saat ini layanan AI sedang dalam penyesuaian. Anda dapat menanyakan seputar jurusan dan PPDB, atau langsung mengunjungi halaman yang tersedia di bawah ini.';
            $userMessage->delete();

            // Lepas kunci idempotency agar pengguna dapat mencoba ulang, namun simpan hasil
            // fallback singkat sebagai respons cache demi meredam lonjakan saat provider down.
            $this->releaseIdempotency($idempotencyKey, null, $idempotencyLock);

            return [
                'answer' => $fallbackAnswer,
                'intent' => $intent,
                'sources' => $sources,
                'actions' => [
                    [
                        'type' => 'navigate',
                        'path' => '/jurusan',
                        'title' => 'Daftar Jurusan SMKN 1 Subang',
                    ],
                    [
                        'type' => 'navigate',
                        'path' => '/ppdb',
                        'title' => 'Informasi PPDB',
                    ],
                ],
                'mode' => 'fallback-error',
            ];
        } finally {
            // [CORE-LOGIC: IDEMPOTENCY-LOCK-SAFETY]
            // Jamin kunci idempotency selalu dilepas apa pun jalur yang ditempuh (termasuk
            // exception tak terduga), sehingga tidak ada request identik yang tertahan 429
            // karena kunci menggantung. `release()` bersifat idempoten (owner-checked).
            $this->releaseIdempotency($idempotencyKey, null, $idempotencyLock);
        }
    }

    /**
     * [CORE-LOGIC: IDEMPOTENCY-GUARD]
     * Melindungi kuota provider AI dari double-submit / retry dengan men-dedup pesan
     * identik dari sesi yang sama dalam jendela waktu singkat (config chat.idempotency_seconds).
     *
     * Perilaku:
     * - Bila respons identik sudah selesai: kembalikan respons cache (short_circuit).
     * - Bila request identik masih diproses: tolak sementara (429) agar tidak memanggil LLM dua kali.
     * - Bila jendela 0: idempotency dinonaktifkan.
     *
     * @return array{key: ?string, lock: ?\Illuminate\Contracts\Cache\Lock, short_circuit: ?array<string, mixed>}
     */
    private function beginIdempotentRequest(string $sessionId, string $message): array
    {
        $window = (int) config('chat.idempotency_seconds', 5);

        if ($window <= 0) {
            return ['key' => null, 'lock' => null, 'short_circuit' => null];
        }

        $key = 'nesai:idem:'.hash('sha256', $sessionId."\0".$message);
        $lock = Cache::lock($key.':lock', $window);

        // Sudah selesai diproses sebelumnya → kembalikan respons cache.
        $cached = Cache::get($key.':result');
        if (is_array($cached)) {
            Log::debug('nesai.idempotency', ['result' => 'cache_hit']);

            return ['key' => null, 'lock' => null, 'short_circuit' => $cached];
        }

        // Belum ada hasil. Coba kunci; bila gagal, request identik sedang berjalan.
        if (! $lock->get()) {
            Log::info('nesai.idempotency', ['result' => 'in_flight', 'status' => 429]);
            abort(429, 'Permintaan identik sedang diproses. Mohon tunggu sebentar.');
        }

        return ['key' => $key, 'lock' => $lock, 'short_circuit' => null];
    }

    /**
     * Menyimpan hasil idempotent (bila ada) dan melepas kunci.
     *
     * Lock SELALU dilepas (bukan menunggu TTL) agar tidak ada kunci menggantung bila
     * terjadi error di tengah. Dedup sukses tetap terjaga karena hasil disimpan sebagai
     * `$key.':result'` (cache) yang disajikan pada request identik berikutnya.
     *
     * @param  \Illuminate\Contracts\Cache\Lock|null  $lock
     * @param  array<string, mixed>|null  $result
     */
    private function releaseIdempotency(?string $key, ?array $result, ?\Illuminate\Contracts\Cache\Lock $lock = null): void
    {
        if ($key === null) {
            return;
        }

        $window = (int) config('chat.idempotency_seconds', 5);

        if ($result !== null) {
            Cache::put($key.':result', $result, $window);
        }

        // Lepas kunci agar request identik berikutnya tidak tertahan 429 walau request
        // ini selesai/mati. Hasil (bila ada) tetap disajikan dari cache di atas.
        if ($lock !== null) {
            try {
                $lock->release();
            } catch (Throwable) {
                // Abaikan: lock akan kedaluwarsa sendiri sesuai window.
            }
        }

        Log::debug('nesai.idempotency', ['result' => $result !== null ? 'stored' : 'released']);
    }

    /**
     * [CORE-LOGIC: TOOL-OUTPUT-EXTRACTION]
     * Menormalkan artefak dari respons agent (sources, actions, intent) menjadi satu jalur
     * yang sama untuk toolResults maupun toolCalls. Menghilangkan duplikasi logika ekstraksi.
     *
     * @return array{0: list<string>, 1: list<array{type: string, path: string, title: string}>, 2: ?string}
     */
    private function extractToolArtifacts(object $response, string $message): array
    {
        $sources = [];
        $actions = [];
        $intent = null;

        $toolResults = isset($response->toolResults) && $response->toolResults->isNotEmpty()
            ? $response->toolResults
            : null;

        $toolCalls = isset($response->toolCalls) && $response->toolCalls->isNotEmpty()
            ? $response->toolCalls
            : [];

        // Gap 1: hasil eksekusi tool (payload faktual) — sumber otoritatif.
        foreach ($toolResults ?? [] as $toolResult) {
            $name = $toolResult->name;
            $payload = is_string($toolResult->result) ? json_decode($toolResult->result, true) : null;

            $sources[] = $this->sourceForTool($name);
            $actions = array_merge($actions, $this->actionsForTool($name, $payload, $message));
            $intent ??= $this->intentForTool($name);
        }

        // Gap 2: fallback dari toolCalls bila tidak ada hasil eksekusi sama sekali.
        if ($toolResults === null) {
            foreach ($toolCalls as $toolCall) {
                $name = $toolCall->name;
                $args = (array) $toolCall->arguments;

                $sources[] = $this->sourceForTool($name);
                $actions = array_merge($actions, $this->actionsForTool($name, $args, $message, fromArgs: true));
                $intent ??= $this->intentForTool($name);
            }
        }

        return [
            array_values(array_filter($sources)),
            $actions,
            $intent,
        ];
    }

    /**
     * Nama sumber data yang diatribusikan untuk setiap tool.
     *
     * Catatan: dibandingkan dengan string literal nama tool (bukan `Tool::name()`
     * statis) karena `name()` adalah method instance, sehingga pemanggilan statis
     * akan melempar Error "Non-static method ... cannot be called statically".
     * String di bawah sinkron dengan `name()` masing-masing tool.
     */
    private function sourceForTool(string $tool): ?string
    {
        return match ($tool) {
            'recommend_jurusan' => 'Sistem Rekomendasi Minat (Database SMKN 1 Subang)',
            'get_jurusan_info' => 'Basis Data Kompetensi Keahlian (Database SMKN 1 Subang)',
            'get_school_info' => 'Basis Data Profil Resmi & Karya Inovasi (Database SMKN 1 Subang)',
            'get_news_info' => 'Portal Berita & Prestasi Resmi (Database SMKN 1 Subang)',
            'get_ppdb_info' => 'Informasi PPDB Terkini (Database SMKN 1 Subang)',
            default => null,
        };
    }

    /**
     * Intent koreksi bila tool menyiratkan kategori spesifik.
     */
    private function intentForTool(string $tool): ?string
    {
        return match ($tool) {
            'recommend_jurusan' => 'major_recommendation',
            'get_ppdb_info' => 'ppdb_information',
            default => null,
        };
    }

    /**
     * Aksi navigasi yang dihasilkan tool.
     *
     * @param  array<string, mixed>|null  $payload
     * @return list<array{type: string, path: string, title: string}>
     */
    private function actionsForTool(string $tool, ?array $payload, string $message, bool $fromArgs = false): array
    {
        if ($tool === 'navigate_to_page') {
            return $this->navigateActions($payload, $message, $fromArgs);
        }

        if ($tool === 'recommend_jurusan') {
            return $this->recommendationActions($payload);
        }

        if ($payload === null && ! $fromArgs) {
            return [];
        }

        return match ($tool) {
            'get_school_info' => [$this->schoolInfoAction($message)],
            'get_news_info' => [[
                'type' => 'navigate',
                'path' => '/berita',
                'title' => 'Lihat Portal Berita & Prestasi',
            ]],
            'get_ppdb_info' => [[
                'type' => 'navigate',
                'path' => '/ppdb',
                'title' => 'Halaman PPDB SMKN 1 Subang',
            ]],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return list<array{type: string, path: string, title: string}>
     */
    private function navigateActions(?array $payload, string $message, bool $fromArgs): array
    {
        // Dari toolResults: payload sudah berisi path & title final.
        if (! $fromArgs && is_array($payload) && isset($payload['path'])) {
            return [[
                'type' => 'navigate',
                'path' => $payload['path'],
                'title' => $payload['title'] ?? 'Navigasi Halaman',
            ]];
        }

        // Dari toolCalls: bangun path dari argumen page + slug.
        if ($fromArgs) {
            $page = $payload['page'] ?? '/';
            $path = str_starts_with($page, '/') ? $page : '/'.ltrim($page, '/');
            if (! empty($payload['slug'])) {
                $path = rtrim($path, '/').'/'.ltrim($payload['slug'], '/');
            }

            return [[
                'type' => 'navigate',
                'path' => $path,
                'title' => $payload['label'] ?? ('Halaman '.ucfirst(trim($path, '/'))),
            ]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return list<array{type: string, path: string, title: string}>
     */
    private function recommendationActions(?array $payload): array
    {
        if (empty($payload['rekomendasi']) || ! is_array($payload['rekomendasi'])) {
            return [];
        }

        $actions = [];
        foreach (array_slice($payload['rekomendasi'], 0, 2) as $rec) {
            if (! empty($rec['slug'])) {
                $actions[] = [
                    'type' => 'navigate',
                    'path' => '/jurusan/'.$rec['slug'],
                    'title' => 'Lihat '.($rec['nama'] ?? 'Jurusan'),
                ];
            }
        }

        return $actions;
    }

    /**
     * @return array{type: string, path: string, title: string}
     */
    private function schoolInfoAction(string $message): array
    {
        if (preg_match('/inovasi|karya|haki|produk/i', $message)) {
            return ['type' => 'navigate', 'path' => '/prestasi', 'title' => 'Lihat Karya Inovasi Siswa'];
        }

        if (preg_match('/prestasi|juara|lomba|kejuaraan/i', $message)) {
            return ['type' => 'navigate', 'path' => '/berita', 'title' => 'Lihat Berita & Prestasi di Portal Berita'];
        }

        return ['type' => 'navigate', 'path' => '/profil', 'title' => 'Lihat Profil SMKN 1 Subang'];
    }

    public function resetConversation(string $sessionId): void
    {
        ChatSession::where('session_id', $sessionId)->delete();
    }
}
