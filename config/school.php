<?php

/**
 * [CORE-LOGIC: SCHOOL-PROFILE-DATA-SOURCE]
 * Data statis profil resmi SMKN 1 Subang.
 *
 * File ini adalah satu-satunya sumber data profil sekolah untuk MVP (tanpa database).
 * Digunakan oleh:
 *   - GetSchoolInfoTool → pencarian info sekolah via chat AI NESAI
 *   - SchoolController  → endpoint REST API /api/v1/school
 *
 * DRAFT — Data di bawah ini adalah data representatif berdasarkan sumber publik.
 * Tim sekolah WAJIB review & sesuaikan sebelum demo juri JHIC 2.0.
 */

return [

    // =========================================================================
    // IDENTITAS RESMI
    // =========================================================================

    'nama_resmi' => 'SMK Negeri 1 Subang',
    'nama_pendek' => 'SMKN 1 Subang',
    'npsn' => '20220413',
    'status' => 'Negeri',
    'akreditasi' => 'A (Unggul)',
    'jenjang' => 'SMK',
    'tahun_berdiri' => 1965,

    // =========================================================================
    // ALAMAT & LOKASI
    // =========================================================================

    'alamat' => [
        'jalan' => 'Jl. Arief Rahman Hakim No. 35',
        'kelurahan' => 'Karanganyar',
        'kecamatan' => 'Subang',
        'kabupaten' => 'Subang',
        'provinsi' => 'Jawa Barat',
        'kode_pos' => '41211',
        'lengkap' => 'Jl. Arief Rahman Hakim No. 35, Karanganyar, Kec. Subang, Kab. Subang, Jawa Barat 41211',
    ],

    'koordinat' => [
        'latitude' => -6.5708,
        'longitude' => 107.7538,
        'google_maps_url' => 'https://maps.app.goo.gl/SMKN1Subang',
    ],

    // =========================================================================
    // KONTAK
    // =========================================================================

    'kontak' => [
        'telepon' => '(0260) 411547',
        'email' => 'smkn1subang@gmail.com',
        'website' => 'https://smkn1subang.sch.id',
    ],

    // =========================================================================
    // MEDIA SOSIAL
    // =========================================================================

    'media_sosial' => [
        'instagram' => 'https://instagram.com/smkn1subang_official',
        'facebook' => 'https://facebook.com/smkn1subang',
        'youtube' => 'https://youtube.com/@smkn1subang',
        'tiktok' => 'https://tiktok.com/@smkn1subang',
    ],

    // =========================================================================
    // VISI & MISI
    // =========================================================================

    'visi' => 'Menjadi SMK Pusat Keunggulan yang menghasilkan lulusan berkarakter, kompeten, berdaya saing global, dan berwawasan lingkungan.',

    'misi' => [
        'Menyelenggarakan pendidikan dan pelatihan berbasis Teaching Factory sesuai kebutuhan Dunia Usaha dan Dunia Industri (DUDI).',
        'Membangun karakter peserta didik yang berakhlak mulia, disiplin, kreatif, dan mandiri berdasarkan Profil Pelajar Pancasila.',
        'Meningkatkan kompetensi pendidik dan tenaga kependidikan melalui pengembangan profesional berkelanjutan.',
        'Mengembangkan kemitraan strategis dengan industri, perguruan tinggi, dan lembaga sertifikasi nasional maupun internasional.',
        'Menerapkan teknologi digital dalam pembelajaran dan manajemen sekolah.',
        'Mewujudkan lingkungan sekolah yang hijau, bersih, dan kondusif untuk pembelajaran.',
    ],

    // =========================================================================
    // SAMBUTAN KEPALA SEKOLAH
    // =========================================================================

    'kepala_sekolah' => [
        'nama' => 'Drs. H. Asep Supriatna, M.Pd.', /* DRAFT — sesuaikan nama aktual */
        'sambutan' => 'Selamat datang di SMKN 1 Subang. Kami berkomitmen mencetak lulusan yang siap kerja, siap wirausaha, dan siap melanjutkan pendidikan ke jenjang yang lebih tinggi. Dengan 10 kompetensi keahlian dan dukungan industri mitra, kami yakin setiap siswa akan menemukan jalur terbaiknya di sini.',
    ],

    // =========================================================================
    // SEJARAH SINGKAT
    // =========================================================================

    'sejarah' => 'SMKN 1 Subang didirikan pada tahun 1965 sebagai salah satu sekolah menengah kejuruan tertua di Kabupaten Subang, Jawa Barat. Awalnya hanya memiliki jurusan perdagangan dan administrasi, sekolah ini terus berkembang mengikuti kebutuhan dunia kerja. Saat ini SMKN 1 Subang memiliki 10 kompetensi keahlian yang mencakup rumpun Teknologi Informasi, Teknik Mesin & Otomotif, Bisnis & Manajemen, serta Logistik & Kuliner. Sebagai SMK Pusat Keunggulan, sekolah ini aktif menjalin kemitraan dengan berbagai industri dan lembaga sertifikasi profesi.',

    // =========================================================================
    // PROGRAM UNGGULAN
    // =========================================================================

    'program_unggulan' => [
        'SMK Pusat Keunggulan (SMK PK) — Program pemerintah untuk peningkatan kualitas lulusan dan link & match dengan industri.',
        'Teaching Factory — Model pembelajaran berbasis produksi dan jasa nyata bersama mitra DUDI.',
        'Program Sertifikasi Profesi — Peserta didik dapat memperoleh sertifikat kompetensi dari LSP-P1 dan BNSP.',
        'Bursa Kerja Khusus (BKK) — Memfasilitasi penyaluran lulusan ke dunia kerja.',
        'Kelas Industri — Kolaborasi kurikulum dan magang dengan perusahaan mitra.',
    ],

    // =========================================================================
    // JUMLAH KOMPETENSI KEAHLIAN
    // =========================================================================

    'jumlah_jurusan' => 10,
    'rumpun_keahlian' => [
        'Teknologi Informasi & Komunikasi' => ['Teknik Komputer Jaringan', 'Pengembangan Perangkat Lunak dan Gim', 'Desain Komunikasi Visual'],
        'Teknik Mesin & Otomotif' => ['Teknik Mesin', 'Teknik Otomotif'],
        'Bisnis & Manajemen' => ['Akuntansi Keuangan Lembaga', 'Manajemen Perkantoran dan Layanan Bisnis', 'Pemasaran'],
        'Logistik & Kuliner' => ['Teknik Logistik', 'Kuliner'],
    ],

];
