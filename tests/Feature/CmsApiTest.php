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
}
