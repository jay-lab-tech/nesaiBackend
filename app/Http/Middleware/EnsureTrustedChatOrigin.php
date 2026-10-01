<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * [CORE-LOGIC: ORIGIN-BINDING-GUARD]
 * Endpoint chatbot AI berjalan pada middleware `web` tanpa proteksi CSRF
 * (agar mudah dipanggil dari SPA). Tanpa guard ini, situs pihak ketiga dapat
 * memicu request memakai cookie sesi korban (CSRF-like) dan menghabiskan kuota
 * provider AI berbayar.
 *
 * Guard ini fail-closed:
 * - Origin hanya berasal dari header Origin/Referer (TIDAK dari Host, karena Host
 *   dapat dipalsukan lewat proxy/forwarded header).
 * - Daftar origin diizinkan dibaca via config() sehingga tetap benar saat config:cache.
 * - Request yang membawa cookie sesi TANPA origin yang terverifikasi selalu ditolak.
 * - Request tanpa cookie sesi (server-to-server / test) tetap dilewatkan.
 */
class EnsureTrustedChatOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Deteksi cookie sesi dari header Cookie MENTAH (bukan cookie yang sudah
        // didekripsi oleh EncryptCookies), karena middleware `web` menjalankan
        // EncryptCookies lebih dulu dan menghapus cookie yang gagal didekripsi.
        $hasSessionCookie = $this->hasSessionCookie($request);
        $origin = $request->headers->get('Origin') ?? $this->originFromReferer($request);

        // Tidak ada origin yang bisa diverifikasi.
        if ($origin === null) {
            // Request dengan cookie sesi wajib lolos verifikasi origin.
            if ($hasSessionCookie) {
                abort(403, 'Origin tidak dapat diverifikasi.');
            }

            return $next($request);
        }

        $allowed = config('chat.trusted_origins', []);

        if (! in_array(rtrim($origin, '/'), $allowed, true)) {
            abort(403, 'Origin tidak diizinkan.');
        }

        return $next($request);
    }

    /**
     * Memeriksa keberadaan cookie sesi dari header Cookie mentah.
     */
    private function hasSessionCookie(Request $request): bool
    {
        $sessionName = config('session.cookie', 'laravel_session');
        $rawCookie = (string) $request->headers->get('Cookie', '');

        if ($rawCookie === '') {
            return $request->cookies->count() > 0;
        }

        return str_contains($rawCookie, $sessionName.'=')
            || str_contains($rawCookie, 'XSRF-TOKEN=')
            || $request->cookies->count() > 0;
    }

    private function originFromReferer(Request $request): ?string
    {
        $referer = $request->headers->get('Referer');

        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $parts = parse_url($referer);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port;
    }
}
