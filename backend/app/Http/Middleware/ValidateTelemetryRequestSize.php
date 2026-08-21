<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTelemetryRequestSize
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5 MB
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $contentLength = (int) $request->header('Content-Length', 0);

        if ($contentLength > self::MAX_BYTES) {
            return response()->json([
                'message' => 'Telemetry request payload is too large.',
                'max_bytes' => self::MAX_BYTES,
            ], 413);
        }
        return $next($request);
    }
}
