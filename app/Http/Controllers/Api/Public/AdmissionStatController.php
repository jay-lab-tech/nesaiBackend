<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\AdmissionStat;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionStatController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $stats = AdmissionStat::with('major')
            ->orderBy('year', 'desc')
            ->orderBy('id')
            ->paginate($this->boundedPerPage($request));

        return $this->paginatedResponse($stats, 'Statistik penerimaan siswa berhasil diambil.');
    }
}
