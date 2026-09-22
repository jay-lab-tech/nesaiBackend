<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAlumniController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Alumni::with('major')->orderBy('id', 'desc');

        if ($request->filled('major_id')) {
            $query->where('major_id', $request->query('major_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('headline', 'like', "%{$search}%");
            });
        }

        $alumni = $query->paginate($perPage);

        return $this->paginatedResponse($alumni, 'Daftar alumni berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'major_id' => ['nullable', 'exists:majors,id'],
            'headline' => ['nullable', 'string', 'max:255'],
            'story' => ['nullable', 'string'],
        ]);

        $alumni = Alumni::create($validated);
        $alumni->load('major');

        return $this->successResponse($alumni, 'Data alumni berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $alumni = Alumni::with('major')->find($id);

        if (!$alumni) {
            return $this->errorResponse('Data alumni tidak ditemukan.', 404);
        }

        return $this->successResponse($alumni, 'Detail data alumni berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $alumni = Alumni::find($id);

        if (!$alumni) {
            return $this->errorResponse('Data alumni tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'major_id' => ['nullable', 'exists:majors,id'],
            'headline' => ['nullable', 'string', 'max:255'],
            'story' => ['nullable', 'string'],
        ]);

        $alumni->update($validated);
        $alumni->load('major');

        return $this->successResponse($alumni, 'Data alumni berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $alumni = Alumni::find($id);

        if (!$alumni) {
            return $this->errorResponse('Data alumni tidak ditemukan.', 404);
        }

        $alumni->delete();

        return $this->successResponse(null, 'Data alumni berhasil dihapus.');
    }
}
