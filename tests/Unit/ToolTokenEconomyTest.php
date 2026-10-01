<?php

namespace Tests\Unit;

use App\Ai\Tools\GetJurusanInfoTool;
use App\Ai\Tools\GetSchoolInfoTool;
use Database\Seeders\SchoolPublicDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ToolTokenEconomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SchoolPublicDataSeeder::class);
    }

    public function test_jurusan_summary_is_shorter_than_full_detail(): void
    {
        $tool = new GetJurusanInfoTool;

        $summary = (string) $tool->handle(new Request([]));
        $detail = (string) $tool->handle(new Request(['nama' => 'PPLG']));

        // Ringkasan daftar jurusan (nama + slug) harus jauh lebih pendek dari dump detail.
        $this->assertLessThan(4000, strlen($summary));
        $this->assertStringContainsString('pengembangan-perangkat-lunak-dan-gim', $summary);

        // Detail spesifik tetap memuat data lengkap.
        $this->assertStringContainsString('prospek_karir', $detail);
        $this->assertStringContainsString('mata_pelajaran_utama', $detail);
    }

    public function test_school_summary_is_shorter_than_full_dump(): void
    {
        $tool = new GetSchoolInfoTool;

        $summary = (string) $tool->handle(new Request([]));
        $visiMisi = (string) $tool->handle(new Request(['section' => 'visi_misi']));

        $this->assertLessThan(4000, strlen($summary));
        $this->assertStringContainsString('Ringkasan profil', $summary);
        // Ringkasan tidak meniup prompt dengan bidang panjang seperti visi.
        $this->assertStringNotContainsString('Berkarakter Agamis', $summary);

        // Section spesifik tetap memuat data lengkap.
        $this->assertStringContainsString('Berkarakter Agamis', $visiMisi);
    }
}
