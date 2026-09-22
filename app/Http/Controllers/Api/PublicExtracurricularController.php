<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicExtracurricularController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Extracurricular::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $extracurriculars = $query->orderBy('name', 'asc')->get();

        return $this->successResponse($extracurriculars, 'Daftar ekstrakurikuler berhasil diambil.');
    }
}
