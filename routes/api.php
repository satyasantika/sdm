<?php

use App\Http\Controllers\Api\V1\DosenController;
use App\Http\Controllers\Api\V1\PejabatController;
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', HealthController::class)->middleware('throttle:30,1');

Route::prefix('v1')->middleware(['auth:sanctum', 'abilities:sdm:read', 'throttle:api', 'api.versi'])->group(function () {
    Route::get('/dosen', [DosenController::class, 'index']);
    Route::get('/dosen/{pegawai}', [DosenController::class, 'show']);
    Route::get('/pejabat', [PejabatController::class, 'index']);
});
