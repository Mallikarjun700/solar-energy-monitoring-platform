<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID');

        if (! $correlationId) {
            $correlationId = (string) Str::uuid();
        }

        $request->headers->set('X-Correlation-ID', $correlationId);

        app()->instance('correlation_id', $correlationId);

        $response = $next($request);

        if (! $response->headers->has('X-Correlation-ID')) {
            $response->headers->set('X-Correlation-ID', app()->bound('correlation_id') ? app('correlation_id') : null);
        }

        if (
            $request->is('api/*')
            && $response instanceof JsonResponse
            && $response->isSuccessful()
            && $response->getStatusCode() !== Response::HTTP_NO_CONTENT
        ) {
            $data = json_decode($response->getContent(), true);

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($data)
                && ! array_is_list($data)
                && ! array_key_exists('correlation_id', $data)
            ) {
                $data['correlation_id'] = $correlationId;
                $response->setData($data);
            }
        }

        return $response;
    }
}
