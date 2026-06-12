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
     * Texnika uchun rele buyrug'ini yuborish (o'chirish yoki yoqish).
     */
    public function controlRelay(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:cutoff,restore'
        ]);

        $user = $request->user();
        $action = $request->action;

        if ($user->isAdmin()) {
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
                'message' => 'Texnika uchun GPS qurilma ID biriktirilmagan.'
            ], 400);
        }

        // Node.js TCP server HTTP API-siga so'rov jo'natish
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('http://host.docker.internal:5001/send-command', [
                'json' => [
                    'imei' => $gpsDeviceId,
                    'action' => $action
                ],
                'connect_timeout' => 5,
                'timeout' => 15
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $action === 'cutoff' 
                        ? 'Dvigatelni o\'chirish buyrug\'i muvaffaqiyatli yuborildi.' 
                        : 'Dvigatelni yoqish buyrug\'i muvaffaqiyatli yuborildi.',
                    'response' => $result
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Buyruqni yuborishda xatolik yuz berdi.'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'GPS bridge server bilan bog\'lanib bo\'lmadi: ' . $e->getMessage()
            ], 500);
        }
    }
}
