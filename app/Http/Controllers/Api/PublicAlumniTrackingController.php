<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlumniTrackingStat;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublicAlumniTrackingController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $stats = AlumniTrackingStat::orderBy('year', 'desc')->get();

        return $this->successResponse($stats, 'Statistik penelusuran alumni berhasil diambil.');
    }
}
