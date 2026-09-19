<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class NavigateToPageTool implements Tool
{
    /**
     * [CORE-LOGIC: ROUTE-DICTIONARY]
     * Kamus pemetaan rute halaman frontend Next.js SMKN 1 Subang.
     * Berfungsi memetakan sinonim percakapan ('daftar', 'pendaftaran', 'tentang') ke path URL resmi frontend ('/ppdb', '/profil').
     *
     * @var array<string, array{path: string, title: string}>
     */
    protected array $routes = [
        'home' => ['path' => '/', 'title' => 'Beranda SMKN 1 Subang'],
        'beranda' => ['path' => '/', 'title' => 'Beranda SMKN 1 Subang'],
        'profil' => ['path' => '/profil', 'title' => 'Profil & Fasilitas SMKN 1 Subang'],
        'tentang' => ['path' => '/profil', 'title' => 'Profil & Fasilitas SMKN 1 Subang'],
        'about' => ['path' => '/profil', 'title' => 'Profil & Fasilitas SMKN 1 Subang'],
        'fasilitas' => ['path' => '/profil#fasilitas', 'title' => 'Fasilitas SMKN 1 Subang'],
        'visi-misi' => ['path' => '/profil#visi-misi', 'title' => 'Visi & Misi SMKN 1 Subang'],
        'jurusan' => ['path' => '/jurusan', 'title' => 'Daftar Jurusan / Kompetensi Keahlian'],
        'majors' => ['path' => '/jurusan', 'title' => 'Daftar Jurusan / Kompetensi Keahlian'],
        'ppdb' => ['path' => '/ppdb', 'title' => 'Pendaftaran Peserta Didik Baru (PPDB)'],
        'daftar' => ['path' => '/ppdb', 'title' => 'Pendaftaran Peserta Didik Baru (PPDB)'],
        'pendaftaran' => ['path' => '/ppdb', 'title' => 'Pendaftaran Peserta Didik Baru (PPDB)'],
        'berita' => ['path' => '/berita', 'title' => 'Berita & Pengumuman Sekolah'],
        'news' => ['path' => '/berita', 'title' => 'Berita & Pengumuman Sekolah'],
        'artikel' => ['path' => '/berita', 'title' => 'Berita & Pengumuman Sekolah'],
        'alumni' => ['path' => '/alumni', 'title' => 'Informasi & Jejak Alumni'],
        'karir' => ['path' => '/alumni', 'title' => 'Informasi & Jejak Alumni'],
        'kontak' => ['path' => '/kontak', 'title' => 'Hubungi SMKN 1 Subang'],
        'contact' => ['path' => '/kontak', 'title' => 'Hubungi SMKN 1 Subang'],
        'pencarian' => ['path' => '/search', 'title' => 'Pencarian Informasi'],
        'search' => ['path' => '/search', 'title' => 'Pencarian Informasi'],
    ];

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'navigate_to_page';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Menyarankan navigasi atau mengarahkan pengguna ke halaman tertentu di website SMKN 1 Subang (misalnya: PPDB, daftar jurusan, detail jurusan tertentu, berita, profil sekolah, alumni, kontak).';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: ROUTE-NORMALIZATION-LOGIC]
        // Menangani input fleksibel dari LLM (nama rute umum, path bergaris miring, maupun penambahan slug dinamis).
        $rawPage = strtolower(trim($request->string('page')->toString()));
        $slug = trim($request->string('slug')->toString());
        $label = trim($request->string('label')->toString());

        $cleanKey = ltrim($rawPage, '/');

        // Jika rute ada di kamus pemetaan standar
        if (isset($this->routes[$cleanKey])) {
            $routeInfo = $this->routes[$cleanKey];
            $path = $routeInfo['path'];
            $title = $label !== '' ? $label : $routeInfo['title'];

            if ($slug !== '') {
                $path = rtrim($path, '/') . '/' . ltrim($slug, '/');
                if ($label === '') {
                    $title .= ' (' . strtoupper($slug) . ')';
                }
            }
        } elseif (str_starts_with($rawPage, '/')) {
            // Jika model langsung mengirimkan path URL lengkap (contoh: '/ppdb', '/jurusan/pplg')
            $path = $rawPage;
            $title = $label !== '' ? $label : 'Halaman ' . ucfirst(trim($rawPage, '/'));
        } else {
            // Fallback untuk halaman yang tidak ada di kamus
            $path = '/' . $cleanKey . ($slug !== '' ? '/' . ltrim($slug, '/') : '');
            $title = $label !== '' ? $label : 'Halaman ' . ucfirst($cleanKey);
        }

        // [CORE-LOGIC: STRUCTURED-ACTION-PAYLOAD]
        // Format payload standar yang nantinya diekstrak NesaiService menjadi properti `actions` JSON respons API
        $payload = [
            'status' => 'success',
            'action' => 'navigate',
            'path' => $path,
            'title' => $title,
            'message' => "Navigasi ke {$title} ({$path}) berhasil disiapkan. Berikan penjelasan singkat dan sarankan tautan ini kepada pengguna.",
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * [CORE-LOGIC: TOOL-JSON-SCHEMA]
     * Mendefinisikan schema argumen tool untuk model Gemini.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->string()
                ->description('Nama rute atau tujuan halaman di website SMKN 1 Subang (contoh: "ppdb", "jurusan", "berita", "profil", "alumni", "kontak", atau path langsung seperti "/ppdb").')
                ->required(),
            'slug' => $schema->string()
                ->description('Slug khusus jika ingin mengarahkan ke halaman spesifik (contoh: "pplg", "tkj", atau slug berita).')
                ->nullable(),
            'label' => $schema->string()
                ->description('Label atau judul tautan navigasi ramah pengguna (contoh: "Pendaftaran PPDB", "Detail Jurusan PPLG").')
                ->nullable(),
        ];
    }
}
