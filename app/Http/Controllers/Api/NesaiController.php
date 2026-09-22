<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\NesaiChatRequest;
use App\Services\Nesai\NesaiService;
use Illuminate\Http\JsonResponse;

class NesaiController
{
    public function __invoke(NesaiChatRequest $request, NesaiService $service): JsonResponse
    {
        $response = $service->respond(
            $request->validated('message'),
            $request->validated('context', [])
        );

        return response()->json([
            'data' => $response,
            'meta' => (object) [],
            'message' => null,
        ]);
    }
}
