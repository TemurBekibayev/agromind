<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\GpsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelemetryController extends Controller
{
    protected $gpsService;

    public function __construct(GpsService $gpsService)
    {
        $this->gpsService = $gpsService;
    }

    /**
     * Real GPS qurilmalaridan yoki Traccar webhook-dan telemetriyani qabul qilish.
     */
    public function receive(Request $request)
    {
        // Flespi formatidagi so'rovlarni aniqlash va moslashtirish
        $data = $request->all();
        
        // Agar Flespi massiv (array) ko'rinishida yuborgan bo'lsa
        if (is_array($data) && isset($data[0]) && is_array($data[0]) && isset($data[0]['ident'])) {
            $fMessage = $data[0];
            $request->merge([
                'device_id' => $fMessage['ident'],
                'latitude' => $fMessage['position.latitude'] ?? ($fMessage['latitude'] ?? null),
                'longitude' => $fMessage['position.longitude'] ?? ($fMessage['longitude'] ?? null),
                'speed' => $fMessage['position.speed'] ?? ($fMessage['speed'] ?? 0.00),
                'fuel_level' => $fMessage['can.fuel.level'] ?? ($fMessage['fuel_level'] ?? 100.00),
                'signal_strength' => $fMessage['gsm.signal.level'] ?? ($fMessage['signal_strength'] ?? 100),
            ]);
        } 
        // Agar Flespi bitta obyekt ko'rinishida yuborgan bo'lsa
        elseif (isset($data['ident'])) {
            $request->merge([
                'device_id' => $data['ident'],
                'latitude' => $data['position.latitude'] ?? ($data['latitude'] ?? null),
                'longitude' => $data['position.longitude'] ?? ($data['longitude'] ?? null),
                'speed' => $data['position.speed'] ?? ($data['speed'] ?? 0.00),
                'fuel_level' => $data['can.fuel.level'] ?? ($data['fuel_level'] ?? 100.00),
                'signal_strength' => $data['gsm.signal.level'] ?? ($data['signal_strength'] ?? 100),
            ]);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'device_id' => 'required|string', // Tracker IMEI raqami
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'fuel_level' => 'nullable|numeric',
            'signal_strength' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            Log::warning("Telemetry validation skipped: " . json_encode($validator->errors()->all()));
            return response()->json([
                'status' => 'skipped',
                'message' => 'Nogps yoki chala telemetriya paketi o\'tkazib yuborildi.',
                'errors' => $validator->errors()->all()
            ], 200);
        }

        // Koordinatalarni O'zbekiston hududiga mosligini tekshirish
        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;
        if ($lat < 35.0 || $lat > 46.0 || $lng < 50.0 || $lng > 75.0) {
            Log::warning("Telemetry coordinate out of bounds (Uzbekistan): Lat: {$lat}, Lng: {$lng} for Device: {$request->device_id}");
            return response()->json([
                'status' => 'skipped',
                'message' => 'Kiritilgan koordinatalar O\'zbekiston hududidan tashqarida.'
            ], 200);
        }

        // Qurilmani gps_device_id (IMEI) bo'yicha bazadan qidiramiz
        $vehicle = Vehicle::where('gps_device_id', $request->device_id)->first();

        if (!$vehicle) {
            Log::warning("Telemetry received for unknown GPS Device ID (IMEI): {$request->device_id}");
            return response()->json([
                'status' => 'skipped',
                'message' => 'Ushbu IMEI raqamli texnika bazadan topilmadi, lekin oqim to\'xtamasligi uchun 200 OK qaytarildi.'
            ], 200);
        }

        // Ma'lumotlarni qayta ishlash (geofence, low fuel tekshiruvi bilan)
        $track = $this->gpsService->processIncoming([
            'vehicle_id' => $vehicle->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'speed' => $request->speed ?? 0.00,
            'fuel_level' => $request->fuel_level ?? 100.00,
            'signal_strength' => $request->signal_strength ?? 100,
        ]);

        $command = \Illuminate\Support\Facades\Cache::pull("gps_command_{$request->device_id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetriya muvaffaqiyatli qabul qilindi.',
            'track_id' => $track->id,
            'command' => $command
        ]);
    }
}
