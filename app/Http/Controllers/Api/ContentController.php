<?php

namespace App\Http\Controllers\Api;

use App\Models\Alumni;
use App\Models\Faq;
use App\Models\News;
use App\Models\Ppdb;
use Illuminate\Http\JsonResponse;

class ContentController
{
    public function news(): JsonResponse
    {
        return response()->json(['data' => News::query()->latest('published_at')->paginate(), 'meta' => (object) [], 'message' => null]);
    }
    public function newsShow(string $slug): JsonResponse
    {
        return response()->json(['data' => News::query()->where('slug', $slug)->firstOrFail(), 'meta' => (object) [], 'message' => null]);
    }
    public function ppdb(): JsonResponse
    {
        return response()->json(['data' => Ppdb::query()->where('is_active', true)->first(), 'meta' => (object) [], 'message' => null]);
    }
    public function alumni(): JsonResponse
    {
        return response()->json(['data' => Alumni::query()->with('major')->paginate(), 'meta' => (object) [], 'message' => null]);
    }
    public function faqs(): JsonResponse
    {
        return response()->json(['data' => Faq::query()->orderBy('sort_order')->get(), 'meta' => (object) [], 'message' => null]);
    }
}
