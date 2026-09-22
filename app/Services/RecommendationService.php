<?php

namespace App\Services;

use App\Ai\Support\JurusanScorer;

/**
 * [CORE-LOGIC: RECOMMENDATION-SERVICE]
 * Service pemrosesan rekomendasi jurusan untuk endpoint POST /api/v1/recommendations/majors.
 * Menggunakan engine scoring yang sama (JurusanScorer) dengan RecommendJurusanTool agar kalkulasi konsisten.
 */
class RecommendationService
{
    public function __construct(
        protected ?JurusanScorer $scorer = null
    ) {
        $this->scorer = $scorer ?? new JurusanScorer();
    }

    /**
     * Merekomendasikan jurusan berdasarkan daftar minat siswa.
     *
     * @param  array<string>  $interests
     * @param  int  $limit
     * @return array
     */
    public function recommend(array $interests, int $limit = 3): array
    {
        return $this->scorer->score($interests, $limit);
    }
}
