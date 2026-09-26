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
            $request->validated('context', []),
            $request->session()->getId(),
        );

        return response()->json([
            'data' => $response,
            'meta' => (object) [],
            'message' => null,
        ]);
    }

    public function reset(\Illuminate\Http\Request $request, NesaiService $service): JsonResponse
    {
        $service->resetConversation($request->session()->getId());

        return response()->json([
            'data' => null,
            'meta' => (object) [],
            'message' => 'Percakapan berhasil direset.',
        ]);
    }
}
