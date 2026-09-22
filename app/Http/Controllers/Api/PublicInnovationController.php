<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Innovation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublicInnovationController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $innovations = Innovation::with('major')->orderBy('id', 'desc')->get();

        return $this->successResponse($innovations, 'Daftar karya inovasi berhasil diambil.');
    }
}
