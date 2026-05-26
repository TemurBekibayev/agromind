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
