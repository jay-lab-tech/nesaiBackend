<?php

/**
 * [CORE-LOGIC: JURUSAN-DATA-SOURCE]
 * Data statis kompetensi keahlian SMKN 1 Subang.
 *
 * File ini adalah satu-satunya sumber data jurusan untuk MVP (tanpa database).
 * Digunakan oleh:
 *   - GetJurusanInfoTool   → pencarian & info jurusan via chat AI
 *   - RecommendJurusanTool → scoring rekomendasi jurusan berdasarkan minat
 *   - RecommendationService → endpoint form rekomendasi /api/v1/recommendations/majors
 *
 * DRAFT — Kata kunci minat, prospek karir, dan mata pelajaran utama di bawah ini
 * adalah template representatif. Tim konten WAJIB review & sesuaikan dengan data
 * riil SMKN 1 Subang sebelum demo juri JHIC 2.0.
 */

return [

    // =========================================================================
    // RUMPUN TEKNOLOGI INFORMASI & KOMUNIKASI
    // =========================================================================

    [
        'nama' => 'Teknik Komputer Jaringan',
        'slug' => 'tkj', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari perancangan, pemasangan, dan pemeliharaan jaringan komputer (LAN, WAN, wireless), administrasi server, keamanan jaringan (cyber security dasar), serta troubleshooting perangkat keras dan lunak jaringan.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'jaringan', 'komputer', 'internet', 'server', 'wifi',
            'networking', 'cyber security', 'keamanan', 'hardware',
            'IT', 'teknologi', 'router', 'mikrotik', 'linux',
        ],
        'prospek_karir' => [
            'Network Administrator',
            'IT Support Specialist',
            'System Administrator',
            'Cyber Security Analyst',
            'Network Engineer',
            'Teknisi Komputer & Jaringan',
        ],
        'mata_pelajaran_utama' => [
            'Dasar-Dasar Teknik Jaringan',
            'Administrasi Infrastruktur Jaringan',
            'Administrasi Sistem Jaringan',
            'Keamanan Jaringan',
            'Teknologi Layanan Jaringan',
        ],
    ],

    [
        'nama' => 'Pengembangan Perangkat Lunak dan Gim',
        'slug' => 'pplg', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari pemrograman (web, mobile, dan desktop), pengembangan aplikasi perangkat lunak, desain dan pembuatan game, manajemen basis data, serta metodologi pengembangan software modern.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'coding', 'programming', 'pemrograman', 'komputer', 'game',
            'aplikasi', 'web', 'software', 'digital', 'IT',
            'developer', 'teknologi', 'mobile', 'python', 'javascript',
        ],
        'prospek_karir' => [
            'Software Developer',
            'Web Developer',
            'Game Developer',
            'Mobile App Developer',
            'Full Stack Developer',
            'Database Administrator',
        ],
        'mata_pelajaran_utama' => [
            'Pemrograman Dasar',
            'Pemrograman Berorientasi Objek',
            'Pemrograman Web dan Perangkat Bergerak',
            'Basis Data',
            'Produk Kreatif dan Kewirausahaan',
        ],
    ],

    [
        'nama' => 'Desain Komunikasi Visual',
        'slug' => 'dkv', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari desain grafis, ilustrasi digital, fotografi, videografi, animasi, branding, dan layout media cetak maupun digital untuk kebutuhan komunikasi visual profesional.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'desain', 'design', 'gambar', 'menggambar', 'seni',
            'kreatif', 'fotografi', 'video', 'animasi', 'grafis',
            'ilustrasi', 'editing', 'photoshop', 'canva', 'visual',
        ],
        'prospek_karir' => [
            'Graphic Designer',
            'UI/UX Designer',
            'Illustrator',
            'Fotografer Profesional',
            'Video Editor & Content Creator',
            'Brand Designer',
        ],
        'mata_pelajaran_utama' => [
            'Dasar-Dasar Desain Grafis',
            'Desain Media Interaktif',
            'Fotografi dan Videografi',
            'Animasi 2D dan 3D',
            'Komputer Grafis',
        ],
    ],

    // =========================================================================
    // RUMPUN TEKNIK MESIN & OTOMOTIF
    // =========================================================================

    [
        'nama' => 'Teknik Mesin',
        'slug' => 'teknik-mesin', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari teknik pemesinan (bubut, frais, gerinda), pengelasan, fabrikasi logam, gambar teknik (CAD), serta perawatan dan perbaikan mesin-mesin industri.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'mesin', 'teknik', 'las', 'pengelasan', 'besi',
            'logam', 'industri', 'pabrik', 'manufaktur', 'bubut',
            'bengkel', 'mechanical', 'engineering', 'CAD', 'fabrikasi',
        ],
        'prospek_karir' => [
            'Teknisi Mesin Industri',
            'Welder / Juru Las Profesional',
            'Operator CNC',
            'Drafter CAD/CAM',
            'Quality Control Inspector',
            'Supervisor Produksi Manufaktur',
        ],
        'mata_pelajaran_utama' => [
            'Teknik Pemesinan Bubut',
            'Teknik Pemesinan Frais',
            'Teknik Pengelasan',
            'Gambar Teknik Mesin',
            'Teknik Fabrikasi Logam dan Manufaktur',
        ],
    ],

    [
        'nama' => 'Teknik Otomotif',
        'slug' => 'teknik-otomotif', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari perawatan, perbaikan, dan overhaul kendaraan bermotor (sepeda motor dan mobil), meliputi sistem kelistrikan, mesin, sasis, dan transmisi otomotif.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'otomotif', 'mobil', 'motor', 'kendaraan', 'mesin',
            'bengkel', 'montir', 'modifikasi', 'balap', 'racing',
            'automotive', 'sepeda motor', 'mekanik', 'teknisi', 'oli',
        ],
        'prospek_karir' => [
            'Mekanik / Teknisi Otomotif',
            'Service Advisor',
            'Instruktur Otomotif',
            'Wirausaha Bengkel',
            'Quality Control Otomotif',
            'Sales Engineer Otomotif',
        ],
        'mata_pelajaran_utama' => [
            'Pemeliharaan Mesin Kendaraan Ringan',
            'Pemeliharaan Sasis dan Pemindah Tenaga',
            'Pemeliharaan Kelistrikan Kendaraan Ringan',
            'Teknik Dasar Otomotif',
            'Teknologi Dasar Otomotif',
        ],
    ],

    // =========================================================================
    // RUMPUN BISNIS & MANAJEMEN
    // =========================================================================

    [
        'nama' => 'Akuntansi Keuangan Lembaga',
        'slug' => 'akl', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari pencatatan transaksi keuangan, penyusunan laporan keuangan, perpajakan, akuntansi perbankan, dan pengelolaan keuangan lembaga/perusahaan sesuai standar akuntansi.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'akuntansi', 'keuangan', 'hitung', 'matematika', 'angka',
            'pembukuan', 'pajak', 'bank', 'ekonomi', 'bisnis',
            'laporan keuangan', 'administrasi', 'akuntan', 'finance', 'accounting',
        ],
        'prospek_karir' => [
            'Staff Akuntansi',
            'Staff Keuangan / Finance',
            'Kasir / Teller Bank',
            'Tax Consultant (Konsultan Pajak)',
            'Internal Auditor',
            'Accounting Manager',
        ],
        'mata_pelajaran_utama' => [
            'Akuntansi Dasar',
            'Akuntansi Keuangan',
            'Akuntansi Perusahaan Jasa, Dagang, dan Manufaktur',
            'Komputer Akuntansi (MYOB/Accurate)',
            'Administrasi Pajak',
        ],
    ],

    [
        'nama' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'slug' => 'mplb', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari administrasi perkantoran modern, pengelolaan arsip dan dokumen, korespondensi bisnis, pelayanan pelanggan, serta penguasaan teknologi otomasi perkantoran.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'administrasi', 'kantor', 'surat-menyurat', 'organisasi', 'rapi',
            'manajemen', 'bisnis', 'office', 'sekretaris', 'arsip',
            'komputer', 'pelayanan', 'komunikasi', 'presentasi', 'Microsoft Office',
        ],
        'prospek_karir' => [
            'Staff Administrasi',
            'Sekretaris',
            'Office Manager',
            'Resepsionis',
            'Customer Service Officer',
            'Human Resource Administration',
        ],
        'mata_pelajaran_utama' => [
            'Dasar-Dasar Manajemen Perkantoran',
            'Teknologi Perkantoran (Otomasi)',
            'Kearsipan',
            'Korespondensi',
            'Layanan Bisnis dan Humas',
        ],
    ],

    [
        'nama' => 'Pemasaran',
        'slug' => 'pemasaran', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari strategi pemasaran konvensional dan digital (digital marketing), riset pasar, pengelolaan bisnis ritel, teknik penjualan, serta kewirausahaan dan e-commerce.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'jualan', 'bisnis', 'marketing', 'dagang', 'toko',
            'online shop', 'e-commerce', 'sosial media', 'iklan', 'promosi',
            'wirausaha', 'entrepreneur', 'digital marketing', 'branding', 'negosiasi',
        ],
        'prospek_karir' => [
            'Digital Marketing Specialist',
            'Sales Executive',
            'Social Media Manager',
            'Entrepreneur / Wirausahawan',
            'Visual Merchandiser',
            'E-Commerce Specialist',
        ],
        'mata_pelajaran_utama' => [
            'Marketing / Pemasaran',
            'Pengelolaan Bisnis Ritel',
            'Penataan Produk',
            'Komunikasi Bisnis',
            'Bisnis Online / E-Commerce',
        ],
    ],

    // =========================================================================
    // RUMPUN LOGISTIK & KULINER
    // =========================================================================

    [
        'nama' => 'Teknik Logistik',
        'slug' => 'teknik-logistik', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari manajemen rantai pasok (supply chain), pengelolaan gudang dan pergudangan, distribusi barang, transportasi logistik, serta sistem informasi logistik modern.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'logistik', 'gudang', 'pengiriman', 'ekspedisi', 'supply chain',
            'distribusi', 'transportasi', 'manajemen', 'barang', 'stok',
            'warehouse', 'shipping', 'kurir', 'cargo', 'inventory',
        ],
        'prospek_karir' => [
            'Staff Logistik',
            'Warehouse Supervisor',
            'Supply Chain Analyst',
            'Purchasing Staff',
            'Distribution Planner',
            'Freight Forwarder',
        ],
        'mata_pelajaran_utama' => [
            'Dasar-Dasar Teknik Logistik',
            'Pengelolaan Gudang (Warehousing)',
            'Manajemen Transportasi dan Distribusi',
            'Sistem Informasi Logistik',
            'Manajemen Rantai Pasok (Supply Chain)',
        ],
    ],

    [
        'nama' => 'Kuliner',
        'slug' => 'kuliner', /* DRAFT — sesuaikan dengan slug frontend Next.js */
        'deskripsi' => 'Mempelajari teknik memasak (cuisine) nusantara dan internasional, pastry & bakery, food plating dan garnish, pengelolaan dapur profesional, sanitasi makanan, serta kewirausahaan kuliner.',
        'kuota' => 10,
        'kata_kunci_minat' => [
            'masak', 'memasak', 'kuliner', 'makanan', 'kue',
            'baking', 'pastry', 'chef', 'restoran', 'koki',
            'resep', 'food', 'dapur', 'catering', 'tata boga',
        ],
        'prospek_karir' => [
            'Chef / Juru Masak Profesional',
            'Pastry Chef',
            'Food Stylist',
            'Wirausaha Kuliner (Restoran/Kafe)',
            'Catering Manager',
            'Food & Beverage Supervisor',
        ],
        'mata_pelajaran_utama' => [
            'Pengolahan dan Penyajian Makanan',
            'Pastry dan Bakery',
            'Tata Hidang',
            'Sanitasi, Hygiene, dan Keselamatan Kerja',
            'Pengelolaan Usaha Jasa Boga',
        ],
    ],

];