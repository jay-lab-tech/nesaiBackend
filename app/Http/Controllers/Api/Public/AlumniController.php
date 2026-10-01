<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $alumni = Alumni::with('major')->orderByDesc('id')->paginate($this->boundedPerPage($request));

        return $this->paginatedResponse($alumni, 'Daftar alumni berhasil diambil.');
    }
}
