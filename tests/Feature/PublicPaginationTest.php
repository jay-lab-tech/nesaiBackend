<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lists_return_pagination_metadata_and_respect_the_maximum_page_size(): void
    {
        for ($index = 1; $index <= 105; $index++) {
            News::query()->create([
                'title' => "News {$index}",
                'slug' => "news-{$index}",
                'published_at' => now(),
            ]);
        }

        $this->getJson('/api/v1/news?per_page=10000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(100, 'data');

        $publicLists = [
            '/api/v1/majors',
            '/api/v1/facilities',
            '/api/v1/extracurriculars',
            '/api/v1/innovations',
            '/api/v1/admission-stats',
            '/api/v1/alumni-tracking-stats',
            '/api/v1/alumni',
            '/api/v1/news',
            '/api/v1/faqs',
            '/api/v1/contents',
        ];

        foreach ($publicLists as $endpoint) {
            $this->getJson($endpoint.'?per_page=10000')
                ->assertOk()
                ->assertJsonPath('meta.per_page', 100);
        }
    }

    public function test_invalid_per_page_values_fall_back_to_the_default(): void
    {
        $this->getJson('/api/v1/news?per_page[]=1000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15);
    }
}
