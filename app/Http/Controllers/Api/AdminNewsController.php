<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminNewsController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = News::query()->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
        }

        $news = $query->paginate($perPage);

        return $this->paginatedResponse($news, 'Daftar berita berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:news,slug'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'thumbnail' => ['nullable'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        }

        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            $request->validate([
                'thumbnail' => ['image', 'max:2048'],
            ]);
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $thumbnailUrl = url('storage/' . $path);
        } elseif (is_string($request->input('thumbnail'))) {
            $thumbnailUrl = $request->input('thumbnail');
        }

        $news = News::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
            'thumbnail' => $thumbnailUrl,
            'published_at' => $validated['published_at'] ?? null,
        ]);

        return $this->successResponse($news, 'Berita berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $news = News::find($id);

        if (!$news) {
            return $this->errorResponse('Berita tidak ditemukan.', 404);
        }

        return $this->successResponse($news, 'Detail berita berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $news = News::find($id);

        if (!$news) {
            return $this->errorResponse('Berita tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', "unique:news,slug,{$id}"],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'thumbnail' => ['nullable'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('thumbnail')) {
            $request->validate([
                'thumbnail' => ['image', 'max:2048'],
            ]);
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $validated['thumbnail'] = url('storage/' . $path);
        } elseif ($request->filled('thumbnail') && is_string($request->input('thumbnail'))) {
            $validated['thumbnail'] = $request->input('thumbnail');
        } else {
            unset($validated['thumbnail']);
        }

        $news->update($validated);

        return $this->successResponse($news, 'Berita berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $news = News::find($id);

        if (!$news) {
            return $this->errorResponse('Berita tidak ditemukan.', 404);
        }

        $news->delete();

        return $this->successResponse(null, 'Berita berhasil dihapus.');
    }
}
