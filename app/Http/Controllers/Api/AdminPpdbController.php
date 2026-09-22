<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ppdb;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPpdbController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        $ppdb = Ppdb::where('is_active', true)->first() ?? Ppdb::first() ?? new Ppdb();

        return $this->successResponse($ppdb, 'Pengaturan PPDB berhasil diambil.');
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string'],
            'schedule' => ['nullable', 'array'],
            'schedule.*.stage' => ['required_with:schedule', 'string'],
            'schedule.*.date' => ['required_with:schedule', 'string'],
            'schedule.*.desc' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $ppdb = Ppdb::where('is_active', true)->first() ?? Ppdb::first();

        if ($ppdb) {
            $ppdb->update($validated);
        } else {
            $ppdb = Ppdb::create($validated);
        }

        return $this->successResponse($ppdb, 'Pengaturan PPDB berhasil diperbarui.');
    }
}
