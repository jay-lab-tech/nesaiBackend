<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SchoolController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        $school = School::first();

        return $this->successResponse($school, 'Profil sekolah berhasil diambil.');
    }
}
