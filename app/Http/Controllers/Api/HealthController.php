<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\JsonResponse;
class HealthController { public function __invoke(): JsonResponse { return response()->json(['data' => ['status' => 'ok', 'service' => 'nesas-api'], 'meta' => (object) [], 'message' => null]); } }
