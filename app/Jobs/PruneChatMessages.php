<?php

namespace App\Jobs;

use App\Models\ChatSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * [CORE-LOGIC: DEFERRED-PRUNING]
 * Memangkas pesan lama pada satu sesi chat agar riwayat tetap terbatas, TANPA
 * membebani jalur respons request. Dijalankan sebagai job queued (bukan sinkron).
 *
 * Job ini idempoten: bila sesi sudah tidak ada, atau jumlah pesan <= batas, ia berhenti
 * tanpa efek samping.
 */
class PruneChatMessages implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $chatSessionId) {}

    public function handle(): void
    {
        $storedLimit = max(2, (int) config('chat.stored_messages_limit', 100));

        $session = ChatSession::find($this->chatSessionId);

        if ($session === null) {
            return;
        }

        $messageCount = $session->messages()->count();

        if ($messageCount <= $storedLimit) {
            return;
        }

        // Hitung id pesan terbaru yang dipertahankan, lalu hapus sisanya secara batch.
        $keepIds = $session->messages()
            ->orderByDesc('id')
            ->limit($storedLimit)
            ->pluck('id');

        $session->messages()
            ->whereNotIn('id', $keepIds)
            ->limit(max(10, (int) config('chat.prune_batch_limit', 50)))
            ->delete();
    }
}
