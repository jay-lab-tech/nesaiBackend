<?php

namespace Tests\Unit;

use App\Ai\Support\JurusanScorer;
use App\Models\Career;
use App\Models\Major;
use App\Models\MajorSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurusanScorerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $major = Major::create([
            'name' => 'Akuntansi dan Keuangan Lembaga',
            'slug' => 'akuntansi-dan-keuangan-lembaga',
            'summary' => 'Pembukuan dan administrasi keuangan.',
            'description' => 'Belajar akuntansi dasar.',
        ]);

        MajorSubject::create(['major_id' => $major->id, 'name' => 'Akuntansi Dasar']);
        Career::create(['major_id' => $major->id, 'name' => 'Staf Akuntansi']);
    }

    public function test_short_interest_does_not_match_partial_words(): void
    {
        $scorer = new JurusanScorer;

        // "ak" bukan kata utuh di data manapun → tidak boleh menghasilkan rekomendasi.
        $this->assertSame([], $scorer->score(['ak'], 3));
    }

    public function test_word_boundary_interest_matches_correct_major(): void
    {
        $scorer = new JurusanScorer;

        $result = $scorer->score(['akuntansi'], 3);

        $this->assertNotEmpty($result);
        $this->assertSame('akuntansi-dan-keuangan-lembaga', $result[0]['slug']);
        $this->assertGreaterThan(0, $result[0]['skor_kecocokan']);
    }
}
