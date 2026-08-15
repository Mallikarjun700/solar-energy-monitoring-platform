<?php

use App\Http\Controllers\Api\V1\PlantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\TelemetryController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('v1')->group(function () {
    Route::apiResource('plants', PlantController::class);
    Route::apiResource('assets', AssetController::class);
    Route::apiResource('devices', DeviceController::class);
});
Route::prefix('v1')->group(function () {
    Route::post('/telemetry/events', [TelemetryController::class, 'ingest']);
    
    // This route is for testing purposes and should be removed in production
    Route::get('/telemetry/events', [TelemetryController::class, 'index']); 
});
