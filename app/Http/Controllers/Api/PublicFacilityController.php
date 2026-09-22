<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicFacilityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Facility::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $facilities = $query->orderBy('name', 'asc')->get();

        return $this->successResponse($facilities, 'Daftar sarana & prasarana berhasil diambil.');
    }
}
