<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Content::query()->orderBy('id', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->query('module'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $contents = $query->paginate($perPage);

        return $this->paginatedResponse($contents, 'Daftar konten berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:contents,slug'],
            'type' => ['required', 'string', 'max:50'],
            'module' => ['nullable', 'string', 'max:50'],
            'body' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        }

        $content = Content::create($validated);

        return $this->successResponse($content, 'Konten berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Konten tidak ditemukan.', 404);
        }

        return $this->successResponse($content, 'Detail konten berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Konten tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', "unique:contents,slug,{$id}"],
            'type' => ['required', 'string', 'max:50'],
            'module' => ['nullable', 'string', 'max:50'],
            'body' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $content->update($validated);

        return $this->successResponse($content, 'Konten berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $content = Content::find($id);

        if (!$content) {
            return $this->errorResponse('Konten tidak ditemukan.', 404);
        }

        $content->delete();

        return $this->successResponse(null, 'Konten berhasil dihapus.');
    }
}
