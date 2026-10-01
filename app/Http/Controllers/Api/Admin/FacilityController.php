<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Facility::query()->orderBy('id', 'desc');

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        $facilities = $query->paginate($perPage);

        return $this->paginatedResponse($facilities, 'Daftar sarana prasarana berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $facility = Facility::create($validated);

        return $this->successResponse($facility, 'Sarana prasarana berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $facility = Facility::find($id);

        if (!$facility) {
            return $this->errorResponse('Sarana prasarana tidak ditemukan.', 404);
        }

        return $this->successResponse($facility, 'Detail sarana prasarana berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $facility = Facility::find($id);

        if (!$facility) {
            return $this->errorResponse('Sarana prasarana tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $facility->update($validated);

        return $this->successResponse($facility, 'Sarana prasarana berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $facility = Facility::find($id);

        if (!$facility) {
            return $this->errorResponse('Sarana prasarana tidak ditemukan.', 404);
        }

        $facility->delete();

        return $this->successResponse(null, 'Sarana prasarana berhasil dihapus.');
    }
}
