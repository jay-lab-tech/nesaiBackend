<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->paginate($this->boundedPerPage($request));

        return $this->paginatedResponse($faqs, 'Daftar FAQ berhasil diambil.');
    }
}
