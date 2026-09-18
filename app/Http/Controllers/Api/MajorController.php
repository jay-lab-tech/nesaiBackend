<?php
namespace App\Http\Controllers\Api;
use App\Models\Major; use Illuminate\Http\JsonResponse;
class MajorController { public function index(): JsonResponse { return response()->json(['data' => Major::query()->paginate(), 'meta' => (object) [], 'message' => null]); } public function show(string $slug): JsonResponse { return response()->json(['data' => Major::query()->with(['subjects','careers','alumni'])->where('slug', $slug)->firstOrFail(), 'meta' => (object) [], 'message' => null]); } }
