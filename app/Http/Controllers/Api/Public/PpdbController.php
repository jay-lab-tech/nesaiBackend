<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Ppdb;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PpdbController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        $ppdb = Ppdb::where('is_active', true)->first();

        return $this->successResponse($ppdb, 'Pengaturan PPDB aktif berhasil diambil.');
    }
}
