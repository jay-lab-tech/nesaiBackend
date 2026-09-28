<?php

use App\Models\ChatSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('chat:prune {--days= : Retention window in days} {--apply : Delete expired sessions and cascading messages}', function (): int {
    $days = max(1, (int) ($this->option('days') ?: config('chat.retention_days', 90)));
    $cutoff = Carbon::now()->subDays($days);
    $sessions = ChatSession::query()
        ->where('created_at', '<', $cutoff)
        ->whereDoesntHave('messages', fn ($query) => $query->where('created_at', '>=', $cutoff));
    $count = (clone $sessions)->count();

    if (! $this->option('apply')) {
        $this->info("Dry run: {$count} chat sessions older than {$days} days would be deleted. Use --apply to delete them.");

        return self::SUCCESS;
    }

    $deleted = $sessions->delete();
    $this->info("Deleted {$deleted} chat sessions older than {$days} days (messages cascade with the session).");

    return self::SUCCESS;
})->purpose('Preview or prune expired NESAI chat sessions; deletion requires --apply');
