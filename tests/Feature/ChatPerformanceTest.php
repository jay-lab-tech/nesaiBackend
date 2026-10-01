<?php

namespace Tests\Feature;

use App\Ai\Agents\SchoolAssistantAgent;
use App\Jobs\PruneChatMessages;
use App\Services\Nesai\NesaiService;
use Database\Seeders\SchoolPublicDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * [CORE-LOGIC: PERFORMANCE-REGRESSION-GUARD]
 * Menjaga agar jalur respons chat tetap ringan: tidak ada pruning sinkron di jalur
 * request, dan dedup mencegah panggilan provider berulang. Ambang waktu dibuat longgar
 * agar tidak flaky di CI, tetapi cukup ketat untuk mendeteksi regresi signifikan.
 */
class ChatPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_persistence_path_does_not_run_synchronous_pruning(): void
    {
        Queue::fake();
        SchoolAssistantAgent::fake(fn () => 'Jawaban singkat.');

        app(NesaiService::class)->respond('Pertanyaan performa', [], 'perf-session');

        // Pruning HARUS dijadwalkan sebagai job (bukan dijalankan sinkron di request).
        Queue::assertPushed(PruneChatMessages::class);
    }

    public function test_identical_requests_trigger_provider_only_once(): void
    {
        config(['chat.idempotency_seconds' => 30]);

        $calls = 0;
        SchoolAssistantAgent::fake(function () use (&$calls) {
            $calls++;

            return 'Jawaban unik.';
        });

        $service = app(NesaiService::class);
        for ($i = 0; $i < 10; $i++) {
            $service->respond('Pesan yang sama persis', [], 'dedup-perf');
        }

        // 10 request identik → hanya 1 panggilan provider (dedup aktif).
        $this->assertSame(1, $calls);
    }

    public function test_non_llm_endpoints_stay_fast_under_sequential_load(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);

        // Naikkan plafon rate limit agar benchmark mengukur kecepatan, bukan limiter.
        config(['chat.rate_limit_recommendations' => 10000]);
        RateLimiter::clear('recommendations:ip:127.0.0.1');

        $start = hrtime(true);
        for ($i = 0; $i < 50; $i++) {
            $this->postJson('/api/v1/recommendations/majors', [
                'interests' => ['coding', 'desain', 'jaringan'],
            ])->assertStatus(200);
        }
        $avgMs = ((hrtime(true) - $start) / 1_000_000) / 50;

        // Endpoint rekomendasi (tanpa LLM) harus jauh di bawah 100ms rata-rata.
        $this->assertLessThan(100, $avgMs, "Recommendation endpoint too slow: {$avgMs}ms avg");
    }
}
