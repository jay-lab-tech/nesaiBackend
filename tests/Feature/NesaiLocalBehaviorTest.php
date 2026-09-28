<?php

namespace Tests\Feature;

use App\Ai\Agents\SchoolAssistantAgent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\Nesai\NesaiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_chat_routes_keep_the_known_ten_per_minute_limit(): void
    {
        foreach (['api/chat', 'api/v1/nesai/chat'] as $uri) {
            $route = collect(Route::getRoutes())->first(
                fn ($route) => $route->uri() === $uri && in_array('POST', $route->methods(), true)
            );

            $this->assertNotNull($route);
            $this->assertContains('throttle:10,1', $route->gatherMiddleware());
        }
    }
}
