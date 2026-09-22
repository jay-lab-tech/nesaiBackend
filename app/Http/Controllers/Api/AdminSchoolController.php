<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSchoolController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        $school = School::first() ?? new School();

        return $this->successResponse($school, 'Profil sekolah berhasil diambil.');
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'accreditation' => ['nullable', 'string', 'max:20'],
            'founded_year' => ['nullable', 'integer'],
            'area_size' => ['nullable', 'string', 'max:50'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'staff_count' => ['nullable', 'integer', 'min:0'],
            'student_count' => ['nullable', 'integer', 'min:0'],
            'classroom_count' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'social_links' => ['nullable', 'array'],
        ]);

        $school = School::first();

        if ($school) {
            $school->update($validated);
        } else {
            $school = School::create($validated);
        }

        return $this->successResponse($school, 'Profil sekolah berhasil diperbarui.');
    }
}
