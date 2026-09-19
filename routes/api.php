<?php
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\NesaiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

// [CORE-LOGIC: CHATBOT-ENDPOINT-DIRECT]
// Endpoint standar untuk chatbot AI NESAI yang diakses oleh frontend Next.js (POST /api/chat)
Route::post('chat', NesaiController::class);

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class);
    Route::get('school', SchoolController::class);
    Route::get('majors', [MajorController::class, 'index']);
    Route::get('majors/{slug}', [MajorController::class, 'show']);
    Route::get('news', [ContentController::class, 'news']);
    Route::get('news/{slug}', [ContentController::class, 'newsShow']);
    Route::get('ppdb', [ContentController::class, 'ppdb']);
    Route::get('alumni', [ContentController::class, 'alumni']);
    Route::get('search', SearchController::class);
    Route::post('recommendations/majors', RecommendationController::class);

    // [CORE-LOGIC: CHATBOT-ENDPOINT-V1-ALIAS]
    // Alias endpoint versi v1 untuk backward compatibility
    Route::post('nesai/chat', NesaiController::class);
});

