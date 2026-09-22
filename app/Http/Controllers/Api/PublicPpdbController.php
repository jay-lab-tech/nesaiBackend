<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ppdb;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublicPpdbController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        $ppdb = Ppdb::where('is_active', true)->first() ?? Ppdb::first();

        return $this->successResponse($ppdb, 'Informasi PPDB berhasil diambil.');
    }
}
