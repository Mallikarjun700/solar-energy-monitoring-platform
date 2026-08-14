<?php

use App\Http\Controllers\Api\V1\PlantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AssetController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('v1')->group(function () {
    Route::apiResource('plants', PlantController::class);
});

Route::prefix('v1')->group(function () {
    Route::apiResource('assets', AssetController::class);
});