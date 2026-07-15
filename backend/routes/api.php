<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FarmController;
use App\Http\Controllers\Api\SoilAnalysisController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\ListingController;

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
Route::post('/telemetry', [TelemetryController::class, 'receive']);
Route::post('/appeals', [\App\Http\Controllers\Api\AppealController::class, 'store']);
Route::post('/telegram/webhook', [\App\Http\Controllers\Api\TelegramController::class, 'webhook']);
Route::get('/setup-admin', function() {
    $admin = \App\Models\User::updateOrCreate(
        ['phone' => '998901234567'],
        [
            'name' => 'Admin AgroMind',
            'role' => 'admin',
            'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
        ]
    );
    return response()->json([
        'status' => 'success',
        'message' => 'Admin account created or updated successfully!',
        'phone' => '998901234567',
        'password' => 'secret123'
    ]);
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
    Route::delete('/analyses/{id}', [SoilAnalysisController::class, 'destroy']);
    Route::post('/ai/chat', [AiChatController::class, 'ask']);

    // Transport vositalari va GPS kuzatish
    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::get('/vehicles/{id}/location', [VehicleController::class, 'location']);
    Route::get('/vehicles/{id}/history', [VehicleController::class, 'history']);
    Route::post('/vehicles/{id}/control', [VehicleController::class, 'control']);
    Route::post('/vehicles/{id}/relay', [VehicleController::class, 'control']);

    // Ogohlantirishlar (Alerts)
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{id}/resolve', [AlertController::class, 'resolve']);

    // Dehqonlar suhbati (Chat)
    Route::get('/chat/messages', [ChatMessageController::class, 'index']);
    Route::post('/chat/messages', [ChatMessageController::class, 'store']);

    // Texnika va uskunalar ijarasi e'lonlari (Listings)
    Route::get('/listings', [ListingController::class, 'index']);
    Route::post('/listings', [ListingController::class, 'store']);
    Route::delete('/listings/{id}', [ListingController::class, 'destroy']);

    // Yoqilg'i monitoringi va quyish (Fuel Management)
    Route::post('/vehicles/{id}/fuel-entries', [\App\Http\Controllers\Api\FuelController::class, 'store']);
    Route::get('/vehicles/{id}/fuel-report', [\App\Http\Controllers\Api\FuelController::class, 'report']);
    Route::post('/vehicles/{id}/fuel-alerts/{alertId}/resolve', [\App\Http\Controllers\Api\FuelController::class, 'resolveAlert']);

    // Shaxsiy suhbatlar (Private Chats)
    Route::get('/private-chats', [\App\Http\Controllers\Api\PrivateMessageController::class, 'index']);
    Route::get('/private-chats/{partnerId}', [\App\Http\Controllers\Api\PrivateMessageController::class, 'getMessages']);
    Route::post('/private-chats', [\App\Http\Controllers\Api\PrivateMessageController::class, 'sendMessage']);
    Route::get('/admin-user', [\App\Http\Controllers\Api\PrivateMessageController::class, 'getAdminUser']);
});
