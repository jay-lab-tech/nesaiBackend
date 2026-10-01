<?php

namespace App\Services\Nesai;

use Illuminate\Support\Facades\Cache;

/**
 * [CORE-LOGIC: GEMINI-CIRCUIT-BREAKER]
 * Mencegah request menunggu Gemini ketika provider sedang bermasalah (kuota habis,
 * rate limit, timeout). Begitu kegagalan mencapai ambang, circuit OPEN dan seluruh
 * pemanggilan Gemini DILEWATI sepenuhnya → respons fallback cepat (<50ms) alih-alih
 * menumpuk di worker PHP-FPM hingga server tampak DOWN.
 *
 * State disimpan di Laravel Cache agar konsisten antar worker/proses (bukan per-request).
 *
 * Siklus: CLOSED → (gagal >= threshold dalam window) → OPEN → (setelah open_seconds) →
 *         HALF-OPEN (izinkan 1 percobaan) → sukses = CLOSED / gagal = OPEN.
 */
class GeminiCircuitBreaker
{
    private const KEY_FAILURES = 'nesai:circuit:failures';

    private const KEY_OPEN_UNTIL = 'nesai:circuit:open_until';

    private const KEY_HALF_OPEN_TRIAL = 'nesai:circuit:half_open_trial';

    /**
     * Apakah pemanggilan Gemini boleh dilakukan sekarang?
     * - CLOSED: ya.
     * - OPEN & belum waktunya half-open: tidak.
     * - OPEN & sudah waktunya: izinkan SATU percobaan (half-open).
     */
    public function allow(): bool
    {
        $openUntil = (int) Cache::get(self::KEY_OPEN_UNTIL, 0);

        if ($openUntil <= 0) {
            return true; // CLOSED
        }

        $now = $this->now();

        if ($now < $openUntil) {
            return false; // masih OPEN
        }

        // Waktunya half-open: izinkan tepat satu percobaan uji.
        if (Cache::add(self::KEY_HALF_OPEN_TRIAL, $now, 30)) {
            return true;
        }

        return false;
    }

    /**
     * Catat keberhasilan: reset kegagalan & tutup circuit.
     */
    public function recordSuccess(): void
    {
        Cache::forget(self::KEY_FAILURES);
        Cache::forget(self::KEY_OPEN_UNTIL);
        Cache::forget(self::KEY_HALF_OPEN_TRIAL);
    }

    /**
     * Catat kegagalan. Bila kegagalan berturut mencapai ambang dalam jendela waktu,
     * buka circuit.
     */
    public function recordFailure(): void
    {
        $threshold = max(1, (int) config('chat.circuit_fail_threshold', 5));
        $window = max(1, (int) config('chat.circuit_fail_window', 60));
        $openSeconds = max(1, (int) config('chat.circuit_open_seconds', 60));

        $failures = (int) Cache::get(self::KEY_FAILURES, 0) + 1;
        Cache::put(self::KEY_FAILURES, $failures, $window);

        if ($failures >= $threshold) {
            Cache::put(self::KEY_OPEN_UNTIL, $this->now() + $openSeconds, $openSeconds + 5);
            Cache::forget(self::KEY_FAILURES);
            Cache::forget(self::KEY_HALF_OPEN_TRIAL);
        }
    }

    /**
     * Apakah circuit sedang OPEN (untuk keperluan observability/logging).
     */
    public function isOpen(): bool
    {
        $openUntil = (int) Cache::get(self::KEY_OPEN_UNTIL, 0);

        return $openUntil > 0 && $this->now() < $openUntil;
    }

    /**
     * Reset paksa (dipakai test).
     */
    public function reset(): void
    {
        Cache::forget(self::KEY_FAILURES);
        Cache::forget(self::KEY_OPEN_UNTIL);
        Cache::forget(self::KEY_HALF_OPEN_TRIAL);
    }

    private function now(): int
    {
        return (int) now()->getTimestamp();
    }
}
