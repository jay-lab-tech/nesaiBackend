<?php
namespace App\Http\Controllers\Api;
use App\Http\Requests\RecommendationRequest; use App\Services\RecommendationService; use Illuminate\Http\JsonResponse;
class RecommendationController { public function __invoke(RecommendationRequest $request, RecommendationService $service): JsonResponse { return response()->json(['data' => $service->recommend($request->validated('interests')), 'meta' => ['status' => 'scoring-rules-pending'], 'message' => 'The real school scoring matrix has not yet been supplied.']); } }
