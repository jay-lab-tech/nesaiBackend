<?php

namespace App\Ai\Tools;

use App\Models\Major;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetJurusanInfoTool implements Tool
{
    /**
     * [CORE-LOGIC: ALIAS-NORMALIZATION]
     * Kamus normalisasi singkatan/istilah populer ke nama resmi atau slug kompetensi keahlian SMKN 1 Subang.
     * Mencegah LLM gagal menemukan jurusan akibat variasi singkatan siswa (misal: RPL -> PPLG, AP -> MPLB).
     *
     * @var array<string, string>
     */
    protected array $aliases = [
        'tkj' => 'Teknik Jaringan Komputer dan Telekomunikasi',
        'tjkt' => 'Teknik Jaringan Komputer dan Telekomunikasi',
        'pplg' => 'Pengembangan Perangkat Lunak dan Gim',
        'rpl' => 'Pengembangan Perangkat Lunak dan Gim',
        'dkv' => 'Desain Komunikasi Visual',
        'akl' => 'Akuntansi dan Keuangan Lembaga',
        'ak' => 'Akuntansi dan Keuangan Lembaga',
        'mplb' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'otkp' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'ap' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'ps' => 'Pemasaran',
        'pm' => 'Pemasaran',
        'bdp' => 'Pemasaran',
        'to' => 'Teknik Otomotif',
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
        'tata hidang' => 'Kuliner',
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
        return 'Mengambil data resmi jurusan atau kompetensi keahlian di SMKN 1 Subang langsung dari database (nama jurusan, slug, deskripsi, mata pelajaran kejuruan, prospek karir, dan karya inovasi). Dapat digunakan untuk mencari jurusan tertentu berdasarkan nama atau singkatan, ataupun menampilkan seluruh daftar jurusan.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: DATABASE-FIRST-SOURCE]
        // Mengambil seluruh data jurusan resmi langsung dari database (Major model) dengan relasi lengkap
        // Menggunakan Cache layer untuk menjamin performa cepat (<10ms).
        $jurusanList = Cache::remember('nesai:jurusan_list_db_v2', 3600, function () {
            $majors = Major::with(['subjects', 'careers', 'innovations'])->get();

            if ($majors->isEmpty()) {
                return config('jurusan', []);
            }

            $configList = config('jurusan', []);

            return $majors->map(function ($major) use ($configList) {
                // Pencocokan bantuan dengan config untuk kata kunci minat tambahan
                $configMatch = collect($configList)->first(function ($c) use ($major) {
                    $slugMatch = isset($c['slug']) && strtolower($c['slug']) === strtolower($major->slug);
                    $nameMatch = isset($c['nama']) && (
                        str_contains(strtolower($major->name), strtolower($c['nama'])) ||
                        str_contains(strtolower($c['nama']), strtolower($major->name))
                    );
                    return $slugMatch || $nameMatch;
                });

                return [
                    'nama' => $major->name,
                    'slug' => $major->slug,
                    'deskripsi' => $major->summary ?: $major->description,
                    'kuota' => $configMatch['kuota'] ?? 10,
                    'kata_kunci_minat' => $configMatch['kata_kunci_minat'] ?? [],
                    'prospek_karir' => $major->careers->pluck('name')->toArray(),
                    'mata_pelajaran_utama' => $major->subjects->pluck('name')->toArray(),
                    'karya_inovasi' => $major->innovations->pluck('name')->toArray(),
                ];
            })->toArray();
        });

        if (empty($jurusanList)) {
            return 'Data jurusan belum tersedia saat ini di basis data sekolah.';
        }

        $query = $request->string('nama')->trim()->toString();

        if ($query === '') {
            return "Daftar seluruh kompetensi keahlian resmi di SMKN 1 Subang (" . count($jurusanList) . " jurusan - Sumber: Basis Data Resmi):\n\n"
                . json_encode($jurusanList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $lowerQuery = strtolower($query);
        $searchTerms = [$lowerQuery];

        if (isset($this->aliases[$lowerQuery])) {
            $searchTerms[] = strtolower($this->aliases[$lowerQuery]);
        }

        // [CORE-LOGIC: FUZZY-SEARCH-FILTER]
        // Filter fleksibel mencocokkan query atau alias hasil normalisasi ke field nama, slug, deskripsi, ataupun mapel/karir
        $filtered = array_values(array_filter($jurusanList, function ($item) use ($searchTerms) {
            $nama = strtolower($item['nama'] ?? '');
            $slug = strtolower($item['slug'] ?? '');
            $deskripsi = strtolower($item['deskripsi'] ?? '');
            $karir = implode(' ', array_map('strtolower', $item['prospek_karir'] ?? []));
            $mapel = implode(' ', array_map('strtolower', $item['mata_pelajaran_utama'] ?? []));

            foreach ($searchTerms as $term) {
                if (
                    str_contains($nama, $term) ||
                    str_contains($slug, $term) ||
                    str_contains($deskripsi, $term) ||
                    str_contains($karir, $term) ||
                    str_contains($mapel, $term)
                ) {
                    return true;
                }
            }

            return false;
        }));

        if (empty($filtered)) {
            return "Tidak ditemukan jurusan yang cocok dengan kata kunci \"{$query}\". Berikut daftar seluruh jurusan resmi yang tersedia di SMKN 1 Subang:\n\n"
                . json_encode($jurusanList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return "Ditemukan " . count($filtered) . " jurusan resmi untuk kata kunci \"{$query}\":\n\n"
            . json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * [CORE-LOGIC: TOOL-JSON-SCHEMA]
     * Menyediakan JSON Schema parameter untuk function calling Gemini.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'nama' => $schema->string()
                ->description('Nama, kata kunci, atau singkatan jurusan yang ingin dicari (contoh: "TKJ", "PPLG", "RPL", "Akuntansi", "Mesin", "Otomotif", "Kuliner", "DKV", "Logistik"). Kosongkan jika ingin melihat semua jurusan.')
                ->nullable(),
        ];
    }
}