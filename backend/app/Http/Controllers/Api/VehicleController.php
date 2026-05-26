<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VehicleController extends Controller
{
    /**
     * Fermerning barcha texnikalari ro'yxatini olish.
     */
    public function index(Request $request)
    {
        $farmIds = $request->user()->farms()->pluck('id');

        $vehicles = Vehicle::whereIn('farm_id', $farmIds)
            ->with(['farm', 'latestGpsTrack'])
            ->get();

        // Har bir texnika uchun virtual statusni hisoblab qo'shamiz
        $vehicles->each(function ($vehicle) {
            $vehicle->status_label = $vehicle->status; // Model accessor
        });

        return response()->json([
            'status' => 'success',
            'vehicles' => $vehicles
        ]);
    }

    /**
     * Texnikaning joriy/oxirgi GPS koordinatalari.
     */
    public function location(Request $request, $id)
    {
        $farmIds = $request->user()->farms()->pluck('id');
        $vehicle = Vehicle::whereIn('farm_id', $farmIds)->find($id);

        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Texnika topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $latestTrack = $vehicle->latestGpsTrack;

        if (!$latestTrack) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ushbu texnika uchun GPS koordinata topilmadi (offline).'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'location' => $latestTrack,
            'vehicle_name' => $vehicle->name,
            'plate_number' => $vehicle->plate_number,
            'status_label' => $vehicle->status,
        ]);
    }

    /**
     * Texnikaning oxirgi 24 soatlik GPS harakat tarixi.
     */
    public function history(Request $request, $id)
    {
        $farmIds = $request->user()->farms()->pluck('id');
        $vehicle = Vehicle::whereIn('farm_id', $farmIds)->find($id);

        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Texnika topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $history = $vehicle->gpsTracks()
            ->where('recorded_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'history' => $history
        ]);
    }
}
