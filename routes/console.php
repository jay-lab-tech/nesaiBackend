<?php

use App\Jobs\PruneChatMessages;
use App\Models\ChatSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('chat:prune {--days= : Retention window in days} {--apply : Delete expired sessions and cascading messages}', function (): int {
    $days = max(1, (int) ($this->option('days') ?: config('chat.retention_days', 90)));
    $cutoff = Carbon::now()->subDays($days);

    // [CORE-LOGIC: ACTIVITY-BASED-RETENTION]
    // Retensi berbasis AKTIVITAS terakhir (updated_at), bukan created_at. Sesi yang
    // dibuat lama tetapi masih aktif TIDAK boleh dihapus. Kami menghitung sesi yang
    // tidak memiliki pesan baru sejak cutoff.
    $sessions = ChatSession::query()
        ->where('updated_at', '<', $cutoff)
        ->whereDoesntHave('messages', fn ($query) => $query->where('created_at', '>=', $cutoff));
    $count = (clone $sessions)->count();

    if (! $this->option('apply')) {
        $this->info("Dry run: {$count} chat sessions inactive for more than {$days} days would be deleted. Use --apply to delete them.");

        return self::SUCCESS;
    }

    $deleted = $sessions->delete();
    $this->info("Deleted {$deleted} inactive chat sessions (older than {$days} days by last activity); messages cascade with the session.");

    return self::SUCCESS;
})->purpose('Preview or prune inactive NESAI chat sessions (by last activity); deletion requires --apply');

Artisan::command('chat:prune-messages {--limit=200 : Maximum number of sessions to reconcile per run}', function (): int {
    $limit = max(1, (int) $this->option('limit'));
    $storedLimit = max(2, (int) config('chat.stored_messages_limit', 100));

    // [CORE-LOGIC: DEFERRED-PRUNING-SWEEP]
    // Menyapu sesi yang riwayatnya melampaui batas dan menjadwalkan job pruning.
    // Menjaga ukuran riwayat tetap terbatas tanpa membebani jalur respons live.
    $sessions = ChatSession::query()
        ->has('messages', '>', $storedLimit)
        ->orderBy('updated_at')
        ->limit($limit)
        ->get(['id']);

    if ($sessions->isEmpty()) {
        $this->info('Tidak ada sesi yang melebihi batas pesan tersimpan.');

        return self::SUCCESS;
    }

    foreach ($sessions as $session) {
        PruneChatMessages::dispatch($session->id)
            ->onQueue(config('chat.prune_queue', 'default'));
    }

    $this->info("Menjadwalkan pruning untuk {$sessions->count()} sesi (batas {$storedLimit} pesan/ sesi).");

    return self::SUCCESS;
})->purpose('Sweep and schedule pruning of over-limit chat histories across sessions');

// [CORE-LOGIC: CHAT-MAINTENANCE-SCHEDULE]
// Menjaga riwayat chat tetap terbatas TANPA membebani jalur respons live:
// - `chat:prune-messages` menyapu sesi yang melebihi batas pesan tiap 5 menit.
// - `chat:prune --apply` menghapus sesi inaktif (>90 hari) sekali sehari.
//
// PENTING: command di bawah memerlukan scheduler berjalan:
//   * DEVELOPMENT : `php artisan schedule:work`
//   * PRODUCTION  : tambahkan 1 cron →
//       * * * * * cd /path-ke-proyek && php artisan schedule:run >> /dev/null 2>&1
// Karena CHAT_PRUNE_QUEUE memakai QUEUE_CONNECTION=database, job pruning juga
// membutuhkan queue worker: `php artisan queue:work --queue=default --tries=1`.
// Jalankan worker via supervisor (produksi) agar tabel `jobs` tidak menumpuk saat
// stress test. Alternatif cepat saat lomba: set CHAT_PRUNE_QUEUE=sync pada .env.
Schedule::command('chat:prune-messages')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('chat:prune --apply')->dailyAt('02:00')->withoutOverlapping();
