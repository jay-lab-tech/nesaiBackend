<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminMajorController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $query = Major::query()->with(['subjects', 'careers'])->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $majors = $query->paginate($perPage);

        return $this->paginatedResponse($majors, 'Daftar jurusan berhasil diambil.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:majors,slug'],
            'summary' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'subjects' => ['nullable', 'array'],
            'subjects.*.name' => ['required_with:subjects', 'string', 'max:255'],
            'subjects.*.description' => ['nullable', 'string'],
            'careers' => ['nullable', 'array'],
            'careers.*.name' => ['required_with:careers', 'string', 'max:255'],
            'careers.*.description' => ['nullable', 'string'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $major = Major::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['subjects'])) {
            foreach ($validated['subjects'] as $subject) {
                $major->subjects()->create($subject);
            }
        }

        if (!empty($validated['careers'])) {
            foreach ($validated['careers'] as $career) {
                $major->careers()->create($career);
            }
        }

        $major->load(['subjects', 'careers']);

        return $this->successResponse($major, 'Data jurusan berhasil disimpan.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $major = Major::with(['subjects', 'careers', 'innovations', 'admissionStats', 'alumni'])->find($id);

        if (!$major) {
            return $this->errorResponse('Jurusan tidak ditemukan.', 404);
        }

        return $this->successResponse($major, 'Detail jurusan berhasil diambil.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $major = Major::find($id);

        if (!$major) {
            return $this->errorResponse('Jurusan tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', "unique:majors,slug,{$id}"],
            'summary' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'subjects' => ['nullable', 'array'],
            'subjects.*.name' => ['required_with:subjects', 'string', 'max:255'],
            'subjects.*.description' => ['nullable', 'string'],
            'careers' => ['nullable', 'array'],
            'careers.*.name' => ['required_with:careers', 'string', 'max:255'],
            'careers.*.description' => ['nullable', 'string'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $major->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['subjects'])) {
            $major->subjects()->delete();
            foreach ($validated['subjects'] as $subject) {
                $major->subjects()->create($subject);
            }
        }

        if (isset($validated['careers'])) {
            $major->careers()->delete();
            foreach ($validated['careers'] as $career) {
                $major->careers()->create($career);
            }
        }

        $major->load(['subjects', 'careers']);

        return $this->successResponse($major, 'Data jurusan berhasil diperbarui.');
    }

    public function destroy(int $id): JsonResponse
    {
        $major = Major::find($id);

        if (!$major) {
            return $this->errorResponse('Jurusan tidak ditemukan.', 404);
        }

        $major->delete();

        return $this->successResponse(null, 'Jurusan berhasil dihapus.');
    }
}
