<?php

namespace App\Services\Nesai;

use App\Ai\Agents\SchoolAssistantAgent;
use Illuminate\Support\Facades\Log;
use Throwable;

class NesaiService
{
    public function __construct(
        private IntentService $intent,
        private RetrievalService $retrieval,
        private LlmService $llm,
    ) {}

    /**
     * Handle incoming user message and generate a structured response.
     *
     * @param  string  $message
     * @param  array  $context
     * @return array{
     *     answer: string,
     *     intent: string,
     *     sources: array<string>,
     *     actions: array<array{type: string, path: string, title: string}>,
     *     mode: string
     * }
     */
    public function respond(string $message, array $context = []): array
    {
        $intent = $this->intent->detect($message);
        $sources = $this->retrieval->retrieve($message);
        $actions = [];

        try {
            // [CORE-LOGIC: AI-AGENT-INVOCATION]
            // Memanggil SchoolAssistantAgent (laravel/ai) secara non-streaming untuk tahap MVP.
            // Agent secara otomatis menentukan apakah perlu memanggil GetJurusanInfoTool atau NavigateToPageTool.
            $response = SchoolAssistantAgent::make()->prompt($message);
            $answer = $response->text;

            // [CORE-LOGIC: TOOL-RESULT-EXTRACTION]
            // Mengekstrak aksi navigasi dan atribusi sumber data dari hasil eksekusi tool ($response->toolResults).
            // Payload dinormalisasi agar dapat langsung dikonsumsi frontend Next.js untuk tombol quick-action/redirect.
            if (isset($response->toolResults) && $response->toolResults->isNotEmpty()) {
                foreach ($response->toolResults as $toolResult) {
                    if ($toolResult->name === 'navigate_to_page' && is_string($toolResult->result)) {
                        $navData = json_decode($toolResult->result, true);
                        if (is_array($navData) && isset($navData['path'])) {
                            $actions[] = [
                                'type' => 'navigate',
                                'path' => $navData['path'],
                                'title' => $navData['title'] ?? 'Navigasi Halaman',
                            ];
                        }
                    }

                    if ($toolResult->name === 'recommend_jurusan') {
                        $sources[] = 'Rekomendasi Jurusan SMKN 1 Subang (config/jurusan.php)';
                        $intent = 'major_recommendation';

                        // Buatkan quick action navigasi ke jurusan teratas yang direkomendasikan
                        if (is_string($toolResult->result)) {
                            $recData = json_decode($toolResult->result, true);
                            if (! empty($recData['rekomendasi']) && is_array($recData['rekomendasi'])) {
                                foreach (array_slice($recData['rekomendasi'], 0, 2) as $rec) {
                                    if (! empty($rec['slug'])) {
                                        $actions[] = [
                                            'type' => 'navigate',
                                            'path' => '/jurusan/' . $rec['slug'],
                                            'title' => 'Lihat ' . ($rec['nama'] ?? 'Jurusan'),
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    if ($toolResult->name === 'get_jurusan_info') {
                        $sources[] = 'Informasi Jurusan SMKN 1 Subang (config/jurusan.php)';
                    }
                }
            }

            // Fallback ekstraksi dari toolCalls jika toolResults belum terisi
            if (empty($actions) && isset($response->toolCalls) && $response->toolCalls->isNotEmpty()) {
                foreach ($response->toolCalls as $toolCall) {
                    if ($toolCall->name === 'navigate_to_page') {
                        $args = $toolCall->arguments;
                        $page = $args['page'] ?? '/';
                        $path = str_starts_with($page, '/') ? $page : '/' . ltrim($page, '/');
                        if (! empty($args['slug'])) {
                            $path = rtrim($path, '/') . '/' . ltrim($args['slug'], '/');
                        }
                        $actions[] = [
                            'type' => 'navigate',
                            'path' => $path,
                            'title' => $args['label'] ?? ('Halaman ' . ucfirst(trim($path, '/'))),
                        ];
                    }

                    if ($toolCall->name === 'recommend_jurusan') {
                        $sources[] = 'Rekomendasi Jurusan SMKN 1 Subang (config/jurusan.php)';
                        $intent = 'major_recommendation';
                    }

                    if ($toolCall->name === 'get_jurusan_info') {
                        $sources[] = 'Informasi Jurusan SMKN 1 Subang (config/jurusan.php)';
                    }
                }
            }

            // [CORE-LOGIC: INTENT-NORMALIZATION]
            // Jika agent memutuskan ada halaman yang harus dikunjungi pengguna, selaraskan intent ke 'school_navigation'.
            if (! empty($actions) && $intent === 'school_information') {
                $intent = 'school_navigation';
            }

            $sources = array_values(array_unique($sources));
            $actions = array_values(array_unique($actions, SORT_REGULAR));

            return [
                'answer' => $answer,
                'intent' => $intent,
                'sources' => $sources,
                'actions' => $actions,
                'mode' => 'ai-agent',
            ];
        } catch (Throwable $e) {
            // [CORE-LOGIC: RESILIENT-FALLBACK-GUARD]
            // Mencegah HTTP 500 error ke pengguna saat presentasi juri jika kuota Gemini habis (rate limit 429) atau koneksi timeout.
            // Mengembalikan respons ramah beserta opsi tombol navigasi darurat ke halaman penting sekolah.
            Log::error('NESAI Agent error: ' . $e->getMessage(), [
                'exception' => $e,
                'message' => $message,
            ]);

            return [
                'answer' => 'Halo! Saya NESAI, asisten virtual SMKN 1 Subang. Saat ini layanan AI sedang dalam penyesuaian. Anda dapat menanyakan seputar jurusan dan PPDB, atau langsung mengunjungi halaman yang tersedia di bawah ini.',
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
        }
    }
}
