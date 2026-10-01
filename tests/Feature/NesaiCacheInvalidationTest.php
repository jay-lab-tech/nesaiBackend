<?php

namespace Tests\Feature;

use App\Ai\Support\NesaiCache;
use App\Models\Major;
use App\Models\School;
use Database\Seeders\SchoolPublicDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NesaiCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_school_flushes_chatbot_cache(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);

        Cache::put(NesaiCache::KEY_SCHOOL_PROFILE, ['cached' => true], 900);
        $this->assertNotNull(Cache::get(NesaiCache::KEY_SCHOOL_PROFILE));

        School::firstOrFail()->update(['principal_name' => 'Nama Baru, S.Pd']);

        $this->assertNull(Cache::get(NesaiCache::KEY_SCHOOL_PROFILE));
    }

    public function test_updating_major_flushes_all_dependent_caches(): void
    {
        $this->seed(SchoolPublicDataSeeder::class);

        Cache::put(NesaiCache::KEY_JURUSAN_LIST, ['x'], 900);
        Cache::put(NesaiCache::KEY_JURUSAN_SCORER, ['x'], 900);

        Major::firstOrFail()->update(['summary' => 'Ringkasan baru.']);

        $this->assertNull(Cache::get(NesaiCache::KEY_JURUSAN_LIST));
        $this->assertNull(Cache::get(NesaiCache::KEY_JURUSAN_SCORER));
    }
}
