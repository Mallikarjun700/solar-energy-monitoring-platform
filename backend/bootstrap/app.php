<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(
            \App\Http\Middleware\CorrelationIdMiddleware::class
        );
        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'idempotency' => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
                'errors' => $exception->errors(),
            ], 422);
        });
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 404);
        });
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 401);
        });
        $exceptions->render(function (AccessDeniedHttpException $exception,Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 403);
        });
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            logger()->error('Unhandled API exception', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.',
                'correlation_id' => app()->bound('correlation_id')
                    ? app('correlation_id')
                    : null,
            ], 500);
        });
    })->create();
