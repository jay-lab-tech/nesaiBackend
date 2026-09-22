<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Major::query()->with(['subjects', 'careers']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $majors = $query->get();

        return $this->successResponse($majors, 'Daftar jurusan berhasil diambil.');
    }

    public function show(string $slug): JsonResponse
    {
        $major = Major::with(['subjects', 'careers', 'innovations', 'admissionStats', 'alumni'])
            ->where('slug', $slug)
            ->first();

        if (!$major) {
            return $this->errorResponse('Jurusan tidak ditemukan.', 404);
        }

        return $this->successResponse($major, 'Detail jurusan berhasil diambil.');
    }
}
