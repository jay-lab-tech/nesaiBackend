<?php
namespace App\Http\Controllers\Api;
use App\Models\Facility; use Illuminate\Http\JsonResponse;
class FacilityController { public function __invoke(): JsonResponse { return response()->json(['data' => Facility::query()->orderBy('name')->get(), 'meta' => (object) [], 'message' => null]); } }
