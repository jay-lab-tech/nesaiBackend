<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Extracurricular;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\Innovation;
use App\Models\Major;
use App\Models\News;
use App\Models\Ppdb;
use App\Models\School;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function metrics(): JsonResponse
    {
        $school = School::first();

        $metrics = [
            'total_students' => $school?->student_count ?? 0,
            'total_staff' => $school?->staff_count ?? 0,
            'total_classrooms' => $school?->classroom_count ?? 0,
            'total_majors' => Major::count(),
            'total_news' => News::count(),
            'total_facilities' => Facility::count(),
            'total_extracurriculars' => Extracurricular::count(),
            'total_innovations' => Innovation::count(),
            'total_alumni' => Alumni::count(),
            'total_faqs' => Faq::count(),
            'is_ppdb_active' => Ppdb::where('is_active', true)->exists(),
        ];

        return $this->successResponse($metrics, 'Metrik dashboard berhasil diambil.');
    }
}
