<?php
namespace App\Http\Controllers\Api;
use App\Models\AdmissionStat; use Illuminate\Http\JsonResponse;
class AdmissionStatController { public function __invoke(): JsonResponse { return response()->json(['data' => AdmissionStat::query()->with('major')->orderBy('major_id')->orderBy('year')->get(), 'meta' => (object) [], 'message' => null]); } }
