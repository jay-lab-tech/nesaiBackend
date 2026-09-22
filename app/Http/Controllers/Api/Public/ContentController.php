<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Content::query()->where('is_published', true);

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->query('module'));
        }

        $contents = $query->get();

        return $this->successResponse($contents, 'Daftar konten berhasil diambil.');
    }

    public function show(string $slug): JsonResponse
    {
        $content = Content::where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (!$content) {
            return $this->errorResponse('Konten tidak ditemukan.', 404);
        }

        return $this->successResponse($content, 'Detail konten berhasil diambil.');
    }
}
