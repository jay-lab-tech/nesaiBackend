<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Innovation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InnovationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Innovation::with('major')->orderBy('id', 'desc');

        if ($request->filled('major_id')) {
            $query->where('major_id', $request->query('major_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        $innovations = $query->paginate($perPage);

        return $this->paginatedResponse($innovations, 'Daftar karya inovasi berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'major_id' => ['nullable', 'exists:majors,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'has_haki' => ['boolean'],
        ]);

        $innovation = Innovation::create($validated);
        $innovation->load('major');

        return $this->successResponse($innovation, 'Karya inovasi berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $innovation = Innovation::with('major')->find($id);

        if (!$innovation) {
            return $this->errorResponse('Karya inovasi tidak ditemukan.', 404);
        }

        return $this->successResponse($innovation, 'Detail karya inovasi berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $innovation = Innovation::find($id);

        if (!$innovation) {
            return $this->errorResponse('Karya inovasi tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'major_id' => ['nullable', 'exists:majors,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'has_haki' => ['boolean'],
        ]);

        $innovation->update($validated);
        $innovation->load('major');

        return $this->successResponse($innovation, 'Karya inovasi berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $innovation = Innovation::find($id);

        if (!$innovation) {
            return $this->errorResponse('Karya inovasi tidak ditemukan.', 404);
        }

        $innovation->delete();

        return $this->successResponse(null, 'Karya inovasi berhasil dihapus.');
    }
}
