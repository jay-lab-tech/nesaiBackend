<?php

use App\Http\Controllers\Api\AdmissionStatController;
use App\Http\Controllers\Api\AlumniTrackingStatController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ExtracurricularController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InnovationController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\NesaiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
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
});
