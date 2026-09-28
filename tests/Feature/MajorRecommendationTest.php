<?php

namespace Tests\Feature;

use Database\Seeders\SchoolPublicDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MajorRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_endpoint_returns_scored_majors(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);

        $response = $this->postJson('/api/v1/recommendations/majors', [
            'interests' => ['coding', 'game', 'software'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'nama',
                    'slug',
                    'skor_kecocokan',
                    'alasan',
                    'kata_kunci_cocok',
                    'prospek_karir',
                    'mata_pelajaran_utama',
                ],
            ],
            'meta' => [
                'total',
                'status',
                'source',
            ],
            'message',
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertSame('pengembangan-perangkat-lunak-dan-gim', $data[0]['slug']);
    }

    public function test_recommendation_endpoint_validates_required_interests(): void
    {
        $response = $this->postJson('/api/v1/recommendations/majors', [
            'interests' => [],
        ]);

        $response->assertStatus(422);
    }
}
