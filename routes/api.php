<?php

<<<<<<< HEAD
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\HealthController;
=======
use App\Http\Controllers\Api\AdmissionStatController;
use App\Http\Controllers\Api\AlumniTrackingStatController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ExtracurricularController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InnovationController;
use App\Http\Controllers\Api\MajorController;
>>>>>>> f0c3f95fad4eaa616fb5de39af07b4ddeaf966a1
use App\Http\Controllers\Api\NesaiController;
use App\Http\Controllers\Api\Public;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

// Direct endpoint for clients that use the short /api/chat path.
Route::post('chat', NesaiController::class);

Route::prefix('v1')->group(function (): void {
<<<<<<< HEAD

    // ==========================================
    // 1. AUTHENTICATION ENDPOINTS
    // ==========================================
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ==========================================
    // 2. PUBLIC ENDPOINTS (Web Sekolah & Chatbot)
    // Akses publik (read-only tanpa Bearer token)
    // ==========================================
    Route::get('/school', [Public\SchoolController::class, 'show']);
    Route::get('/majors', [Public\MajorController::class, 'index']);
    Route::get('/majors/{slug}', [Public\MajorController::class, 'show']);
    Route::get('/facilities', [Public\FacilityController::class, 'index']);
    Route::get('/extracurriculars', [Public\ExtracurricularController::class, 'index']);
    Route::get('/innovations', [Public\InnovationController::class, 'index']);
    Route::get('/admission-stats', [Public\AdmissionStatController::class, 'index']);
    Route::get('/alumni-tracking-stats', [Public\AlumniTrackingController::class, 'index']);
    Route::get('/alumni', [Public\AlumniController::class, 'index']);
    Route::get('/news', [Public\NewsController::class, 'index']);
    Route::get('/news/{slug}', [Public\NewsController::class, 'show']);
    Route::get('/ppdb', [Public\PpdbController::class, 'show']);
    Route::get('/faqs', [Public\FaqController::class, 'index']);
    Route::get('/contents', [Public\ContentController::class, 'index']);
    Route::get('/contents/{slug}', [Public\ContentController::class, 'show']);

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
        Route::get('/dashboard/metrics', [Admin\DashboardController::class, 'metrics']);

        // Profil Sekolah (Single Instance)
        Route::get('/school', [Admin\SchoolController::class, 'show']);
        Route::put('/school', [Admin\SchoolController::class, 'update']);

        // CRUD Modul
        Route::apiResource('majors', Admin\MajorController::class);
        Route::apiResource('facilities', Admin\FacilityController::class);
        Route::apiResource('extracurriculars', Admin\ExtracurricularController::class);
        Route::apiResource('innovations', Admin\InnovationController::class);
        Route::apiResource('admission-stats', Admin\AdmissionStatController::class);
        Route::apiResource('alumni-tracking-stats', Admin\AlumniTrackingController::class);
        Route::apiResource('alumni', Admin\AlumniController::class);
        Route::apiResource('news', Admin\NewsController::class);
        Route::apiResource('faqs', Admin\FaqController::class);
        Route::apiResource('contents', Admin\ContentController::class);

        // PPDB Settings (Get & Update)
        Route::get('/ppdb', [Admin\PpdbController::class, 'show']);
        Route::put('/ppdb', [Admin\PpdbController::class, 'update']);
    });
=======
    Route::get('health', HealthController::class);
    Route::get('school', SchoolController::class);
    Route::get('majors', [MajorController::class, 'index']);
    Route::get('majors/{slug}', [MajorController::class, 'show']);
    Route::get('news', [ContentController::class, 'news']);
    Route::get('news/{slug}', [ContentController::class, 'newsShow']);
    Route::get('ppdb', [ContentController::class, 'ppdb']);
    Route::get('alumni', [ContentController::class, 'alumni']);
    Route::get('faqs', [ContentController::class, 'faqs']);
    Route::get('facilities', FacilityController::class);
    Route::get('extracurriculars', ExtracurricularController::class);
    Route::get('innovations', InnovationController::class);
    Route::get('stats/admissions', AdmissionStatController::class);
    Route::get('stats/alumni-tracking', AlumniTrackingStatController::class);
    Route::get('search', SearchController::class);
    Route::post('recommendations/majors', RecommendationController::class);
    Route::post('nesai/chat', NesaiController::class);
>>>>>>> f0c3f95fad4eaa616fb5de39af07b4ddeaf966a1
});
