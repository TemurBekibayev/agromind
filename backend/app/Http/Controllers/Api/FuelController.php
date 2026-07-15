<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\FuelEntry;
use App\Models\FuelAlert;
use App\Models\GpsTrack;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FuelController extends Controller
{
    /**
     * Yoqilg'i quyish miqdorini kiritish.
     */
    public function store(Request $request, $id)
    {
        $user = $request->user();
        
        if ($user->isAdmin() || $user->isMonitor()) {
            $vehicle = Vehicle::find($id);
        } else {
            $farmIds = $user->farms()->pluck('id');
            $vehicle = Vehicle::whereIn('farm_id', $farmIds)->find($id);
        }

        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Texnika topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $request->validate([
            'fuel_amount' => 'required|numeric|min:0.1',
            'refilled_at' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $fuelAmount = (float) $request->fuel_amount;
        $refilledAt = $request->refilled_at ? Carbon::parse($request->refilled_at) : Carbon::now();
        $notes = $request->notes;

        $latestTrack = $vehicle->latestGpsTrack;

        // Joriy bak holatini litrda aniqlaymiz
        $currentFuelPercent = $latestTrack ? (float) $latestTrack->fuel_level : 0.0;
        $capacity = (float) $vehicle->fuel_capacity;
        $currentFuelLiters = ($currentFuelPercent / 100) * $capacity;

        // Yangi yoqilg'i yozuvini yaratamiz
        $entry = FuelEntry::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'fuel_amount' => $fuelAmount,
            'refilled_at' => $refilledAt,
            'notes' => $notes,
        ]);

        // Yangi yoqilg'i foizini hisoblaymiz (agar sig'imdan oshib ketsa yoki teng bo'lsa 100%, aks holda normal hisoblanadi)
        $newFuelLiters = $currentFuelLiters + $fuelAmount;
        $newFuelPercent = $newFuelLiters >= $capacity ? 100.0 : ($newFuelLiters / $capacity) * 100.0;

        if ($latestTrack) {
            // Oxirgisini yangilaymiz, shunda xaritada ko'rinadi
            $latestTrack->update([
                'fuel_level' => $newFuelPercent
            ]);
        } else {
            // Agar umuman signal kelmagan bo'lsa, yangi track ochamiz
            GpsTrack::create([
                'vehicle_id' => $vehicle->id,
                'latitude' => 41.311081,
                'longitude' => 69.240562,
                'speed' => 0.00,
                'fuel_level' => $newFuelPercent,
                'recorded_at' => Carbon::now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Yoqilg\'i quyish miqdori muvaffaqiyatli saqlandi.',
            'warning' => null,
            'entry' => $entry
        ], 201);
    }

    /**
     * Yoqilg'i hisoboti va statistikasini olish.
     */
    public function report(Request $request, $id)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isMonitor()) {
            $vehicle = Vehicle::find($id);
        } else {
            $farmIds = $user->farms()->pluck('id');
            $vehicle = Vehicle::whereIn('farm_id', $farmIds)->find($id);
        }

        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Texnika topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        // Oxirgi 3 kun ichida bosib o'tilgan masofani hisoblaymiz
        $tracks = $vehicle->gpsTracks()
            ->where('recorded_at', '>=', Carbon::now()->subDays(3))
            ->orderBy('recorded_at', 'asc')
            ->get();

        $distance = 0.0;
        for ($i = 0; $i < count($tracks) - 1; $i++) {
            $t1 = $tracks[$i];
            $t2 = $tracks[$i + 1];
            $distance += $this->haversineDistance(
                (float)$t1->latitude, (float)$t1->longitude,
                (float)$t2->latitude, (float)$t2->longitude
            );
        }

        $expectedRate = 0.45; // litr/km
        $expectedConsumed = $distance * $expectedRate;
        $totalRefilled = (float) $vehicle->fuelEntries()->sum('fuel_amount');
        
        $latestTrack = $vehicle->latestGpsTrack;
        $currentFuelPercent = $latestTrack ? (float) $latestTrack->fuel_level : 0.0;
        $currentFuelLiters = ($currentFuelPercent / 100) * (float) $vehicle->fuel_capacity;

        // Yoqilg'i holatini aniqlash
        $fuelStatus = 'ok';
        $warningMessage = null;

        if ($currentFuelPercent == 0) {
            $fuelStatus = 'empty';
        } elseif ($currentFuelPercent <= 15.0) {
            $fuelStatus = 'low';
        }

        // Agar yoqilg'i 0% ga tushib ketgan bo'lsa va hali ham yursa -> missing_refill
        if ($currentFuelPercent <= 5.0 && $distance > 2.0) {
            $fuelStatus = 'missing_refill';
            $warningMessage = "Siz solingan yoqilg'i miqdorini kiritmagansiz! Tizim hisobi bo'yicha yoqilg'i tugagan bo'lishi kerak edi, lekin transport vositasi harakatlanishda davom etmoqda.";
        }

        // Ishonch reytingi (Trust Score)
        $alertCount = $vehicle->fuelAlerts()->whereIn('status', ['pending_check', 'confirmed'])->count();
        $trustScore = max(0, 100 - ($alertCount * 15));

        return response()->json([
            'status' => 'success',
            'report' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->name,
                'plate_number' => $vehicle->plate_number,
                'distance_traveled_km' => round($distance, 1),
                'expected_rate_l_km' => $expectedRate,
                'expected_consumed_liters' => round($expectedConsumed, 2),
                'actual_rate_l_km' => round($distance > 0 ? ($totalRefilled / $distance) : 0, 2),
                'total_refilled_liters' => $totalRefilled,
                'current_fuel_liters' => round($currentFuelLiters, 1),
                'current_fuel_percent' => round($currentFuelPercent, 1),
                'fuel_status' => $fuelStatus,
                'warning_message' => $warningMessage,
                'trust_score' => $trustScore,
                'average_difference_percent' => 12.4,
                'suspicious_events_count' => $alertCount,
            ],
            'fuel_entries' => $vehicle->fuelEntries()->orderBy('refilled_at', 'desc')->take(10)->get()->map(function ($e) {
                return [
                    'id' => $e->id,
                    'fuel_amount' => $e->fuel_amount,
                    'refilled_at' => $e->refilled_at->toIso8601String(),
                    'notes' => $e->notes
                ];
            }),
            'fuel_alerts' => $vehicle->fuelAlerts()->orderBy('created_at', 'desc')->take(10)->get()->map(function ($a) {
                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'severity' => $a->severity,
                    'description' => $a->description,
                    'calculated_fuel_before' => $a->calculated_fuel_before,
                    'refilled_amount' => $a->refilled_amount,
                    'status' => $a->status,
                    'created_at' => $a->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Shubhali holatni hal qilish.
     */
    public function resolveAlert(Request $request, $id, $alertId)
    {
        $request->validate([
            'status' => 'required|string|in:confirmed,rejected',
        ]);

        $alert = FuelAlert::where('vehicle_id', $id)->find($alertId);

        if (!$alert) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ogohlantirish topilmadi.'
            ], 404);
        }

        $alert->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ogohlantirish holati muvaffaqiyatli yangilandi.',
            'alert' => $alert
        ]);
    }

    /**
     * Haversine formulasi yordamida masofani hisoblash (km).
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371.0; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
