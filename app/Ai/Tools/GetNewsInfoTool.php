<?php

namespace App\Ai\Tools;

use App\Models\News;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetNewsInfoTool implements Tool
{
    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'get_news_info';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Mengambil informasi berita terkini, artikel, pengumuman resmi, dan catatan prestasi/kejuaraan/lomba siswa langsung dari portal berita resmi SMKN 1 Subang (database news). Gunakan tool ini saat pengguna bertanya tentang prestasi, kejuaraan, lomba, berita terbaru, artikel, kegiatan terkini, atau informasi yang dimuat di portal berita sekolah.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $search = trim($request->string('search')->toString());

        $query = News::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $newsList = $query->take(6)->get(['id', 'title', 'slug', 'excerpt', 'body', 'published_at']);

        // Jika pencarian spesifik tidak menemukan hasil, ambil berita/prestasi terbaru
        // agar agent tetap memiliki informasi aktual dari portal berita.
        if ($newsList->isEmpty() && $search !== '') {
            $newsList = News::query()
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderBy('published_at', 'desc')
                ->take(5)
                ->get(['id', 'title', 'slug', 'excerpt', 'body', 'published_at']);
        }

        if ($newsList->isEmpty()) {
            return 'Belum ada berita atau prestasi yang dipublikasikan di portal berita SMKN 1 Subang saat ini.';
        }

        $formatted = $newsList->map(function ($news) {
            $summary = $news->excerpt ?: Str::limit(strip_tags($news->body), 150);
            return [
                'judul' => $news->title,
                'slug' => $news->slug,
                'link' => '/berita/' . $news->slug,
                'ringkasan' => $summary,
                'tanggal_publikasi' => $news->published_at?->format('d F Y') ?? '-',
            ];
        })->toArray();

        return "Daftar Berita & Prestasi dari Portal Berita Resmi SMKN 1 Subang:\n\n"
            . json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Provide JSON schema for function calling.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Kata kunci pencarian berita atau prestasi di portal berita (contoh: "prestasi", "juara", "jhic", "lomba", "ppdb"). Kosongkan untuk melihat berita dan prestasi terbaru.')
                ->nullable(),
        ];
    }
}
