<?php

namespace App\Http\Controllers\Api\Public;

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
        $stats = AlumniTrackingStat::orderByDesc('year')->orderBy('id')->paginate($this->boundedPerPage($request));

        return $this->paginatedResponse($stats, 'Statistik penelusuran alumni berhasil diambil.');
    }
}
