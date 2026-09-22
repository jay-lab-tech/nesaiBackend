<?php

use App\Http\Controllers\Api\AdminAdmissionStatController;
use App\Http\Controllers\Api\AdminAlumniController;
use App\Http\Controllers\Api\AdminAlumniTrackingController;
use App\Http\Controllers\Api\AdminContentController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminExtracurricularController;
use App\Http\Controllers\Api\AdminFacilityController;
use App\Http\Controllers\Api\AdminFaqController;
use App\Http\Controllers\Api\AdminInnovationController;
use App\Http\Controllers\Api\AdminMajorController;
use App\Http\Controllers\Api\AdminNewsController;
use App\Http\Controllers\Api\AdminPpdbController;
use App\Http\Controllers\Api\AdminSchoolController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\NesaiController;
use App\Http\Controllers\Api\PublicAdmissionStatController;
use App\Http\Controllers\Api\PublicAlumniController;
use App\Http\Controllers\Api\PublicAlumniTrackingController;
use App\Http\Controllers\Api\PublicContentController;
use App\Http\Controllers\Api\PublicExtracurricularController;
use App\Http\Controllers\Api\PublicFacilityController;
use App\Http\Controllers\Api\PublicFaqController;
use App\Http\Controllers\Api\PublicInnovationController;
use App\Http\Controllers\Api\PublicMajorController;
use App\Http\Controllers\Api\PublicNewsController;
use App\Http\Controllers\Api\PublicPpdbController;
use App\Http\Controllers\Api\PublicSchoolController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

// [CORE-LOGIC: CHATBOT-ENDPOINT-DIRECT]
// Endpoint standar untuk chatbot AI NESAI yang diakses oleh frontend Next.js (POST /api/chat)
Route::post('chat', NesaiController::class);

Route::prefix('v1')->group(function (): void {

    // ==========================================
    // 1. AUTHENTICATION ENDPOINTS
    // ==========================================
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ==========================================
    // 2. PUBLIC ENDPOINTS (Web Sekolah & Chatbot)
    // Akses publik (read-only tanpa Bearer token)
    // ==========================================
    Route::get('/school', [PublicSchoolController::class, 'show']);
    Route::get('/majors', [PublicMajorController::class, 'index']);
    Route::get('/majors/{slug}', [PublicMajorController::class, 'show']);
    Route::get('/facilities', [PublicFacilityController::class, 'index']);
    Route::get('/extracurriculars', [PublicExtracurricularController::class, 'index']);
    Route::get('/innovations', [PublicInnovationController::class, 'index']);
    Route::get('/admission-stats', [PublicAdmissionStatController::class, 'index']);
    Route::get('/alumni-tracking-stats', [PublicAlumniTrackingController::class, 'index']);
    Route::get('/alumni', [PublicAlumniController::class, 'index']);
    Route::get('/news', [PublicNewsController::class, 'index']);
    Route::get('/news/{slug}', [PublicNewsController::class, 'show']);
    Route::get('/ppdb', [PublicPpdbController::class, 'show']);
    Route::get('/faqs', [PublicFaqController::class, 'index']);
    Route::get('/contents', [PublicContentController::class, 'index']);
    Route::get('/contents/{slug}', [PublicContentController::class, 'show']);

    // Utility & AI Services
    Route::get('/health', HealthController::class);
    Route::get('/search', SearchController::class);
    Route::post('/recommendations/majors', RecommendationController::class);

    // [CORE-LOGIC: CHATBOT-ENDPOINT-V1-ALIAS]
    Route::post('/nesai/chat', NesaiController::class);

    // ==========================================
    // 3. ADMIN CMS ENDPOINTS (Wajib auth:sanctum)
    // ==========================================
    Route::middleware('auth:sanctum')->prefix('admin')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard Stats
        Route::get('/dashboard/metrics', [AdminDashboardController::class, 'metrics']);

        // Profil Sekolah (Single Instance)
        Route::get('/school', [AdminSchoolController::class, 'show']);
        Route::put('/school', [AdminSchoolController::class, 'update']);

        // CRUD Modul
        Route::apiResource('majors', AdminMajorController::class);
        Route::apiResource('facilities', AdminFacilityController::class);
        Route::apiResource('extracurriculars', AdminExtracurricularController::class);
        Route::apiResource('innovations', AdminInnovationController::class);
        Route::apiResource('admission-stats', AdminAdmissionStatController::class);
        Route::apiResource('alumni-tracking-stats', AdminAlumniTrackingController::class);
        Route::apiResource('alumni', AdminAlumniController::class);
        Route::apiResource('news', AdminNewsController::class);
        Route::apiResource('faqs', AdminFaqController::class);
        Route::apiResource('contents', AdminContentController::class);

        // PPDB Settings (Get & Update)
        Route::get('/ppdb', [AdminPpdbController::class, 'show']);
        Route::put('/ppdb', [AdminPpdbController::class, 'update']);
    });
});
