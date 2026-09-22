<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionStat;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAdmissionStatController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = AdmissionStat::with('major')->orderBy('year', 'desc');

        if ($request->filled('major_id')) {
            $query->where('major_id', $request->query('major_id'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->query('year'));
        }

        $stats = $query->paginate($perPage);

        return $this->paginatedResponse($stats, 'Statistik penerimaan siswa berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'major_id' => ['required', 'exists:majors,id'],
            'year' => [
                'required',
                'integer',
                Rule::unique('admission_stats')->where(fn ($q) => $q->where('major_id', $request->input('major_id'))),
            ],
            'applicant_count' => ['required', 'integer', 'min:0'],
        ]);

        $stat = AdmissionStat::create($validated);
        $stat->load('major');

        return $this->successResponse($stat, 'Statistik penerimaan berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $stat = AdmissionStat::with('major')->find($id);

        if (!$stat) {
            return $this->errorResponse('Statistik penerimaan tidak ditemukan.', 404);
        }

        return $this->successResponse($stat, 'Detail statistik penerimaan berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $stat = AdmissionStat::find($id);

        if (!$stat) {
            return $this->errorResponse('Statistik penerimaan tidak ditemukan.', 404);
        }

        $majorId = $request->input('major_id', $stat->major_id);

        $validated = $request->validate([
            'major_id' => ['required', 'exists:majors,id'],
            'year' => [
                'required',
                'integer',
                Rule::unique('admission_stats')
                    ->where(fn ($q) => $q->where('major_id', $majorId))
                    ->ignore($id),
            ],
            'applicant_count' => ['required', 'integer', 'min:0'],
        ]);

        $stat->update($validated);
        $stat->load('major');

        return $this->successResponse($stat, 'Statistik penerimaan berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $stat = AdmissionStat::find($id);

        if (!$stat) {
            return $this->errorResponse('Statistik penerimaan tidak ditemukan.', 404);
        }

        $stat->delete();

        return $this->successResponse(null, 'Statistik penerimaan berhasil dihapus.');
    }
}
