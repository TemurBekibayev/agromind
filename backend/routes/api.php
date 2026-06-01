<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FarmController;
use App\Http\Controllers\Api\SoilAnalysisController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\AiChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| AgroMind mobil ilovasi uchun API endpoinlari.
|
*/

// Ochiq marshrutlar (Public routes)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::any('/telemetry', [TelemetryController::class, 'receive']);

Route::get('/debug-logs', function() {
    $logPath = storage_path('logs/laravel.log');
    if (!file_exists($logPath)) {
        return response()->json(['message' => 'Log file not found.']);
    }
    $lines = file($logPath);
    $lastLines = array_slice($lines, -150);
    return response(implode("", $lastLines), 200, ['Content-Type' => 'text/plain']);
});

// Himoyalangan marshrutlar (Protected routes via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Foydalanuvchi profili
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Fermalar CRUD
    Route::apiResource('farms', FarmController::class);

    // Tuproq tahlillari va AI maslahatlari
    Route::get('/farms/{farm}/analyses', [SoilAnalysisController::class, 'index']);
    Route::post('/farms/{farm}/analyses', [SoilAnalysisController::class, 'store']);
    Route::get('/analyses/{id}', [SoilAnalysisController::class, 'show']);
    Route::post('/analyses/{id}/recommend', [SoilAnalysisController::class, 'recommend']);
    Route::post('/ai/chat', [AiChatController::class, 'ask']);

    // Transport vositalari va GPS kuzatish
    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::get('/vehicles/{id}/location', [VehicleController::class, 'location']);
    Route::get('/vehicles/{id}/history', [VehicleController::class, 'history']);

    // Ogohlantirishlar (Alerts)
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{id}/resolve', [AlertController::class, 'resolve']);
});
