<?php

use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeadLetterController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\PlantController;
use App\Http\Controllers\Api\V1\TelemetryController;
use App\Http\Middleware\ValidateTelemetryRequestSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

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
        } catch (Throwable $exception) {
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/plants', [PlantController::class, 'index'])->middleware('abilities:plants:read');
        Route::post('/plants', [PlantController::class, 'store'])->middleware('abilities:plants:write');
        Route::get('/plants/{plant}', [PlantController::class, 'show'])->middleware('abilities:plants:read');
        Route::get('/assets', [AssetController::class, 'index'])->middleware('abilities:assets:read');
        Route::post('/assets', [AssetController::class, 'store'])->middleware('abilities:assets:write');
        Route::get('/assets/{asset}', [AssetController::class, 'show'])->middleware('abilities:assets:read');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])->middleware('abilities:assets:write');
        Route::patch('/assets/{asset}', [AssetController::class, 'update'])->middleware('abilities:assets:write');
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->middleware('abilities:assets:write');
        Route::get('/devices', [DeviceController::class, 'index'])->middleware('abilities:devices:read');
        Route::post('/devices', [DeviceController::class, 'store'])->middleware('abilities:devices:write');
        Route::get('/devices/{device}', [DeviceController::class, 'show'])->middleware('abilities:devices:read');
        Route::put('/devices/{device}', [DeviceController::class, 'update'])->middleware('abilities:devices:write');
        Route::patch('/devices/{device}', [DeviceController::class, 'update'])->middleware('abilities:devices:write');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->middleware('abilities:devices:write');

        Route::post('/telemetry/events', [TelemetryController::class, 'ingest'])->middleware(['abilities:telemetry:write',
            'throttle:telemetry',
            ValidateTelemetryRequestSize::class,
            'idempotency',
        ]);

        Route::get('/telemetry/health', [TelemetryController::class, 'health'])->middleware('abilities:telemetry:read');
        Route::get('/telemetry/events', [TelemetryController::class, 'index'])->middleware('abilities:telemetry:read');
        Route::get('/telemetry/events/cursor', [TelemetryController::class, 'cursorIndex'])->middleware('abilities:telemetry:read');
        Route::get('/telemetry/devices/{deviceId}/latest', [TelemetryController::class, 'latest'])->middleware('abilities:telemetry:read');
        Route::get('/dlq', [DeadLetterController::class, 'index'])->middleware('abilities:dlq:read');
        Route::post('/dlq/{deadLetterEvent}/replay', [DeadLetterController::class, 'replay'])->middleware('abilities:dlq:replay');
        Route::get('/alerts', [AlertController::class, 'index'])->middleware('abilities:alerts:read');
        Route::get('/alerts/{alert}', [AlertController::class, 'show'])->middleware('abilities:alerts:read');
        Route::post('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->middleware('abilities:alerts:acknowledge');
        Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->middleware('abilities:alerts:resolve');
    });
});
