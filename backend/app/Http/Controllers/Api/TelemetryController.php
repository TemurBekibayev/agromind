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
        Log::info("Telemetry Debug - Method: " . $request->method());
        Log::info("Telemetry Debug - Headers: " . json_encode($request->headers->all()));
        Log::info("Telemetry Debug - Body: " . json_encode($request->all()));

        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'debug',
                'message' => 'GET request received successfully.',
                'headers' => $request->headers->all(),
                'data' => $request->all()
            ]);
        }

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

        $request->validate([
            'device_id' => 'required|string', // Tracker IMEI raqami
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'fuel_level' => 'nullable|numeric',
            'signal_strength' => 'nullable|integer',
        ]);

        // Qurilmani gps_device_id (IMEI) bo'yicha bazadan qidiramiz
        $vehicle = Vehicle::where('gps_device_id', $request->device_id)->first();

        if (!$vehicle) {
            Log::warning("Telemetry received for unknown GPS Device ID (IMEI): {$request->device_id}");
            return response()->json([
                'status' => 'error',
                'message' => 'Ushbu IMEI raqamli texnika bazadan topilmadi.'
            ], 404);
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

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetriya muvaffaqiyatli qabul qilindi.',
            'track_id' => $track->id
        ]);
    }
}
