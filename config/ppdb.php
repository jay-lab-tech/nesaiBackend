<?php

/**
 * [CORE-LOGIC: PPDB-DATA-SOURCE]
 * Data statis Penerimaan Peserta Didik Baru (PPDB) SMKN 1 Subang.
 *
 * File ini adalah satu-satunya sumber data PPDB untuk MVP (tanpa database).
 * Digunakan oleh:
 *   - GetPpdbInfoTool     → pencarian info PPDB via chat AI NESAI
 *   - ContentController   → endpoint REST API /api/v1/ppdb
 *
 * DRAFT — Jadwal dan syarat di bawah ini adalah perkiraan berdasarkan pola PPDB tahun sebelumnya.
 * Panitia PPDB WAJIB review & sesuaikan data aktual sebelum demo juri JHIC 2.0.
 */

return [

    // =========================================================================
    // INFORMASI UMUM
    // =========================================================================

    'tahun_ajaran' => '2027/2028',
    'gelombang' => 'Gelombang 1',
    'status' => 'Akan Segera Dibuka', /* Nilai: 'Akan Segera Dibuka', 'Sedang Berlangsung', 'Ditutup' */

    // =========================================================================
    // JADWAL PENDAFTARAN
    // =========================================================================

    'jadwal' => [
        [
            'tahap' => 'Pendaftaran Online',
            'tanggal_mulai' => '1 Juni 2027',
            'tanggal_selesai' => '15 Juni 2027',
            'keterangan' => 'Pengisian formulir pendaftaran melalui portal PPDB online.',
        ],
        [
            'tahap' => 'Verifikasi Berkas',
            'tanggal_mulai' => '16 Juni 2027',
            'tanggal_selesai' => '20 Juni 2027',
            'keterangan' => 'Verifikasi dan validasi dokumen persyaratan di sekolah.',
        ],
        [
            'tahap' => 'Tes Seleksi / Wawancara',
            'tanggal_mulai' => '21 Juni 2027',
            'tanggal_selesai' => '25 Juni 2027',
            'keterangan' => 'Tes minat bakat dan/atau wawancara calon peserta didik.',
        ],
        [
            'tahap' => 'Pengumuman Hasil Seleksi',
            'tanggal_mulai' => '28 Juni 2027',
            'tanggal_selesai' => '28 Juni 2027',
            'keterangan' => 'Pengumuman daftar peserta didik yang diterima melalui portal dan media sosial sekolah.',
        ],
        [
            'tahap' => 'Daftar Ulang',
            'tanggal_mulai' => '29 Juni 2027',
            'tanggal_selesai' => '5 Juli 2027',
            'keterangan' => 'Pendaftaran ulang dan pelunasan administrasi bagi peserta didik yang diterima.',
        ],
        [
            'tahap' => 'Masa Pengenalan Lingkungan Sekolah (MPLS)',
            'tanggal_mulai' => '14 Juli 2027',
            'tanggal_selesai' => '16 Juli 2027',
            'keterangan' => 'Orientasi dan pengenalan lingkungan sekolah bagi siswa baru.',
        ],
    ],

    // =========================================================================
    // SYARAT UMUM PENDAFTARAN
    // =========================================================================

    'syarat_umum' => [
        'Lulusan SMP/MTs atau sederajat (tahun lulus 2027 atau maksimal 3 tahun sebelumnya).',
        'Usia maksimal 21 tahun pada tanggal 1 Juli 2027.',
        'Sehat jasmani dan rohani (dibuktikan dengan surat keterangan dokter).',
        'Berkelakuan baik (dibuktikan dengan surat keterangan dari sekolah asal).',
        'Tidak terlibat penggunaan narkoba dan zat adiktif lainnya.',
    ],

    // =========================================================================
    // BERKAS / DOKUMEN YANG DIPERLUKAN
    // =========================================================================

    'berkas' => [
        'Fotokopi Ijazah/SKHUN atau Surat Keterangan Lulus (yang dilegalisir).',
        'Fotokopi Rapor semester 1 sampai 5.',
        'Fotokopi Kartu Keluarga (KK).',
        'Fotokopi Akta Kelahiran.',
        'Pas foto berwarna ukuran 3×4 sebanyak 4 lembar.',
        'Surat Keterangan Sehat dari dokter/puskesmas.',
        'Surat Keterangan Berkelakuan Baik dari sekolah asal.',
        'Fotokopi Kartu Indonesia Pintar (KIP) atau kartu bantuan sejenis (jika ada).',
    ],

    // =========================================================================
    // JALUR SELEKSI
    // =========================================================================

    'jalur_seleksi' => [
        [
            'nama' => 'Jalur Zonasi',
            'deskripsi' => 'Seleksi berdasarkan jarak tempat tinggal calon peserta didik dengan sekolah.',
            'kuota_persen' => 50,
        ],
        [
            'nama' => 'Jalur Prestasi',
            'deskripsi' => 'Seleksi berdasarkan prestasi akademik (nilai rapor) dan/atau non-akademik (kejuaraan, sertifikat).',
            'kuota_persen' => 30,
        ],
        [
            'nama' => 'Jalur Afirmasi',
            'deskripsi' => 'Seleksi untuk calon peserta didik dari keluarga kurang mampu, pemegang KIP, atau kondisi tertentu.',
            'kuota_persen' => 15,
        ],
        [
            'nama' => 'Jalur Perpindahan Orang Tua/Wali',
            'deskripsi' => 'Seleksi untuk calon peserta didik yang orang tua/walinya berpindah tugas atau domisili.',
            'kuota_persen' => 5,
        ],
    ],

    // =========================================================================
    // ALUR / TAHAPAN PENDAFTARAN
    // =========================================================================

    'alur_pendaftaran' => [
        '1. Buat akun di portal PPDB online SMKN 1 Subang.',
        '2. Isi formulir pendaftaran online dengan data yang lengkap dan benar.',
        '3. Unggah dokumen persyaratan dalam format PDF/JPG.',
        '4. Pilih kompetensi keahlian (jurusan) yang diminati (maksimal 2 pilihan).',
        '5. Cetak bukti pendaftaran online.',
        '6. Datang ke sekolah untuk verifikasi berkas asli sesuai jadwal.',
        '7. Ikuti tes seleksi / wawancara (jika diperlukan).',
        '8. Pantau pengumuman hasil seleksi melalui portal atau media sosial sekolah.',
        '9. Lakukan daftar ulang jika dinyatakan diterima.',
    ],

    // =========================================================================
    // BIAYA
    // =========================================================================

    'biaya' => [
        'pendaftaran' => 'Gratis (tidak dipungut biaya pendaftaran).',
        'spp' => 'Gratis — SMKN 1 Subang adalah sekolah negeri yang dibiayai pemerintah.',
        'seragam_dan_atribut' => 'Informasi biaya seragam dan atribut akan disampaikan saat daftar ulang.',
    ],

    // =========================================================================
    // LINK & KONTAK PPDB
    // =========================================================================

    'portal_ppdb' => 'https://ppdb.smkn1subang.sch.id', /* DRAFT — sesuaikan URL aktual */
    'portal_ppdb_jabar' => 'https://ppdb.jabarprov.go.id',

    'kontak_panitia' => [
        'telepon' => '(0260) 411547',
        'whatsapp' => '08xx-xxxx-xxxx', /* DRAFT — sesuaikan nomor WA panitia */
        'email' => 'ppdb@smkn1subang.sch.id',
        'instagram' => 'https://instagram.com/smkn1subang_official',
    ],

    // =========================================================================
    // CATATAN / INFORMASI TAMBAHAN
    // =========================================================================

    'catatan' => [
        'Jadwal dan ketentuan PPDB dapat berubah mengikuti kebijakan Dinas Pendidikan Provinsi Jawa Barat.',
        'Calon peserta didik disarankan memantau informasi terbaru melalui website dan media sosial resmi sekolah.',
        'Untuk pertanyaan lebih lanjut, silakan hubungi panitia PPDB melalui kontak yang tersedia.',
    ],

];
