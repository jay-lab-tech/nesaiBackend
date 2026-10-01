<?php
namespace App\Http\Controllers\Api;
use App\Models\AlumniTrackingStat; use Illuminate\Http\JsonResponse;
class AlumniTrackingStatController { public function __invoke(): JsonResponse { return response()->json(['data' => AlumniTrackingStat::query()->orderBy('year')->get(), 'meta' => (object) [], 'message' => null]); } }
