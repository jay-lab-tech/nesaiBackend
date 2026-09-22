<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return standard success single / operation response.
     */
    protected function successResponse(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        return response()->json($payload, $code);
    }

    /**
     * Return standard paginated list response.
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, 200);
    }

    /**
     * Return standard error response.
     */
    protected function errorResponse(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
