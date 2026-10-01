<?php

namespace Tests\Unit;

use App\Services\Nesai\IntentService;
use PHPUnit\Framework\TestCase;

class IntentServiceTest extends TestCase
{
    public function test_detects_ppdb_intent(): void
    {
        $service = new IntentService;

        $this->assertSame('ppdb_information', $service->detect('Kapan pendaftaran PPDB dibuka?'));
    }

    public function test_detects_major_recommendation_intent(): void
    {
        $service = new IntentService;

        $this->assertSame('major_recommendation', $service->detect('Saya bingung pilih jurusan, minat saya coding.'));
    }

    public function test_detects_navigation_intent(): void
    {
        $service = new IntentService;

        $this->assertSame('school_navigation', $service->detect('Berikan saya link halaman jurusan.'));
    }

    public function test_defaults_to_school_information(): void
    {
        $service = new IntentService;

        $this->assertSame('school_information', $service->detect('Apa visi misi sekolah?'));
    }
}
