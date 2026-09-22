<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AlumniController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $alumni = Alumni::with('major')->orderBy('id', 'desc')->get();

        return $this->successResponse($alumni, 'Daftar alumni berhasil diambil.');
    }
}
