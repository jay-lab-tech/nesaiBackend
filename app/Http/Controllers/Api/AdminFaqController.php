<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFaqController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Faq::query()->orderBy('sort_order', 'asc');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->paginate($perPage);

        return $this->paginatedResponse($faqs, 'Daftar FAQ berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = (Faq::max('sort_order') ?? 0) + 1;
        }

        $faq = Faq::create($validated);

        return $this->successResponse($faq, 'FAQ berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return $this->errorResponse('FAQ tidak ditemukan.', 404);
        }

        return $this->successResponse($faq, 'Detail FAQ berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return $this->errorResponse('FAQ tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $faq->update($validated);

        return $this->successResponse($faq, 'FAQ berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return $this->errorResponse('FAQ tidak ditemukan.', 404);
        }

        $faq->delete();

        return $this->successResponse(null, 'FAQ berhasil dihapus.');
    }
}
