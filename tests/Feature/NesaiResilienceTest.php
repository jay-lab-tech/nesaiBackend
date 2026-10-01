<?php

namespace Tests\Feature;

use App\Ai\Agents\SchoolAssistantAgent;
use App\Services\Nesai\GeminiCircuitBreaker;
use App\Services\Nesai\NesaiService;
use Database\Seeders\SchoolPublicDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * [CORE-LOGIC: NESAI-RESILIENCE-GUARD]
 * Memverifikasi arsitektur survival untuk stress test online:
 * - Fast-path DB menjawab intent umum TANPA memanggil Gemini.
 * - Circuit breaker membuka setelah kegagalan berulang & melewati Gemini.
 * - Fallback tetap HTTP-200-friendly (mode "fallback-error") tanpa 5xx.
 * - Concurrency cap melindungi provider dari lonjakan serentak.
 */
class NesaiResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan state circuit bersih antar-test.
        app(GeminiCircuitBreaker::class)->reset();
    }

    public function test_fastpath_answers_ppdb_without_calling_gemini(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);
        config(['chat.fastpath_enabled' => true]);

        $called = 0;
        SchoolAssistantAgent::fake(function () use (&$called) {
            $called++;

            return 'Seharusnya tidak dipanggil.';
        });

        $response = app(NesaiService::class)->respond(
            'Kapan jadwal pendaftaran PPDB dibuka dan apa saja syaratnya?',
            [],
            'fastpath-ppdb',
        );

        $this->assertSame(0, $called, 'Fast-path PPDB tidak boleh memanggil Gemini.');
        $this->assertSame('ai-agent', $response['mode']);
        $this->assertSame('ppdb_information', $response['intent']);
        $this->assertNotEmpty($response['answer']);
    }

    public function test_fastpath_ppdb_output_is_human_readable_not_raw_json(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);
        config(['chat.fastpath_enabled' => true]);

        SchoolAssistantAgent::fake(['Tidak dipakai.']);

        $response = app(NesaiService::class)->respond('Kapan jadwal PPDB?', [], 'fp-ppdb-fmt');
        $answer = $response['answer'];

        // Jawaban harus manusiawi: memuat narasi & tidak memuat JSON mentah.
        $this->assertStringContainsString('PPDB', $answer);
        $this->assertStringContainsString('Jadwal', $answer);
        $this->assertStringNotContainsString('{', $answer, 'Output fast-path tidak boleh memuat JSON mentah.');
        $this->assertStringNotContainsString('"tahap"', $answer);
        $this->assertStringNotContainsString('json', mb_strtolower($answer));
    }

    public function test_fastpath_major_list_is_informative_and_not_raw_json(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);
        config(['chat.fastpath_enabled' => true]);

        SchoolAssistantAgent::fake(['Tidak dipakai.']);

        $response = app(NesaiService::class)->respond('Daftar jurusan apa saja yang ada?', [], 'fp-jurusan');
        $answer = $response['answer'];

        $this->assertStringContainsString('kompetensi keahlian', mb_strtolower($answer));
        // Opsi B: memuat deskripsi & prospek karir (informatif), bukan sekadar nama+slug mentah.
        $this->assertStringContainsString('Prospek karir', $answer);
        $this->assertStringNotContainsString('"slug"', $answer, 'Tidak boleh menampilkan JSON mentah (slug).');
        $this->assertStringNotContainsString('"nama"', $answer);
        $this->assertStringNotContainsString('panggil tool', mb_strtolower($answer), 'Instruksi internal untuk LLM tidak boleh bocor ke pengguna.');
    }

    public function test_fastpath_school_contact_is_human_readable(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);
        config(['chat.fastpath_enabled' => true]);

        SchoolAssistantAgent::fake(['Tidak dipakai.']);

        $response = app(NesaiService::class)->respond('Apa kontak sekolah?', [], 'fp-kontak');
        $answer = $response['answer'];

        $this->assertStringContainsString('kontak', mb_strtolower($answer));
        $this->assertMatchesRegularExpression('/telepon|email|website/i', $answer);
        $this->assertStringNotContainsString('{', $answer);
    }

    public function test_fastpath_can_be_disabled_to_fall_back_to_ai(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);
        config(['chat.fastpath_enabled' => false]);

        SchoolAssistantAgent::fake(['Jawaban AI.']);

        $response = app(NesaiService::class)->respond(
            'Kapan jadwal pendaftaran PPDB dibuka?',
            [],
            'fastpath-disabled',
        );

        $this->assertSame('Jawaban AI.', $response['answer']);
        $this->assertSame('ai-agent', $response['mode']);
    }

    public function test_circuit_opens_after_repeated_failures_and_skips_gemini(): void
    {
        config([
            'chat.fastpath_enabled' => false,
            'chat.circuit_fail_threshold' => 3,
            'chat.circuit_fail_window' => 60,
            'chat.circuit_open_seconds' => 60,
        ]);

        $calls = 0;
        SchoolAssistantAgent::fake(function () use (&$calls) {
            $calls++;
            throw new RuntimeException('simulated provider outage');
        });

        $service = app(NesaiService::class);

        // Tiga kegagalan berturut → mencapai ambang → circuit OPEN.
        for ($i = 0; $i < 3; $i++) {
            $response = $service->respond('Pertanyaan gagal '.$i, [], 'circuit-session-'.$i);
            $this->assertSame('fallback-error', $response['mode']);
        }

        $this->assertSame(3, $calls);
        $this->assertTrue(app(GeminiCircuitBreaker::class)->isOpen());

        // Request berikutnya: Gemini DILEWATI sepenuhnya → jumlah panggilan tidak bertambah.
        $response = $service->respond('Pertanyaan setelah circuit open', [], 'circuit-session-after');
        $this->assertSame('fallback-error', $response['mode']);
        $this->assertSame(3, $calls, 'Gemini tidak boleh dipanggil saat circuit OPEN.');
    }

    public function test_success_resets_circuit_failure_counter(): void
    {
        config([
            'chat.fastpath_enabled' => false,
            'chat.circuit_fail_threshold' => 3,
        ]);

        SchoolAssistantAgent::fake(['Jawaban sukses.']);

        app(NesaiService::class)->respond('Halo', [], 'reset-session');

        $this->assertFalse(app(GeminiCircuitBreaker::class)->isOpen());
    }

    public function test_concurrency_cap_skips_gemini_when_slots_full(): void
    {
        config([
            'chat.fastpath_enabled' => false,
            'chat.max_concurrency' => 1,
            'chat.concurrency_lock_seconds' => 15,
        ]);

        // Isi satu-satunya slot agar penuh, tanpa melepasnya.
        $lock = \Illuminate\Support\Facades\Cache::lock('nesai:gemini:slot:1', 15);
        $this->assertTrue($lock->get());

        $calls = 0;
        SchoolAssistantAgent::fake(function () use (&$calls) {
            $calls++;

            return 'Tidak boleh dipanggil.';
        });

        $response = app(NesaiService::class)->respond('Pertanyaan saat slot penuh', [], 'concurrency-session');

        $this->assertSame('fallback-error', $response['mode']);
        $this->assertSame(0, $calls, 'Gemini tidak boleh dipanggil saat slot concurrency penuh.');

        $lock->release();
    }

    /**
     * [REGRESSION] Bug kritis: `sourceForTool`/`intentForTool`/`actionsForTool` memanggil
     * `Tool::name()` secara STATIS padahal `name()` adalah method instance, sehingga
     * melempar Error "Non-static method ... cannot be called statically" yang membuat
     * SEMUA respons berbasis tool jatuh ke fallback. Test ini memastikan resolusi tool
     * tidak melempar error dan mengembalikan nilai yang benar.
     */
    public function test_tool_artifact_resolvers_do_not_error_on_static_call_bug(): void
    {
        $service = app(NesaiService::class);
        $ref = new \ReflectionClass($service);

        $sourceForTool = $ref->getMethod('sourceForTool');
        $sourceForTool->setAccessible(true);
        $intentForTool = $ref->getMethod('intentForTool');
        $intentForTool->setAccessible(true);
        $actionsForTool = $ref->getMethod('actionsForTool');
        $actionsForTool->setAccessible(true);

        // Tidak boleh melempar Error untuk nama tool apa pun.
        // navigate_to_page memang tidak memiliki atribusi sumber (null), jadi tidak diwajibkan non-null.
        foreach (['recommend_jurusan', 'get_jurusan_info', 'get_school_info', 'get_news_info', 'get_ppdb_info'] as $tool) {
            $this->assertNotNull($sourceForTool->invoke($service, $tool), "sourceForTool({$tool})");
        }

        // navigate_to_page harus aman dipanggil (tidak melempar) meski hasilnya null.
        $this->assertNull($sourceForTool->invoke($service, 'navigate_to_page'));

        $this->assertSame('major_recommendation', $intentForTool->invoke($service, 'recommend_jurusan'));
        $this->assertSame('ppdb_information', $intentForTool->invoke($service, 'get_ppdb_info'));

        $actions = $actionsForTool->invoke($service, 'get_ppdb_info', ['x' => 1], 'halo', false);
        $this->assertIsArray($actions);
    }

    /**
     * [P1-1 REGRESSION] Provider yang SUKSES tidak boleh menambah counter kegagalan circuit.
     * (Sebelumnya, error non-provider setelah Gemini sukses ikut menaikkan counter.)
     */
    public function test_successful_provider_does_not_increment_circuit_failures(): void
    {
        config(['chat.fastpath_enabled' => false, 'chat.circuit_fail_threshold' => 3]);
        SchoolAssistantAgent::fake(['Jawaban sukses.']);

        app(NesaiService::class)->respond('Halo', [], 'circuit-no-fail');

        $this->assertFalse(app(GeminiCircuitBreaker::class)->isOpen());
        $this->assertNull(\Illuminate\Support\Facades\Cache::get('nesai:circuit:failures'));
    }

    /**
     * [P1-2 REGRESSION] Setelah fallback (provider error), kunci idempotency harus DILEPAS
     * agar request identik berikutnya bisa dicoba ulang (tidak tertahan 429 "in-flight").
     */
    public function test_idempotency_lock_released_after_fallback_allows_retry(): void
    {
        config(['chat.fastpath_enabled' => false, 'chat.idempotency_seconds' => 30]);

        $calls = 0;
        SchoolAssistantAgent::fake(function () use (&$calls) {
            $calls++;
            throw new RuntimeException('simulated outage');
        });

        $service = app(NesaiService::class);

        $first = $service->respond('Pesan gagal', [], 'retry-session');
        $this->assertSame('fallback-error', $first['mode']);

        // Kunci harus sudah dilepas → request identik bisa diproses lagi (tidak 429).
        $second = $service->respond('Pesan gagal', [], 'retry-session');
        $this->assertSame('fallback-error', $second['mode']);
        $this->assertSame(2, $calls, 'Kedua request harus sampai ke provider (lock tidak menggantung).');
    }

    /**
     * [REGRESSION] Pertanyaan rekomendasi/konseling jurusan (intent yang TIDAK di-fast-path)
     * harus tetap dilayani jalur AI dan TIDAK jatuh ke fallback.
     */
    public function test_recommendation_question_is_not_forced_to_fallback(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);
        config(['chat.fastpath_enabled' => true]);

        SchoolAssistantAgent::fake(['Rekomendasi jurusan untukmu adalah PPLG.']);

        $response = app(NesaiService::class)->respond(
            'saya ingin masuk rpl, apakah cocok?',
            [],
            'recommendation-session',
        );

        $this->assertSame('ai-agent', $response['mode']);
        $this->assertSame('Rekomendasi jurusan untukmu adalah PPLG.', $response['answer']);
    }
}
