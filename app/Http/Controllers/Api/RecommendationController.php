<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\RecommendationRequest;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;

class RecommendationController
{
    /**
     * [CORE-LOGIC: RECOMMENDATION-CONTROLLER]
     * Menangani request rekomendasi jurusan berbasis minat siswa (Find Your Path) dari frontend Next.js.
     */
    public function __invoke(RecommendationRequest $request, RecommendationService $service): JsonResponse
    {
        $interests = $request->validated('interests', []);
        $recommendations = $service->recommend($interests);

        return response()->json([
            'data' => $recommendations,
            'meta' => [
                'total' => count($recommendations),
                'status' => 'scored',
                'source' => 'config/jurusan.php',
            ],
            'message' => empty($recommendations)
                ? 'Tidak ditemukan jurusan yang cocok dengan minat yang dipilih.'
                : 'Rekomendasi jurusan berhasil dikalkulasi berdasarkan minat yang dipilih.',
        ]);
    }
}
