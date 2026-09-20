<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPpdbInfoTool implements Tool
{
    /**
     * [CORE-LOGIC: SECTION-DICTIONARY]
     * Kamus pemetaan sinonim/alias dari input percakapan ke key konfigurasi config/ppdb.php.
     * Mencegah LLM gagal mengambil bagian PPDB akibat variasi istilah pengguna.
     *
     * @var array<string, list<string>>
     */
    protected array $sectionAliases = [
        'jadwal' => ['jadwal', 'tahun_ajaran', 'gelombang', 'status'],
        'waktu' => ['jadwal', 'tahun_ajaran', 'status'],
        'kapan' => ['jadwal', 'tahun_ajaran', 'status'],
        'tanggal' => ['jadwal', 'tahun_ajaran'],
        'syarat' => ['syarat_umum', 'berkas'],
        'persyaratan' => ['syarat_umum', 'berkas'],
        'berkas' => ['berkas'],
        'dokumen' => ['berkas'],
        'jalur' => ['jalur_seleksi'],
        'seleksi' => ['jalur_seleksi'],
        'zonasi' => ['jalur_seleksi'],
        'prestasi' => ['jalur_seleksi'],
        'afirmasi' => ['jalur_seleksi'],
        'alur' => ['alur_pendaftaran'],
        'prosedur' => ['alur_pendaftaran'],
        'cara_daftar' => ['alur_pendaftaran'],
        'tahapan' => ['alur_pendaftaran'],
        'biaya' => ['biaya'],
        'gratis' => ['biaya'],
        'portal' => ['portal_ppdb', 'portal_ppdb_jabar'],
        'link' => ['portal_ppdb', 'portal_ppdb_jabar'],
        'website' => ['portal_ppdb', 'portal_ppdb_jabar'],
        'kontak' => ['kontak_panitia'],
        'panitia' => ['kontak_panitia'],
        'whatsapp' => ['kontak_panitia'],
        'catatan' => ['catatan'],
        'info' => ['tahun_ajaran', 'gelombang', 'status', 'catatan'],
    ];

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'get_ppdb_info';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Mengambil informasi Penerimaan Peserta Didik Baru (PPDB) SMKN 1 Subang: jadwal pendaftaran, syarat & berkas, jalur seleksi (zonasi/prestasi/afirmasi), alur tahapan pendaftaran, biaya, link portal PPDB, dan kontak panitia. Gunakan tool ini saat pengguna bertanya tentang pendaftaran siswa baru, PPDB, syarat masuk, atau cara mendaftar.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: STATIC-CONFIG-SOURCE]
        // Data PPDB dibaca statis dari config/ppdb.php (bukan database)
        // untuk menjamin response time rendah dan deterministik tanpa bottleneck I/O database.
        $ppdbData = config('ppdb', []);

        if (empty($ppdbData)) {
            return 'Data PPDB belum tersedia saat ini di konfigurasi sekolah.';
        }

        $section = strtolower(trim($request->string('section')->toString()));

        // Jika tidak ada section spesifik, kembalikan seluruh info PPDB
        if ($section === '') {
            return "Informasi lengkap PPDB SMKN 1 Subang Tahun Ajaran " . ($ppdbData['tahun_ajaran'] ?? '') . ":\n\n"
                . json_encode($ppdbData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Cari section yang diminta melalui alias dictionary
        $keys = $this->sectionAliases[$section] ?? null;

        if ($keys === null) {
            // Jika alias tidak ditemukan, coba langsung akses key di config
            if (isset($ppdbData[$section])) {
                return "Informasi PPDB \"{$section}\" SMKN 1 Subang:\n\n"
                    . json_encode([$section => $ppdbData[$section]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // Fallback: kembalikan seluruh data PPDB
            return "Section \"{$section}\" tidak ditemukan. Berikut informasi lengkap PPDB SMKN 1 Subang:\n\n"
                . json_encode($ppdbData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Ambil hanya key-key yang relevan
        $filtered = [];
        foreach ($keys as $key) {
            if (isset($ppdbData[$key])) {
                $filtered[$key] = $ppdbData[$key];
            }
        }

        if (empty($filtered)) {
            return "Data untuk section \"{$section}\" tidak ditemukan di konfigurasi PPDB.";
        }

        return "Informasi PPDB \"{$section}\" SMKN 1 Subang:\n\n"
            . json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * [CORE-LOGIC: TOOL-JSON-SCHEMA]
     * Menyediakan JSON Schema parameter untuk function calling Gemini.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section' => $schema->string()
                ->description('Bagian informasi PPDB yang ingin diambil (contoh: "jadwal", "syarat", "berkas", "jalur", "alur", "biaya", "portal", "kontak"). Kosongkan untuk mendapatkan seluruh informasi PPDB.')
                ->nullable(),
        ];
    }
}
