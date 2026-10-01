<?php

namespace Tests\Feature;

use App\Ai\Agents\SchoolAssistantAgent;
use App\Jobs\PruneChatMessages;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\Nesai\NesaiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class NesaiLocalBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_separate_sessions_keep_separate_history_with_a_fake_provider(): void
    {
        SchoolAssistantAgent::fake(['Jawaban lokal A.', 'Jawaban lokal B.', 'Jawaban lokal C.']);
        $service = app(NesaiService::class);

        foreach (['session-a', 'session-b', 'session-c'] as $index => $sessionId) {
            $response = $service->respond('Pertanyaan '.($index + 1), [], $sessionId);
            $this->assertSame('ai-agent', $response['mode']);
        }

        $this->assertSame(3, ChatSession::query()->count());
        $this->assertSame(6, ChatMessage::query()->count());
        foreach (['session-a', 'session-b', 'session-c'] as $sessionId) {
            $this->assertSame(2, ChatSession::query()->where('session_id', $sessionId)->firstOrFail()->messages()->count());
        }
    }

    public function test_provider_exception_returns_fallback_and_does_not_store_failed_user_prompt(): void
    {
        SchoolAssistantAgent::fake(fn () => throw new RuntimeException('simulated provider outage'));

        $response = app(NesaiService::class)->respond('Pertanyaan uji', [], 'fallback-session');

        $this->assertSame('fallback-error', $response['mode']);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_active_session_history_is_capped(): void
    {
        config(['chat.stored_messages_limit' => 4]);
        SchoolAssistantAgent::fake(fn () => 'Jawaban singkat.');
        $service = app(NesaiService::class);

        for ($index = 1; $index <= 4; $index++) {
            $service->respond('Pertanyaan '.$index, [], 'bounded-session');
        }

        $session = ChatSession::query()->where('session_id', 'bounded-session')->firstOrFail();
        $this->assertSame(4, $session->messages()->count());
        $this->assertSame('model', $session->messages()->orderByDesc('id')->first()->role);
    }

    public function test_pruning_is_dispatched_as_a_deferred_job(): void
    {
        Queue::fake();
        SchoolAssistantAgent::fake(fn () => 'Jawaban singkat.');

        app(NesaiService::class)->respond('Halo', [], 'queued-prune-session');

        Queue::assertPushedOn(config('chat.prune_queue', 'default'), PruneChatMessages::class);
    }

    public function test_identical_message_within_window_is_deduplicated(): void
    {
        config(['chat.idempotency_seconds' => 30]);
        SchoolAssistantAgent::fake(['Jawaban idempotent.']);
        $service = app(NesaiService::class);

        $first = $service->respond('Pertanyaan sama', [], 'idem-session');
        $second = $service->respond('Pertanyaan sama', [], 'idem-session');

        // Respons kedua diambil dari cache idempotency → provider hanya dipanggil sekali.
        $this->assertSame($first, $second);
        $this->assertSame(2, ChatSession::query()->where('session_id', 'idem-session')->firstOrFail()->messages()->count());
    }

    public function test_blank_message_rejected_by_validation(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '',
        ], ['Origin' => 'http://localhost:3000']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    public function test_chat_routes_use_the_ip_bound_named_throttle(): void
    {
        foreach (['api/chat', 'api/v1/nesai/chat'] as $uri) {
            $route = collect(Route::getRoutes())->first(
                fn ($route) => $route->uri() === $uri && in_array('POST', $route->methods(), true)
            );

            $this->assertNotNull($route);
            // Rate limit mengikat pada IP (limiter "chat") + plafon global, bukan lagi
            // throttle:10,1 yang dapat di-reset dengan memutar cookie sesi baru.
            $this->assertContains('throttle:chat', $route->gatherMiddleware());
        }
    }

    public function test_search_and_recommendation_endpoints_are_throttled(): void
    {
        $search = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/search' && in_array('GET', $route->methods(), true)
        );
        $recommend = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === 'api/v1/recommendations/majors' && in_array('POST', $route->methods(), true)
        );

        $this->assertNotNull($search);
        $this->assertNotNull($recommend);
        $this->assertContains('throttle:search', $search->gatherMiddleware());
        $this->assertContains('throttle:recommendations', $recommend->gatherMiddleware());
    }

    public function test_context_rejects_more_than_five_items(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => 'Halo',
            'context' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
        ], ['Origin' => 'http://localhost:3000']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('context');
    }

    public function test_context_rejects_non_string_items(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => 'Halo',
            'context' => [['nested' => 'array']],
        ], ['Origin' => 'http://localhost:3000']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('context.0');
    }

    public function test_chat_rejects_request_from_untrusted_origin_with_session(): void
    {
        config(['session.driver' => 'array']);

        $response = $this->withUnencryptedCookie('laravel_session', 'attacker-session')
            ->postJson('/api/chat', ['message' => 'Halo'], ['Origin' => 'https://evil.example.com']);

        $response->assertStatus(403);
    }

    public function test_chat_rejects_request_with_session_cookie_but_no_origin(): void
    {
        // Fail-closed: request yang membawa cookie sesi namun tanpa Origin/Referer
        // yang dapat diverifikasi harus ditolak (mencegah CSRF-like dari pihak ketiga).
        $response = $this->call('POST', '/api/chat', [], ['laravel_session' => 'attacker-or-ambient-session']);

        $response->assertStatus(403);
    }
}
