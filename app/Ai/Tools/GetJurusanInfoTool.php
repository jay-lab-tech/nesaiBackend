<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetJurusanInfoTool implements Tool
{
    /**
     * [CORE-LOGIC: ALIAS-NORMALIZATION]
     * Kamus normalisasi singkatan/istilah populer ke nama resmi kompetensi keahlian SMKN 1 Subang.
     * Mencegah LLM gagal menemukan jurusan akibat variasi singkatan siswa (misal: RPL -> PPLG, AP -> MPLB).
     *
     * @var array<string, string>
     */
    protected array $aliases = [
        'tkj' => 'Teknik Komputer Jaringan',
        'pplg' => 'Pengembangan Perangkat Lunak dan Gim',
        'rpl' => 'Pengembangan Perangkat Lunak dan Gim',
        'dkv' => 'Desain Komunikasi Visual',
        'akl' => 'Akuntansi Keuangan Lembaga',
        'ak' => 'Akuntansi Keuangan Lembaga',
        'mplb' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'otkp' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'ap' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'ps' => 'Pemasaran',
        'pm' => 'Pemasaran',
        'bdp' => 'Pemasaran',
        'tbsm' => 'Teknik Otomotif',
        'tkr' => 'Teknik Otomotif',
        'otomotif' => 'Teknik Otomotif',
        'tp' => 'Teknik Mesin',
        'tm' => 'Teknik Mesin',
        'mesin' => 'Teknik Mesin',
        'tlog' => 'Teknik Logistik',
        'logistik' => 'Teknik Logistik',
        'tb' => 'Kuliner',
        'tata boga' => 'Kuliner',
        'boga' => 'Kuliner',
    ];

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'get_jurusan_info';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Mengambil data jurusan atau kompetensi keahlian resmi di SMKN 1 Subang (nama jurusan, deskripsi, kuota). Dapat digunakan untuk mencari jurusan tertentu berdasarkan nama atau singkatan, ataupun menampilkan seluruh daftar jurusan.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: STATIC-CONFIG-SOURCE]
        // Sesuai aturan MVP kompetisi: data jurusan dibaca statis dari config/jurusan.php (bukan database)
        // untuk menjamin response time rendah (<10ms) dan deterministik tanpa bottleneck I/O database.
        $jurusanList = config('jurusan', []);

        if (empty($jurusanList)) {
            return 'Data jurusan belum tersedia saat ini di konfigurasi sekolah.';
        }

        $query = $request->string('nama')->trim()->toString();

        if ($query === '') {
            return "Daftar seluruh jurusan di SMKN 1 Subang (" . count($jurusanList) . " jurusan):\n\n"
                . json_encode($jurusanList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $lowerQuery = strtolower($query);
        $searchTerms = [$lowerQuery];

        if (isset($this->aliases[$lowerQuery])) {
            $searchTerms[] = strtolower($this->aliases[$lowerQuery]);
        }

        // [CORE-LOGIC: FUZZY-SEARCH-FILTER]
        // Filter fleksibel mencocokkan query atau alias hasil normalisasi ke field nama maupun deskripsi jurusan.
        $filtered = array_values(array_filter($jurusanList, function ($item) use ($searchTerms) {
            $nama = strtolower($item['nama'] ?? '');
            $deskripsi = strtolower($item['deskripsi'] ?? '');

            foreach ($searchTerms as $term) {
                if (str_contains($nama, $term) || str_contains($deskripsi, $term)) {
                    return true;
                }
            }

            return false;
        }));

        if (empty($filtered)) {
            return "Tidak ditemukan jurusan yang cocok dengan kata kunci \"{$query}\". Berikut daftar seluruh jurusan yang tersedia di SMKN 1 Subang:\n\n"
                . json_encode($jurusanList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return "Ditemukan " . count($filtered) . " jurusan untuk kata kunci \"{$query}\":\n\n"
            . json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * [CORE-LOGIC: TOOL-JSON-SCHEMA]
     * Menyediakan JSON Schema parameter untuk function calling Gemini.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'nama' => $schema->string()
                ->description('Nama, kata kunci, atau singkatan jurusan yang ingin dicari (contoh: "TKJ", "PPLG", "RPL", "Akuntansi", "Mesin", "Kuliner"). Kosongkan jika ingin melihat semua jurusan.')
                ->nullable(),
        ];
    }
}
