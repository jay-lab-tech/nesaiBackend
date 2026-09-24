<?php

namespace App\Ai\Tools;

use App\Models\Ppdb;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPpdbInfoTool implements Tool
{
    /**
     * [CORE-LOGIC: SECTION-DICTIONARY]
     * Kamus pemetaan sinonim/alias dari input percakapan ke key konfigurasi PPDB.
     * Mencegah LLM gagal mengambil bagian PPDB akibat variasi istilah pengguna.
     *
     * @var array<string, list<string>>
     */
    protected array $sectionAliases = [
        'jadwal' => ['jadwal', 'tahun_ajaran', 'gelombang', 'status', 'schedule'],
        'waktu' => ['jadwal', 'tahun_ajaran', 'status', 'schedule'],
        'kapan' => ['jadwal', 'tahun_ajaran', 'status', 'schedule'],
        'tanggal' => ['jadwal', 'tahun_ajaran', 'schedule'],
        'syarat' => ['syarat_umum', 'berkas', 'requirements', 'persyaratan'],
        'persyaratan' => ['syarat_umum', 'berkas', 'requirements', 'persyaratan'],
        'berkas' => ['berkas', 'requirements'],
        'dokumen' => ['berkas', 'requirements'],
        'jalur' => ['jalur_seleksi'],
        'seleksi' => ['jalur_seleksi'],
        'zonasi' => ['jalur_seleksi'],
        'prestasi' => ['jalur_seleksi'],
        'afirmasi' => ['jalur_seleksi'],
        'alur' => ['alur_pendaftaran'],
        'prosedur' => ['alur_pendaftaran'],
        'cara_daftar' => ['alur_pendaftaran'],
        'tahapan' => ['alur_pendaftaran', 'jadwal'],
        'biaya' => ['biaya'],
        'gratis' => ['biaya'],
        'portal' => ['portal_ppdb', 'portal_ppdb_jabar'],
        'link' => ['portal_ppdb', 'portal_ppdb_jabar'],
        'website' => ['portal_ppdb', 'portal_ppdb_jabar'],
        'kontak' => ['kontak_panitia'],
        'panitia' => ['kontak_panitia'],
        'whatsapp' => ['kontak_panitia'],
        'catatan' => ['catatan'],
        'info' => ['tahun_ajaran', 'gelombang', 'status', 'catatan', 'title', 'deskripsi'],
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
        return 'Mengambil informasi Penerimaan Peserta Didik Baru (PPDB) SMKN 1 Subang: jadwal pendaftaran, syarat & berkas, jalur seleksi (zonasi/prestasi/afirmasi/perpindahan tugas), alur tahapan pendaftaran, biaya, link portal PPDB, dan kontak panitia. Gunakan tool ini saat pengguna bertanya tentang pendaftaran siswa baru, PPDB, syarat masuk, atau cara mendaftar.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: DATABASE-FIRST-SOURCE]
        // Mengambil data PPDB dari database jika tersedia, dengan fallback ke konfigurasi resmi sekolah
        $ppdbData = Cache::remember('nesai:ppdb_data_v2', 3600, function () {
            $ppdb = Ppdb::where('is_active', true)->latest()->first() ?? Ppdb::latest()->first();
            $fallbackConfig = config('ppdb', []);

            if (! $ppdb) {
                return $fallbackConfig;
            }

            return array_merge($fallbackConfig, [
                'title' => $ppdb->title,
                'deskripsi' => $ppdb->description,
                'persyaratan' => $ppdb->requirements ?? ($fallbackConfig['syarat_umum'] ?? []),
                'jadwal' => $ppdb->schedule ?? ($fallbackConfig['jadwal'] ?? []),
                'is_active' => (bool) $ppdb->is_active,
                'portal_ppdb' => $fallbackConfig['portal_ppdb_jabar'] ?? 'https://ppdb.jabarprov.go.id',
            ]);
        });

        if (empty($ppdbData)) {
            return 'Data PPDB belum tersedia saat ini di konfigurasi dan basis data sekolah.';
        }

        $section = strtolower(trim($request->string('section')->toString()));

        // Jika tidak ada section spesifik, kembalikan seluruh info PPDB
        if ($section === '') {
            $tahun = $ppdbData['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1);
            return "Informasi lengkap PPDB SMKN 1 Subang Tahun Ajaran {$tahun} (Sumber: Basis Data Resmi):\n\n"
                . json_encode($ppdbData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Cari section yang diminta melalui alias dictionary
        $keys = $this->sectionAliases[$section] ?? null;

        if ($keys === null) {
            // Jika alias tidak ditemukan, coba langsung akses key di array
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
            return "Data untuk section \"{$section}\" tidak ditemukan di informasi PPDB sekolah.";
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