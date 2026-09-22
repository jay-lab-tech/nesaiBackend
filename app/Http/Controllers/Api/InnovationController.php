<?php
namespace App\Http\Controllers\Api;
use App\Models\Innovation; use Illuminate\Http\JsonResponse;
class InnovationController { public function __invoke(): JsonResponse { return response()->json(['data' => Innovation::query()->with('major')->get(), 'meta' => (object) [], 'message' => null]); } }
