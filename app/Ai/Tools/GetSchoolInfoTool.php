<?php

namespace App\Ai\Tools;

use App\Models\Extracurricular;
use App\Models\Facility;
use App\Models\Innovation;
use App\Models\News;
use App\Models\School;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetSchoolInfoTool implements Tool
{
    /**
     * [CORE-LOGIC: SECTION-DICTIONARY]
     * Kamus pemetaan sinonim/alias dari input percakapan ke key data profil sekolah.
     * Mencegah LLM gagal mengambil bagian profil sekolah akibat variasi istilah pengguna.
     *
     * @var array<string, list<string>>
     */
    protected array $sectionAliases = [
        'identitas' => ['nama_resmi', 'nama_pendek', 'npsn', 'status', 'akreditasi', 'jenjang', 'tahun_berdiri'],
        'profil' => ['nama_resmi', 'nama_pendek', 'npsn', 'status', 'akreditasi', 'jenjang', 'tahun_berdiri', 'deskripsi'],
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
        'sejarah' => ['sejarah', 'tahun_berdiri'],
        'program_unggulan' => ['program_unggulan'],
        'unggulan' => ['program_unggulan'],
        'jurusan' => ['jumlah_jurusan', 'rumpun_keahlian'],
        'rumpun' => ['jumlah_jurusan', 'rumpun_keahlian'],
        'siswa' => ['jumlah_siswa', 'jumlah_guru_staf', 'jumlah_ruang_kelas'],
        'guru' => ['jumlah_guru_staf', 'kepala_sekolah'],
        'statistik' => ['jumlah_siswa', 'jumlah_guru_staf', 'jumlah_ruang_kelas', 'luas_lahan'],
        'fasilitas' => ['jumlah_fasilitas', 'ringkasan_fasilitas'],
        'sarana' => ['jumlah_fasilitas', 'ringkasan_fasilitas'],
        'ekskul' => ['ekstrakurikuler'],
        'ekstrakurikuler' => ['ekstrakurikuler'],
        'inovasi' => ['karya_inovasi'],
        'karya' => ['karya_inovasi'],
        'karya_siswa' => ['karya_inovasi'],
        'karya_inovasi' => ['karya_inovasi'],
        'haki' => ['karya_inovasi'],
        'produk' => ['karya_inovasi'],
        'prestasi' => ['berita_prestasi', 'program_unggulan'],
        'berita' => ['berita_prestasi'],
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
        return 'Mengambil informasi profil resmi SMKN 1 Subang langsung dari database: identitas sekolah (nama, NPSN, akreditasi), kepala sekolah, jumlah siswa/guru/kelas, alamat & lokasi peta, kontak (telepon, email, website), media sosial, visi-misi, sejarah, sarana prasarana/fasilitas, ekstrakurikuler, karya inovasi siswa/produk HAKI (seperti Motocimic, Nesasserator, Siborin), serta program unggulan. Untuk berita dan catatan prestasi lomba lengkap, gunakan tool get_news_info.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: DATABASE-FIRST-SOURCE]
        // Mengambil data profil sekolah resmi dari database (School model) dengan caching layer
        // untuk response time ultra cepat (<10ms) dan deterministik.
        $schoolData = Cache::remember('nesai:school_profile_v4', 3600, function () {
            $school = School::first();

            $facilityCount = Facility::count();
            $facilities = Facility::select('name', 'category', 'quantity')->take(15)->get()->toArray();
            $extracurriculars = Extracurricular::pluck('name')->toArray();

            $innovations = Innovation::with('major')->get()->map(function ($item) {
                return [
                    'nama' => $item->name,
                    'jurusan' => $item->major?->name ?? 'Umum',
                    'deskripsi' => $item->description,
                    'terdaftar_haki' => (bool) $item->has_haki,
                ];
            })->toArray();

            $recentNews = News::whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderBy('published_at', 'desc')
                ->take(5)
                ->get(['title', 'slug', 'excerpt', 'published_at'])
                ->map(function ($news) {
                    return [
                        'judul' => $news->title,
                        'slug' => $news->slug,
                        'ringkasan' => $news->excerpt,
                        'tanggal' => $news->published_at?->format('d F Y') ?? '-',
                    ];
                })->toArray();

            $fallbackConfig = config('school', []);

            if (empty($innovations) && ! empty($fallbackConfig['karya_inovasi'])) {
                $innovations = $fallbackConfig['karya_inovasi'];
            }

            if (! $school) {
                $profile = $fallbackConfig;
                $profile['karya_inovasi'] = $innovations;
                $profile['berita_prestasi'] = $recentNews;
                return $profile;
            }

            $classroomCount = $school->classroom_count_min && $school->classroom_count_max
                ? "{$school->classroom_count_min} - {$school->classroom_count_max} ruang kelas"
                : ($school->classroom_count ? "{$school->classroom_count} ruang kelas" : '50 - 52 ruang kelas');

            return [
                'nama_resmi' => $school->name,
                'nama_pendek' => 'SMKN 1 Subang (NESAS)',
                'npsn' => $school->npsn ?? '20233680',
                'status' => 'Negeri',
                'akreditasi' => $school->accreditation ?? 'A',
                'jenjang' => 'SMK (Sekolah Menengah Kejuruan)',
                'tahun_berdiri' => $school->founded_year ?? 1965,
                'luas_lahan' => $school->area_size ?? '18.882 m2',
                'kepala_sekolah' => $school->principal_name ?? 'Walyati Retnoningsih, S.Si., M.AP',
                'jumlah_guru_staf' => $school->staff_count ?? 159,
                'jumlah_siswa' => $school->student_count ?? 2589,
                'jumlah_ruang_kelas' => $classroomCount,
                'deskripsi' => $school->description ?? ($fallbackConfig['deskripsi'] ?? ''),
                'visi' => $school->vision ?? ($fallbackConfig['visi'] ?? ''),
                'misi' => is_array($school->mission)
                    ? $school->mission
                    : array_values(array_filter(array_map('trim', explode("\n", $school->mission ?? '')))),
                'alamat' => $school->address ?? ($fallbackConfig['alamat']['teks'] ?? ''),
                'koordinat' => $fallbackConfig['koordinat'] ?? ['lat' => -6.5714, 'lng' => 107.7587],
                'kontak' => [
                    'telepon' => $school->phone ?? '0260-411410',
                    'email' => $school->email ?? 'info@smkn1subang.sch.id',
                    'website' => 'https://smkn1subang.sch.id',
                ],
                'media_sosial' => $school->social_links ?? ($fallbackConfig['media_sosial'] ?? []),
                'program_unggulan' => 'BerAKSI (Berkarakter, Adaptif, Kompeten, Sinergis, Inovatif) & BLUD 2029',
                'jumlah_jurusan' => 10,
                'jumlah_fasilitas' => $facilityCount,
                'ringkasan_fasilitas' => $facilities,
                'ekstrakurikuler' => $extracurriculars,
                'karya_inovasi' => $innovations,
                'berita_prestasi' => $recentNews,
                'sejarah' => $fallbackConfig['sejarah'] ?? 'Didirikan pada tahun 1965 sebagai salah satu SMK perintis di Kabupaten Subang.',
            ];
        });

        if (empty($schoolData)) {
            return 'Data profil sekolah belum tersedia saat ini di database sekolah.';
        }

        $section = strtolower(trim($request->string('section')->toString()));

        // Jika tidak ada section spesifik, kembalikan seluruh profil sekolah
        if ($section === '') {
            return "Profil resmi SMKN 1 Subang (Sumber: Basis Data Resmi):\n\n"
                . json_encode($schoolData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Cari section yang diminta melalui alias dictionary
        $keys = $this->sectionAliases[$section] ?? null;

        if ($keys === null) {
            // Jika alias tidak ditemukan, coba langsung akses key di array
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
            return "Data untuk section \"{$section}\" tidak ditemukan di basis data sekolah.";
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
                ->description('Bagian informasi profil sekolah yang ingin diambil (contoh: "identitas", "kepala_sekolah", "siswa", "guru", "statistik", "alamat", "kontak", "visi_misi", "fasilitas", "ekskul", "inovasi", "karya", "haki", "sejarah", "program_unggulan", "sosmed"). Kosongkan untuk mendapatkan seluruh profil sekolah.')
                ->nullable(),
        ];
    }
}