<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlumniTrackingStat;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlumniTrackingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = AlumniTrackingStat::query()->orderBy('year', 'desc');

        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        $stats = $query->paginate($perPage);

        return $this->paginatedResponse($stats, 'Statistik penelusuran alumni berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'unique:alumni_tracking_stats,year'],
            'employed_percent' => ['nullable', 'numeric', 'between:0,100'],
            'entrepreneur_percent' => ['nullable', 'numeric', 'between:0,100'],
            'college_percent' => ['nullable', 'numeric', 'between:0,100'],
            'other_percent' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $stat = AlumniTrackingStat::create($validated);

        return $this->successResponse($stat, 'Statistik alumni berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $stat = AlumniTrackingStat::find($id);

        if (!$stat) {
            return $this->errorResponse('Statistik alumni tidak ditemukan.', 404);
        }

        return $this->successResponse($stat, 'Detail statistik alumni berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $stat = AlumniTrackingStat::find($id);

        if (!$stat) {
            return $this->errorResponse('Statistik alumni tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'year' => ['required', 'integer', "unique:alumni_tracking_stats,year,{$id}"],
            'employed_percent' => ['nullable', 'numeric', 'between:0,100'],
            'entrepreneur_percent' => ['nullable', 'numeric', 'between:0,100'],
            'college_percent' => ['nullable', 'numeric', 'between:0,100'],
            'other_percent' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $stat->update($validated);

        return $this->successResponse($stat, 'Statistik alumni berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $stat = AlumniTrackingStat::find($id);

        if (!$stat) {
            return $this->errorResponse('Statistik alumni tidak ditemukan.', 404);
        }

        $stat->delete();

        return $this->successResponse(null, 'Statistik alumni berhasil dihapus.');
    }
}
