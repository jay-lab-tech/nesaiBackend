<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Innovation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InnovationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $innovations = Innovation::with('major')->orderByDesc('id')->paginate($this->boundedPerPage($request));

        return $this->paginatedResponse($innovations, 'Daftar karya inovasi berhasil diambil.');
    }
}
