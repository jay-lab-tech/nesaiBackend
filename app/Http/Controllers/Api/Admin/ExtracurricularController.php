<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtracurricularController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Extracurricular::query()->orderBy('id', 'desc');

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        $extracurriculars = $query->paginate($perPage);

        return $this->paginatedResponse($extracurriculars, 'Daftar ekstrakurikuler berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $extracurricular = Extracurricular::create($validated);

        return $this->successResponse($extracurricular, 'Ekstrakurikuler berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $extracurricular = Extracurricular::find($id);

        if (!$extracurricular) {
            return $this->errorResponse('Ekstrakurikuler tidak ditemukan.', 404);
        }

        return $this->successResponse($extracurricular, 'Detail ekstrakurikuler berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $extracurricular = Extracurricular::find($id);

        if (!$extracurricular) {
            return $this->errorResponse('Ekstrakurikuler tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $extracurricular->update($validated);

        return $this->successResponse($extracurricular, 'Ekstrakurikuler berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $extracurricular = Extracurricular::find($id);

        if (!$extracurricular) {
            return $this->errorResponse('Ekstrakurikuler tidak ditemukan.', 404);
        }

        $extracurricular->delete();

        return $this->successResponse(null, 'Ekstrakurikuler berhasil dihapus.');
    }
}
