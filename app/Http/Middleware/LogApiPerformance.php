<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LogApiPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : ($exception instanceof ValidationException ? 422 : 500);

            Log::error('api.request', [
                'method' => $request->method(),
                'route' => $request->route()?->uri() ?? $request->path(),
                'status' => $status,
                'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                'error' => $exception::class,
            ]);

            throw $exception;
        }

        Log::info('api.request', [
            'method' => $request->method(),
            'route' => $request->route()?->uri() ?? $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
            'response_bytes' => strlen((string) $response->getContent()),
        ]);

        return $response;
    }
}
