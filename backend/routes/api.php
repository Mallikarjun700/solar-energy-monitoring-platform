<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Api\V1\PlantController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\TelemetryController;
use App\Http\Controllers\Api\V1\DeadLetterController;
use App\Http\Controllers\Api\V1\AlertController;


Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('plants', PlantController::class);
        Route::apiResource('assets', AssetController::class);
        Route::apiResource('devices', DeviceController::class);

        Route::post('/telemetry/events', [TelemetryController::class, 'ingest'])->middleware([
                'auth:sanctum', 
                'abilities:telemetry:write', 
                'throttle:telemetry', 
                \App\Http\Middleware\ValidateTelemetryRequestSize::class,
                'idempotency',
            ]);
        Route::get('/dlq', [DeadLetterController::class, 'index'])->middleware('abilities:dlq:read');
        Route::post('/dlq/{deadLetterEvent}/replay',[DeadLetterController::class, 'replay'])->middleware('abilities:dlq:replay');

        Route::get('/alerts', [AlertController::class, 'index'])->middleware('abilities:alerts:read');
        Route::get('/alerts/{alert}', [AlertController::class, 'show'])->middleware('abilities:alerts:read');

        Route::get('/telemetry/health', [TelemetryController::class, 'health']);
        // This route is for testing purposes and should be removed in production
        Route::get('/telemetry/events', [TelemetryController::class, 'index']); 
        Route::get('/telemetry/devices/{deviceId}/latest',[TelemetryController::class, 'latest']);

        Route::get('/telemetry/events/cursor',[TelemetryController::class, 'cursorIndex']);
        Route::get('/alerts', [AlertController::class, 'index'])->middleware('abilities:alerts:read');
        Route::get('/alerts/{alert}', [AlertController::class, 'show'])->middleware('abilities:alerts:read');

        Route::post('/alerts/{alert}/acknowledge',[AlertController::class, 'acknowledge'])->middleware('abilities:alerts:acknowledge');
        Route::post('/alerts/{alert}/resolve',[AlertController::class, 'resolve'])->middleware('abilities:alerts:resolve');

    });
});


Route::prefix('v1')->group(function () {
    Route::get('/ready', function () {
        try {
            DB::connection()->getPdo();

            if (Schema::hasTable('jobs')) {
                DB::table('jobs')->count();
            }

            return response()->json([
                'status' => 'ready',
                'checks' => [
                    'database' => 'ok',
                    'queue' => 'ok',
                ],
            ], 200);
        } catch (\Throwable $exception) {
            logger()->error('Readiness check failed', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'not_ready',
                'checks' => [
                    'database' => 'failed',
                    'queue' => 'failed',
                ],
            ], 503);
        }
    });
    Route::get('/test-error', function () {
        throw new \RuntimeException('Intentional test exception.');
    });
});
