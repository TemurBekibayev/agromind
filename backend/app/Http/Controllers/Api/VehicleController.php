<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class VehicleController extends Controller
{
    /**
     * Fermerning barcha texnikalari ro'yxatini olish.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isMonitor()) {
            // Admin va Monitorlar barcha texnikalarni ko'ra oladi
            $vehicles = Vehicle::with(['farm', 'latestGpsTrack'])->get();
        } else {
            // Oddiy fermerlar faqat o'zlarining texnikalarini ko'radi
            $farmIds = $user->farms()->pluck('id');
            $vehicles = Vehicle::whereIn('farm_id', $farmIds)
                ->with(['farm', 'latestGpsTrack'])
                ->get();
        }

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
        $user = $request->user();

        if ($user->isAdmin() || $user->isMonitor()) {
            // Admin va Monitorlar istalgan texnika joriy joylashuvini ko'radi
            $vehicle = Vehicle::with(['farm.geofences'])->find($id);
        } else {
            // Fermer faqat o'zining texnikasini ko'radi
            $farmIds = $user->farms()->pluck('id');
            $vehicle = Vehicle::whereIn('farm_id', $farmIds)->with(['farm.geofences'])->find($id);
        }

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

        // Geodeziya/Geofence buzilishi haqida faol ogohlantirish bormi?
        $hasGeofenceAlert = $vehicle->alerts()
            ->where('type', 'geofence_breach')
            ->where('status', 'active')
            ->exists();

        // Agar faol alert bo'lsa (tashqarida) is_inside_geofence = 0, aks holda 1
        $latestTrack->setAttribute('is_inside_geofence', $hasGeofenceAlert ? 0 : 1);

        return response()->json([
            'status' => 'success',
            'location' => $latestTrack,
            'vehicle_name' => $vehicle->name,
            'plate_number' => $vehicle->plate_number,
            'status_label' => $vehicle->status,
            'is_blocked' => (bool) $vehicle->is_blocked,
            'geofences' => $vehicle->farm ? $vehicle->farm->geofences : [],
            'debug_geofences_raw' => $vehicle->farm ? $vehicle->farm->geofences->map(function($g) {
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'coordinates_type' => gettype($g->coordinates),
                    'coordinates_val' => $g->coordinates,
                ];
            }) : null,
        ]);
    }

    /**
     * Texnikaning oxirgi 24 soatlik GPS harakat tarixi.
     */
    public function history(Request $request, $id)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isMonitor()) {
            // Admin va Monitorlar istalgan texnika harakat tarixini ko'radi
            $vehicle = Vehicle::find($id);
        } else {
            // Fermer faqat o'zining texnikasini ko'radi
            $farmIds = $user->farms()->pluck('id');
            $vehicle = Vehicle::whereIn('farm_id', $farmIds)->find($id);
        }

        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Texnika topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $days = (int) $request->query('days', 3);
        if ($days < 1 || $days > 7) {
            $days = 3;
        }

        $history = $vehicle->gpsTracks()
            ->where('recorded_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'history' => $history
        ]);
    }

    /**
     * Texnika dvigatelini bloklash yoki blokdan ochish (o'chirish yoki yoqish).
     */
    public function control(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|string|in:cut_off,restore,stop,resume,1,0,cutoff'
        ]);

        $user = $request->user();
        $action = $request->action;
        $isCutOff = in_array($action, ['cut_off', 'stop', '1', 'cutoff']);
        $bridgeAction = $isCutOff ? 'cutoff' : 'restore';
        $command = $isCutOff ? 'RELAY,1#' : 'RELAY,0#';

        if ($user->isAdmin() || $user->isMonitor()) {
            $vehicle = Vehicle::find($id);
        } else {
            // Fermer faqat o'zining texnikasini boshqara oladi
            $farmIds = $user->farms()->pluck('id');
            $vehicle = Vehicle::whereIn('farm_id', $farmIds)->find($id);
        }

        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Texnika topilmadi yoki sizga tegishli emas.'
            ], 404);
        }

        $gpsDeviceId = $vehicle->gps_device_id;
        if (!$gpsDeviceId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ushbu texnikada GPS qurilmasi o\'rnatilmagan.'
            ], 422);
        }

        // Bazada holatni yangilaymiz
        $vehicle->is_blocked = $isCutOff;
        $vehicle->save();

        // 1. Buyruqni cache ga yozamiz (Polling fallback - 1 soat davomida faol bo'ladi)
        Cache::put("gps_command_{$gpsDeviceId}", $command, 3600);

        // 2. Node.js TCP server HTTP API-siga so'rov jo'natishga urinib ko'ramiz (Real-time instant delivery)
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('http://host.docker.internal:5001/send-command', [
                'json' => [
                    'imei' => $gpsDeviceId,
                    'action' => $bridgeAction
                ],
                'connect_timeout' => 3,
                'timeout' => 5
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $isCutOff 
                        ? 'Dvigatelni o\'chirish buyrug\'i muvaffaqiyatli yuborildi.' 
                        : 'Dvigatelni yoqish (blokdan ochish) buyrug\'i muvaffaqiyatli yuborildi.',
                    'is_blocked' => $isCutOff,
                    'delivery' => 'instant'
                ]);
            }
        } catch (\Exception $e) {
            // Agar Node.js offline bo'lsa yoki ulanish uzilgan bo'lsa, xatolik qaytarmaymiz.
            // Chunki buyruq Cache ga yozildi va trekker keyingi safar bog'langanda avtomatik oladi.
        }

        return response()->json([
            'status' => 'success',
            'message' => $isCutOff 
                ? 'Dvigatelni o\'chirish buyrug\'i navbatga joylandi. Qurilma ulanishi kutilmoqda.' 
                : 'Dvigatelni yoqish (blokdan ochish) buyrug\'i navbatga joylandi. Qurilma ulanishi kutilmoqda.',
            'is_blocked' => $isCutOff,
            'delivery' => 'queued'
        ]);
    }

    /**
     * Texnikaning yoqilg'i sarfi hisoboti va oxirgi yoqilg'i quyish tarixi.
     */
    public function fuelReport(Request $request, $id)
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

        // Bosib o'tgan masofa (km)
        $distance = $vehicle->getDistanceTraveled();
        // Model bo'yicha kutilgan o'rtacha sarf (litr / km)
        $expectedRate = $vehicle->expected_fuel_rate;
        // Kutilgan jami yoqilg'i sarfi (litr)
        $expectedConsumed = round($distance * $expectedRate, 2);
        
        // Haqiqiy yoqilg'i sarfi (litr / km)
        $actualRate = $vehicle->getActualFuelRate();
        
        // Jami kiritilgan yoqilg'i miqdori (litr)
        $totalRefilled = $vehicle->fuelEntries()->sum('fuel_amount');

        // Dinamik yoqilg'i holati
        $capacity = floatval($vehicle->fuel_capacity) ?: 50.0;
        $currentFuel = floatval($vehicle->current_fuel_level);
        $percent = round(($currentFuel / $capacity) * 100.0, 1);
        
        $fuelStatus = 'ok';
        if ($percent <= 0) {
            if (floatval($vehicle->distance_since_empty) > 2.0) {
                $fuelStatus = 'missing_refill';
            } else {
                $fuelStatus = 'empty';
            }
        } elseif ($percent <= 15) {
            $fuelStatus = 'low';
        }

        $warningMessage = null;
        if ($fuelStatus === 'missing_refill') {
            $warningMessage = "Siz solingan yoqilg'i miqdorini kiritmagansiz! Iltimos, oxirgi yoqilg'i quyilganligini tasdiqlab ilovaga kiriting.";
        }

        // Oxirgi yoqilg'i quyishlar
        $entries = $vehicle->fuelEntries()
            ->orderBy('refilled_at', 'desc')
            ->limit(30)
            ->get();

        // Shubhali holatlar
        $alerts = $vehicle->fuelAlerts()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'report' => [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->name,
                'plate_number' => $vehicle->plate_number,
                'distance_traveled_km' => $distance,
                'expected_rate_l_km' => $expectedRate,
                'expected_consumed_liters' => $expectedConsumed,
                'actual_rate_l_km' => $actualRate,
                'total_refilled_liters' => $totalRefilled,
                'current_fuel_liters' => $currentFuel,
                'current_fuel_percent' => $percent,
                'fuel_status' => $fuelStatus,
                'warning_message' => $warningMessage,
                'trust_score' => $vehicle->trust_score,
                'average_difference_percent' => $vehicle->average_difference,
                'suspicious_events_count' => $alerts->count(),
            ],
            'fuel_entries' => $entries,
            'fuel_alerts' => $alerts
        ]);
    }

    /**
     * Mobil ilovadan yoqilg'i quyish miqdorini kiritish (refill).
     */
    public function storeFuelEntry(Request $request, $id)
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

        $fuelAmount = floatval($request->fuel_amount);
        $currentFuel = floatval($vehicle->current_fuel_level);
        $capacity = floatval($vehicle->fuel_capacity);
        
        $overflowMargin = $capacity * 1.15;
        $isOverflow = ($currentFuel + $fuelAmount) > $overflowMargin;
        
        // Discrepancy (katta farq bilan to'xtab salyarka solish holatini tekshirish)
        $maxPossibleRefill = max(0.0, $capacity - $currentFuel);
        $isDiscrepancy = $fuelAmount > ($maxPossibleRefill + 10.0); // 10 Litr marja bilan

        $warning = null;
        if ($isOverflow) {
            $warning = "Diqqat! Tizim hisobi bo'yicha bakda taxminan " . round($currentFuel, 1) . " litr yoqilg'i qolgan bo'lishi kerak edi. Kiritilgan miqdor bak sig'imidan oshib ketmoqda (Sig'im: {$capacity}L, Quyildi: {$fuelAmount}L). Traktorchi yoqilg'ini asossiz sarflagan (o'g'irlagan) bo'lishi mumkin!";
            
            \App\Models\FuelAlert::create([
                'vehicle_id' => $vehicle->id,
                'type' => 'overflow',
                'severity' => 'medium',
                'description' => "Bak sig'imidan ortiqcha yoqilg'i quyildi. Sig'im: {$capacity}L, Tizim bo'yicha qoldiq: {$currentFuel}L, Quyildi: {$fuelAmount}L.",
                'calculated_fuel_before' => $currentFuel,
                'refilled_amount' => $fuelAmount,
                'status' => 'pending_check',
            ]);
        } elseif ($isDiscrepancy) {
            $warning = "Diqqat! Tizim hisobi bo'yicha bakda taxminan " . round($currentFuel, 1) . " litr yoqilg'i qolgan bo'lishi kerak edi. Kiritilgan miqdor kutilganidan ancha ko'p (Sig'im: {$capacity}L, Quyildi: {$fuelAmount}L). Iltimos, traktorchi tomonidan yoqilg'i sarflanishini tekshiring.";
            
            \App\Models\FuelAlert::create([
                'vehicle_id' => $vehicle->id,
                'type' => 'discrepancy',
                'severity' => 'low',
                'description' => "Kutilganidan ko'p yoqilg'i quyildi. Taxminan yana {$currentFuel}L yoqilg'i qolgan bo'lishi kerak edi (Sig'im: {$capacity}L, Quyildi: {$fuelAmount}L).",
                'calculated_fuel_before' => $currentFuel,
                'refilled_amount' => $fuelAmount,
                'status' => 'pending_check',
            ]);
        }

        if ($warning) {
            \App\Models\Alert::create([
                'vehicle_id' => $vehicle->id,
                'farm_id' => $vehicle->farm_id,
                'type' => 'fuel_discrepancy',
                'message' => $warning,
                'status' => 'active',
                'triggered_at' => now(),
            ]);
        }

        // Tizimdagi yoqilg'i darajasini oshirish
        $newLevel = min($currentFuel + $fuelAmount, $capacity);
        $vehicle->update([
            'current_fuel_level' => $newLevel,
            'distance_since_empty' => 0.0,
        ]);

        $entry = \App\Models\FuelEntry::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'fuel_amount' => $fuelAmount,
            'refilled_at' => $request->refilled_at ?? now(),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Yoqilg\'i quyish miqdori muvaffaqiyatli saqlandi.',
            'warning' => $warning,
            'entry' => $entry
        ], 201);
    }

    /**
     * Shubhali yoqilg'i ogohlantirishini tasdiqlash yoki rad etish (Calibration).
     */
    public function resolveFuelAlert(Request $request, $id, $alertId)
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

        $alert = \App\Models\FuelAlert::where('vehicle_id', $vehicle->id)->find($alertId);
        if (!$alert) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shubhali holat topilmadi.'
            ], 404);
        }

        $request->validate([
            'status' => 'required|string|in:confirmed,rejected'
        ]);

        $status = $request->status;
        $alert->update(['status' => $status]);

        // Moving-average kalibrlash (admin tasdiqlasa, me'yoriy sarflarni 5% ga oshiramiz)
        if ($status === 'confirmed') {
            $road = floatval($vehicle->nominal_rate_road) * 1.05;
            $light = floatval($vehicle->nominal_rate_work_light) * 1.05;
            $heavy = floatval($vehicle->nominal_rate_work_heavy) * 1.05;
            
            $vehicle->update([
                'nominal_rate_road' => round($road, 2),
                'nominal_rate_work_light' => round($light, 2),
                'nominal_rate_work_heavy' => round($heavy, 2)
            ]);

            \App\Models\Alert::create([
                'vehicle_id' => $vehicle->id,
                'farm_id' => $vehicle->farm_id,
                'type' => 'system_calibration',
                'message' => "Tizim o'rganish natijasi: {$vehicle->name} uchun yoqilg'i me'yorlari kalibrlandi (Road: {$vehicle->nominal_rate_road}L, Light: {$vehicle->nominal_rate_work_light}L, Heavy: {$vehicle->nominal_rate_work_heavy}L).",
                'status' => 'resolved',
                'triggered_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $status === 'confirmed' 
                ? 'Shubhali holat tasdiqlandi va tizim me\'yorlari kalibrlandi.' 
                : 'Shubhali holat rad etildi.',
            'alert' => $alert,
            'vehicle' => $vehicle
        ]);
    }
}
