<?php

namespace App\Services\Nesai;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * [CORE-LOGIC: GEMINI-CONCURRENCY-GUARD]
 * Membatasi jumlah request SERENTAK ke Gemini (mis. maks 3) agar lonjakan load test
 * tidak menghabiskan koneksi PHP-FPM / kuota provider sekaligus. Bila slot penuh,
 * pemanggil TIDAK mengantre panjang — ia langsung dialihkan ke jalur cepat
 * (fast-path/fallback) supaya server tetap responsif.
 *
 * Implementasi memakai atomic Cache lock agar konsisten antar proses:
 * satu lock bernama `nesai:gemini:slot:{n}` untuk n = 1..max_concurrency.
 * Pemanggil mencoba mengunci salah satu slot; berhasil = boleh memanggil Gemini.
 */
class GeminiConcurrencyGuard
{
    private const SLOT_PREFIX = 'nesai:gemini:slot:';

    /**
     * Jalankan $callback bila ada slot bebas; kembalikan null bila semua slot penuh.
     *
     * @template T
     * @param  Closure():T  $callback
     * @return T|null
     */
    public function run(Closure $callback): mixed
    {
        $max = max(1, (int) config('chat.max_concurrency', 3));
        $ttl = max(1, (int) config('chat.concurrency_lock_seconds', 15));

        for ($slot = 1; $slot <= $max; $slot++) {
            $lock = Cache::lock(self::SLOT_PREFIX.$slot, $ttl);

            if ($lock->get()) {
                try {
                    return $callback();
                } finally {
                    // Lepas slot agar request berikutnya dapat memakainya.
                    optional($lock)->release();
                }
            }
        }

        // Semua slot terpakai → jangan menunggu; biarkan pemanggil memilih fallback.
        return null;
    }
}
