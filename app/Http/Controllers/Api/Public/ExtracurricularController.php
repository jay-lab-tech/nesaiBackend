<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtracurricularController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Extracurricular::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $extracurriculars = $query->get();

        return $this->successResponse($extracurriculars, 'Daftar ekstrakurikuler berhasil diambil.');
    }
}
