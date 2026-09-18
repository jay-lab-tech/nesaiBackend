<?php
namespace App\Http\Controllers\Api;
use App\Http\Requests\NesaiChatRequest; use App\Services\Nesai\NesaiService; use Illuminate\Http\JsonResponse;
class NesaiController { public function __invoke(NesaiChatRequest $request, NesaiService $service): JsonResponse { return response()->json(['data' => $service->respond($request->validated('message')), 'meta' => (object) [], 'message' => null]); } }
