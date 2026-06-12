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

        $history = $vehicle->gpsTracks()
            ->where('recorded_at', '>=', Carbon::now()->subHours(24))
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
}
