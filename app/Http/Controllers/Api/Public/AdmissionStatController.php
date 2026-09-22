<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\AdmissionStat;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdmissionStatController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $stats = AdmissionStat::with('major')
            ->orderBy('year', 'desc')
            ->get();

        return $this->successResponse($stats, 'Statistik penerimaan siswa berhasil diambil.');
    }
}
