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
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/telemetry', [TelemetryController::class, 'receive']);

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
    Route::get('/vehicles/{id}/fuel-report', [VehicleController::class, 'fuelReport']);
    Route::post('/vehicles/{id}/fuel-entries', [VehicleController::class, 'storeFuelEntry']);
    Route::post('/vehicles/{id}/fuel-alerts/{alertId}/resolve', [VehicleController::class, 'resolveFuelAlert']);

    // Ogohlantirishlar (Alerts)
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{id}/resolve', [AlertController::class, 'resolve']);

    // Dehqonlar suhbati (Chat)
    Route::get('/chat/messages', [ChatMessageController::class, 'index']);
    Route::post('/chat/messages', [ChatMessageController::class, 'store']);

    // Shaxsiy suhbatlar (Private Chats)
    Route::get('/private-chats', [\App\Http\Controllers\Api\PrivateChatController::class, 'index']);
    Route::get('/private-chats/{partnerId}', [\App\Http\Controllers\Api\PrivateChatController::class, 'show']);
    Route::post('/private-chats', [\App\Http\Controllers\Api\PrivateChatController::class, 'store']);

    // Adminga murojaat yuborish
    Route::post('/support-messages', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $msg = \App\Models\SupportMessage::create([
            'user_id' => $request->user()->id,
            'type' => 'support',
            'sender_name' => $request->user()->name,
            'sender_phone' => $request->user()->phone,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Murojaatingiz adminga muvaffaqiyatli yuborildi.',
            'data' => $msg
        ], 201);
    });

    // Texnika va uskunalar ijarasi e'lonlari (Listings)
    Route::get('/listings', [ListingController::class, 'index']);
    Route::post('/listings', [ListingController::class, 'store']);
    Route::delete('/listings/{id}', [ListingController::class, 'destroy']);

    // Suv limitlari va sarfi (Water records)
    Route::get('/water-records', function (\Illuminate\Http\Request $request) {
        $farmer = $request->user();
        
        $farmIds = \App\Models\Farm::where('user_id', $farmer->id)->pluck('id');

        $records = \App\Models\WaterRecord::whereIn('farm_id', $farmIds)
            ->with('farm')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'farmer_id' => $farmer->id,
            'farmer_name' => $farmer->name,
            'records' => $records
        ]);
    });
});
