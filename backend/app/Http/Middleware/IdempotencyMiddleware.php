<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey === null || $idempotencyKey === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Idempotency-Key header is required.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 400);
        }

        if (strlen($idempotencyKey) > 255 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $idempotencyKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Idempotency-Key format.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 400);
        }

        $requestHash = hash(
            'sha256',
            $request->method().'|'.
            $request->path().'|'.
            $request->getContent()
        );

        $existing = IdempotencyKey::query()
            ->where('key', $idempotencyKey)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            if ($existing->request_hash !== $requestHash) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Idempotency-Key has already been used with a different request.',
                    'correlation_id' => app()->bound('correlation_id')
                        ? app('correlation_id')
                        : null,
                ], 409);
            }

            if ($existing->status_code !== null) {
                return response()
                    ->json(
                        $existing->response_body,
                        $existing->status_code
                    )
                    ->header(
                        'X-Correlation-ID',
                        $existing->correlation_id
                    );
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Request with this Idempotency-Key is already being processed.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 409);
        }

        try {
            $idempotencyRecord = IdempotencyKey::create([
                'key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'correlation_id' => app()->bound('correlation_id') ? app('correlation_id') : null,
                'expires_at' => now()->addHours(24),
            ]);
        } catch (QueryException $exception) {
            /*
             * Another concurrent request may have claimed the same key
             * between our lookup and INSERT.
             */
            $idempotencyRecord = IdempotencyKey::query()
                ->where('key', $idempotencyKey)
                ->firstOrFail();

            if ($idempotencyRecord->request_hash !== $requestHash) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Idempotency-Key has already been used with a different request.',
                    'correlation_id' => app()->bound('correlation_id')
                        ? app('correlation_id')
                        : null,
                ], 409);
            }

            if ($idempotencyRecord->status_code !== null) {
                return response()
                    ->json(
                        $idempotencyRecord->response_body,
                        $idempotencyRecord->status_code
                    )
                    ->header(
                        'X-Correlation-ID',
                        $idempotencyRecord->correlation_id
                    );
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Request with this Idempotency-Key is already being processed.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 409);
        }

        $response = $next($request);

        if (! $response->headers->has('X-Correlation-ID')) {
            $response->headers->set('X-Correlation-ID', app()->bound('correlation_id') ? app('correlation_id') : null);

        }
        $idempotencyRecord->update([
            'status_code' => $response->getStatusCode(),
            'response_body' => json_decode(
                $response->getContent(),
                true
            ),
        ]);

        return $response;
    }
}
