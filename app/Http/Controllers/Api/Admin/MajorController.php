<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MajorController extends Controller
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
        $logoRule = $request->hasFile('logo')
            ? ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048']
            : ['nullable', 'string', 'max:2048'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:majors,slug'],
            'summary' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'logo' => $logoRule,
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

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('majors', 'public');
        } elseif (!empty($validated['logo'])) {
            $logoPath = $validated['logo'];
        }

        $major = Major::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'logo' => $logoPath,
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

        $logoRule = $request->hasFile('logo')
            ? ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048']
            : ['nullable', 'string', 'max:2048'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', "unique:majors,slug,{$id}"],
            'summary' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'logo' => $logoRule,
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

        $updateData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            if ($major->logo && !Str::startsWith($major->logo, ['http://', 'https://'])) {
                Storage::disk('public')->delete($major->logo);
            }
            $updateData['logo'] = $request->file('logo')->store('majors', 'public');
        } elseif ($request->has('logo')) {
            $newLogo = $validated['logo'] ?? null;
            if ($newLogo !== $major->logo && $major->logo && !Str::startsWith($major->logo, ['http://', 'https://'])) {
                Storage::disk('public')->delete($major->logo);
            }
            $updateData['logo'] = $newLogo;
        }

        $major->update($updateData);

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

        if ($major->logo && !Str::startsWith($major->logo, ['http://', 'https://'])) {
            Storage::disk('public')->delete($major->logo);
        }

        $major->delete();

        return $this->successResponse(null, 'Jurusan berhasil dihapus.');
    }
}

