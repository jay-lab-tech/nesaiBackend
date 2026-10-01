<?php

return [
    // Maximum number of stored conversation messages sent to Gemini per request.
    'history_limit' => (int) env('CHAT_HISTORY_LIMIT', 20),
    // Keep stored chat history bounded even when a session remains active.
    'stored_messages_limit' => max(2, (int) env('CHAT_STORED_MESSAGES_LIMIT', 100)),
    // Per-provider-call timeout. With MaxSteps(3), one chat request is bounded to 3 calls.
    // Default diturunkan 30s -> 10s: pada load test, timeout panjang membuat koneksi PHP-FPM
    // menumpuk dan server tampak DOWN. Gemini Flash normalnya membalas jauh di bawah 10s;
    // bila lebih lambat, lebih baik fallback cepat daripada menggantung.
    'provider_timeout' => min(60, max(1, (int) env('CHAT_PROVIDER_TIMEOUT', 10))),

    // [CORE-LOGIC: FAST-PATH]
    // Bila aktif, intent umum deterministik (PPDB, kontak/alamat, fasilitas, daftar jurusan,
    // profil) dijawab langsung dari database tanpa memanggil Gemini. Menghemat kuota &
    // menjaga latency tetap rendah saat stress test. Dapat dinonaktifkan tanpa deploy ulang.
    'fastpath_enabled' => filter_var(env('CHAT_FASTPATH_ENABLED', true), FILTER_VALIDATE_BOOL),

    // [CORE-LOGIC: CIRCUIT-BREAKER]
    // Ambang kegagalan Gemini berturut-turut sebelum circuit OPEN (skip Gemini sepenuhnya).
    'circuit_fail_threshold' => max(1, (int) env('CHAT_CIRCUIT_FAIL_THRESHOLD', 5)),
    // Jendela (detik) penghitungan kegagalan berturut-turut.
    'circuit_fail_window' => max(1, (int) env('CHAT_CIRCUIT_FAIL_WINDOW', 60)),
    // Durasi (detik) circuit OPEN sebelum mencoba half-open.
    'circuit_open_seconds' => max(1, (int) env('CHAT_CIRCUIT_OPEN_SECONDS', 60)),

    // [CORE-LOGIC: CONCURRENCY-CAP]
    // Jumlah maksimum request SERENTAK ke Gemini. Bila penuh, jalur AI dilewati
    // (fast-path/fallback) alih-alih mengantre panjang yang menguras worker.
    'max_concurrency' => max(1, (int) env('CHAT_MAX_CONCURRENCY', 3)),
    // Durasi (detik) lock concurrency bertahan bila proses pemegang lock mati mendadak.
    'concurrency_lock_seconds' => max(1, (int) env('CHAT_CONCURRENCY_LOCK_SECONDS', 15)),
    // Manual chat retention window; pruning is dry-run unless --apply is supplied.
    'retention_days' => (int) env('CHAT_RETENTION_DAYS', 90),

    // [CORE-LOGIC: RATE-LIMITS]
    // Chat dibatasi per-IP (bukan per-sesi) agar attacker tidak bisa mereset bucket
    // dengan cookie sesi baru; ditambah plafon global untuk melindungi kuota provider.
    'rate_limit_per_ip' => max(1, (int) env('CHAT_RATE_LIMIT_PER_IP', 60)),
    'rate_limit_global' => max(1, (int) env('CHAT_RATE_LIMIT_GLOBAL', 120)),
    'rate_limit_search' => max(1, (int) env('CHAT_RATE_LIMIT_SEARCH', 30)),
    'rate_limit_recommendations' => max(1, (int) env('CHAT_RATE_LIMIT_RECOMMENDATIONS', 20)),

    // [CORE-LOGIC: IDEMPOTENCY-WINDOW]
    // Jendela (detik) untuk men-dedup pesan identik dari sesi yang sama, sehingga
    // double-submit / auto-retry tidak menghabiskan kuota provider AI berbayar.
    'idempotency_seconds' => max(0, (int) env('CHAT_IDEMPOTENCY_SECONDS', 5)),

    // Batas pesan tersimpan yang dipangkas tiap request (bila pruning sinkron aktif).
    'prune_batch_limit' => max(10, (int) env('CHAT_PRUNE_BATCH_LIMIT', 50)),

    // Queue yang dipakai job pruning pesan (lihat App\Jobs\PruneChatMessages).
    'prune_queue' => env('CHAT_PRUNE_QUEUE', 'default'),

    // [CORE-LOGIC: TRUSTED-ORIGINS]
    // Daftar origin frontend yang diizinkan memanggil endpoint chat. WAJIB di-set eksplisit
    // di production (mis. https://smkn1subang.sch.id). Nilai default hanya untuk pengembangan.
    // Dibaca via config() (bukan env()) agar aman saat `php artisan config:cache`.
    'trusted_origins' => array_values(array_filter(array_map(
        fn ($value) => rtrim(trim((string) $value), '/'),
        explode(',', (string) env('CHAT_TRUSTED_ORIGINS', 'http://localhost:3000')),
    ))),
];
