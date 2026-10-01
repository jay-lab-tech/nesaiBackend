<?php

namespace App\Ai\Support;

use Illuminate\Support\Facades\Cache;

/**
 * [CORE-LOGIC: NESAI-CACHE-HUB]
 * Pusat manajemen cache untuk data chatbot NESAI. Menyediakan satu sumber
 * kebenaran untuk cache keys & TTL agar tidak lagi tersebar/hardcode di tiap tool.
 *
 * Cache di-invalidate otomatis melalui model events (lihat AppServiceProvider)
 * sehingga saat admin mengubah data via CMS, chatbot langsung menyajikan data terbaru
 * alih-alih menunggu TTL habis.
 */
class NesaiCache
{
    public const KEY_SCHOOL_PROFILE = 'nesai:school_profile';

    public const KEY_JURUSAN_LIST = 'nesai:jurusan_list';

    public const KEY_JURUSAN_SCORER = 'nesai:jurusan_scorer';

    public const KEY_PPDB = 'nesai:ppdb_data';

    /**
     * TTL default (detik). Turun dari 3600 untuk membatasi dampak data usang
     * bila ada perubahan di luar model event (mis. seeding/DB manual).
     */
    public const DEFAULT_TTL = 900;

    /**
     * @template T
     * @param  callable():T  $callback
     * @return T
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
    }

    /**
     * Menghapus seluruh cache yang bergantung pada data sekolah/jurusan/PPDB.
     */
    public static function flush(): void
    {
        Cache::forget(self::KEY_SCHOOL_PROFILE);
        Cache::forget(self::KEY_JURUSAN_LIST);
        Cache::forget(self::KEY_JURUSAN_SCORER);
        Cache::forget(self::KEY_PPDB);

        // Bersihkan key lama (versi sebelumnya) agar tidak menyajikan data usang
        // bila ada proses yang belum sepenuhnya ter-deploy.
        Cache::forget('nesai:jurusan_list_db_v2');
        Cache::forget('nesai:jurusan_scorer_db_v2');
        Cache::forget('nesai:school_profile_v4');
        Cache::forget('nesai:ppdb_data_v2');
    }
}
