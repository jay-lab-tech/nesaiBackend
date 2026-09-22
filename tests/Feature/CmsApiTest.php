<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CmsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic school and admin user
        School::create([
            'name' => 'SMK Negeri 1 Subang',
            'npsn' => '20233680',
            'principal_name' => 'Deden Saiman, S.Pd., M.Eng.',
            'student_count' => 1500,
            'staff_count' => 80,
            'classroom_count' => 36,
        ]);

        User::create([
            'name' => 'Administrator CMS',
            'email' => 'admin@smkn1subang.sch.id',
            'password' => Hash::make('admin12345'),
        ]);
    }

    public function test_public_school_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/school');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'SMK Negeri 1 Subang',
                ],
            ]);
    }

    public function test_public_majors_endpoint_returns_list(): void
    {
        Major::create([
            'name' => 'Rekayasa Perangkat Lunak',
            'slug' => 'rpl',
            'summary' => 'Belajar coding dan software.',
        ]);

        $response = $this->getJson('/api/v1/majors');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment(['slug' => 'rpl']);
    }

    public function test_login_validation_and_authentication(): void
    {
        // Invalid credentials
        $failResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@smkn1subang.sch.id',
            'password' => 'wrongpassword',
        ]);
        $failResponse->assertStatus(401)
            ->assertJson(['success' => false]);

        // Valid credentials
        $successResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@smkn1subang.sch.id',
            'password' => 'admin12345',
        ]);
        $successResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'admin@smkn1subang.sch.id',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => ['token', 'user'],
            ]);
    }

    public function test_unauthenticated_admin_routes_return_401(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard/metrics');
        $response->assertStatus(401);
    }

    public function test_authenticated_admin_flow(): void
    {
        $user = User::where('email', 'admin@smkn1subang.sch.id')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $headers = ['Authorization' => 'Bearer ' . $token];

        // 1. Check me endpoint
        $meResponse = $this->getJson('/api/v1/admin/auth/me', $headers);
        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'admin@smkn1subang.sch.id',
                ],
            ]);

        // 2. Check dashboard metrics
        $metricsResponse = $this->getJson('/api/v1/admin/dashboard/metrics', $headers);
        $metricsResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_students',
                    'total_staff',
                    'total_majors',
                    'total_news',
                ],
            ]);

        // 3. Create News
        $newsResponse = $this->postJson('/api/v1/admin/news', [
            'title' => 'Peringatan Hari Guru Nasional',
            'excerpt' => 'Kemeriahan hari guru di kampus SMKN 1 Subang.',
            'body' => 'Lengkapnya isi acara berlangsung khidmat...',
            'published_at' => now()->toDateTimeString(),
        ], $headers);

        $newsResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Peringatan Hari Guru Nasional',
                ],
            ]);

        // 4. Create Major with nested subjects
        $majorResponse = $this->postJson('/api/v1/admin/majors', [
            'name' => 'Teknik Komputer dan Jaringan',
            'slug' => 'tkj',
            'summary' => 'Jaringan komputer dan server.',
            'subjects' => [
                ['name' => 'Administrasi Server', 'description' => 'Linux & Cloud'],
            ],
            'careers' => [
                ['name' => 'Network Engineer', 'description' => 'Mengelola infrastruktur jaringan'],
            ],
        ], $headers);

        $majorResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Teknik Komputer dan Jaringan',
                ],
            ]);

        // 5. Logout
        $logoutResponse = $this->postJson('/api/v1/admin/auth/logout', [], $headers);
        $logoutResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_public_and_admin_modules_crud_flow(): void
    {
        $user = User::where('email', 'admin@smkn1subang.sch.id')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        // 1. Facility CRUD
        $facResponse = $this->postJson('/api/v1/admin/facilities', [
            'name' => 'Lab Komputer RPL',
            'category' => 'Laboratorium',
        ], $headers);
        $facResponse->assertStatus(201);
        $facilityId = $facResponse->json('data.id');

        $this->getJson('/api/v1/facilities')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Lab Komputer RPL']);

        $this->putJson("/api/v1/admin/facilities/{$facilityId}", [
            'name' => 'Lab Komputer RPL Modern',
            'category' => 'Laboratorium',
        ], $headers)->assertStatus(200);

        // 2. Extracurricular CRUD
        $exResponse = $this->postJson('/api/v1/admin/extracurriculars', [
            'name' => 'Paskibra',
            'category' => 'Kepemimpinan',
        ], $headers);
        $exResponse->assertStatus(201);

        $this->getJson('/api/v1/extracurriculars')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Paskibra']);

        // 3. FAQ CRUD
        $faqResponse = $this->postJson('/api/v1/admin/faqs', [
            'question' => 'Kapan PPDB dibuka?',
            'answer' => 'PPDB dibuka mulai bulan Mei.',
            'sort_order' => 1,
        ], $headers);
        $faqResponse->assertStatus(201);

        $this->getJson('/api/v1/faqs')
            ->assertStatus(200)
            ->assertJsonFragment(['question' => 'Kapan PPDB dibuka?']);

        // 4. PPDB Update and Public Show
        $this->putJson('/api/v1/admin/ppdb', [
            'title' => 'PPDB Tahun 2026/2027',
            'description' => 'Pendaftaran peserta didik baru.',
            'requirements' => ['Ijazah SMP', 'Kartu Keluarga'],
            'schedule' => [
                ['stage' => 'Tahap 1', 'date' => 'Juni 2026', 'desc' => 'Jalur Afirmasi'],
            ],
            'is_active' => true,
        ], $headers)->assertStatus(200);

        $this->getJson('/api/v1/ppdb')
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'PPDB Tahun 2026/2027']);

        // 5. School Profile Admin Update
        $this->putJson('/api/v1/admin/school', [
            'name' => 'SMK Negeri 1 Subang Unggul',
            'npsn' => '20233680',
            'address' => 'Jl. Arif Rahman Hakim No. 35 Subang',
            'student_count' => 1600,
        ], $headers)->assertStatus(200);

        $this->getJson('/api/v1/school')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'SMK Negeri 1 Subang Unggul']);

        // 6. Content CRUD
        $contentResponse = $this->postJson('/api/v1/admin/contents', [
            'title' => 'Sejarah Sekolah',
            'type' => 'page',
            'module' => 'about',
            'body' => 'Didirikan pada tahun 1968...',
            'is_published' => true,
        ], $headers);
        $contentResponse->assertStatus(201);
        $contentSlug = $contentResponse->json('data.slug');

        $this->getJson("/api/v1/contents/{$contentSlug}")
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Sejarah Sekolah']);
    }
}

