<?php
namespace App\Http\Controllers\Api;
use App\Models\School; use Illuminate\Http\JsonResponse;
class SchoolController { public function __invoke(): JsonResponse { return response()->json(['data' => School::query()->first(), 'meta' => (object) [], 'message' => null]); } }
