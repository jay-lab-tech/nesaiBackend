<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Facility::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $facilities = $query->get();

        return $this->successResponse($facilities, 'Daftar sarana prasarana berhasil diambil.');
    }
}
