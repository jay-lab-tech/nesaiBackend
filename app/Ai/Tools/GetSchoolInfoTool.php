<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetSchoolInfoTool implements Tool
{
    /**
     * [CORE-LOGIC: SECTION-DICTIONARY]
     * Kamus pemetaan sinonim/alias dari input percakapan ke key konfigurasi config/school.php.
     * Mencegah LLM gagal mengambil bagian profil sekolah akibat variasi istilah pengguna.
     *
     * @var array<string, list<string>>
     */
    protected array $sectionAliases = [
        'identitas' => ['nama_resmi', 'nama_pendek', 'npsn', 'status', 'akreditasi', 'jenjang', 'tahun_berdiri'],
        'profil' => ['nama_resmi', 'nama_pendek', 'npsn', 'status', 'akreditasi', 'jenjang', 'tahun_berdiri'],
        'alamat' => ['alamat', 'koordinat'],
        'lokasi' => ['alamat', 'koordinat'],
        'peta' => ['alamat', 'koordinat'],
        'maps' => ['alamat', 'koordinat'],
        'kontak' => ['kontak', 'media_sosial'],
        'telepon' => ['kontak'],
        'email' => ['kontak'],
        'sosmed' => ['media_sosial'],
        'sosial_media' => ['media_sosial'],
        'instagram' => ['media_sosial'],
        'visi' => ['visi', 'misi'],
        'misi' => ['visi', 'misi'],
        'visi_misi' => ['visi', 'misi'],
        'kepala_sekolah' => ['kepala_sekolah'],
        'sejarah' => ['sejarah'],
        'program_unggulan' => ['program_unggulan'],
        'unggulan' => ['program_unggulan'],
        'jurusan' => ['jumlah_jurusan', 'rumpun_keahlian'],
        'rumpun' => ['jumlah_jurusan', 'rumpun_keahlian'],
    ];

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'get_school_info';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Mengambil informasi profil resmi SMKN 1 Subang: identitas sekolah (nama, NPSN, akreditasi), alamat & lokasi peta, kontak (telepon, email, website), media sosial, visi-misi, sejarah, sambutan kepala sekolah, dan program unggulan. Gunakan tool ini saat pengguna bertanya tentang profil, identitas, lokasi, kontak, atau visi-misi sekolah.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: STATIC-CONFIG-SOURCE]
        // Data profil sekolah dibaca statis dari config/school.php (bukan database)
        // untuk menjamin response time rendah dan deterministik tanpa bottleneck I/O database.
        $schoolData = config('school', []);

        if (empty($schoolData)) {
            return 'Data profil sekolah belum tersedia saat ini di konfigurasi.';
        }

        $section = strtolower(trim($request->string('section')->toString()));

        // Jika tidak ada section spesifik, kembalikan seluruh profil sekolah
        if ($section === '') {
            return "Profil lengkap SMKN 1 Subang:\n\n"
                . json_encode($schoolData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Cari section yang diminta melalui alias dictionary
        $keys = $this->sectionAliases[$section] ?? null;

        if ($keys === null) {
            // Jika alias tidak ditemukan, coba langsung akses key di config
            if (isset($schoolData[$section])) {
                return "Informasi \"{$section}\" SMKN 1 Subang:\n\n"
                    . json_encode([$section => $schoolData[$section]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // Fallback: kembalikan seluruh data
            return "Section \"{$section}\" tidak ditemukan. Berikut profil lengkap SMKN 1 Subang:\n\n"
                . json_encode($schoolData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Ambil hanya key-key yang relevan
        $filtered = [];
        foreach ($keys as $key) {
            if (isset($schoolData[$key])) {
                $filtered[$key] = $schoolData[$key];
            }
        }

        if (empty($filtered)) {
            return "Data untuk section \"{$section}\" tidak ditemukan di konfigurasi sekolah.";
        }

        return "Informasi \"{$section}\" SMKN 1 Subang:\n\n"
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
                ->description('Bagian informasi profil sekolah yang ingin diambil (contoh: "identitas", "alamat", "kontak", "visi_misi", "sejarah", "kepala_sekolah", "program_unggulan", "sosmed"). Kosongkan untuk mendapatkan seluruh profil sekolah.')
                ->nullable(),
        ];
    }
}
