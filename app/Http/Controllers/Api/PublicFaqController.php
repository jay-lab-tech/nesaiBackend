<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublicFaqController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $faqs = Faq::orderBy('sort_order', 'asc')->get();

        return $this->successResponse($faqs, 'Daftar FAQ berhasil diambil.');
    }
}
