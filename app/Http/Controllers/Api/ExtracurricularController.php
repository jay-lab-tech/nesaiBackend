<?php
namespace App\Http\Controllers\Api;
use App\Models\Extracurricular; use Illuminate\Http\JsonResponse;
class ExtracurricularController { public function __invoke(): JsonResponse { return response()->json(['data' => Extracurricular::query()->orderBy('name')->get(), 'meta' => (object) [], 'message' => null]); } }
