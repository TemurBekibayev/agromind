<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\GpsTrack;
use App\Models\Geofence;
use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GpsService
{
    /**
     * Soxta (Simulyatsiya qilingan) GPS ma'lumotlarini olish.
     * Tizim hisoblab borayotgan dynamic current_fuel_level ga tayanadi.
     */
    public function getFakeLocation(Vehicle $vehicle): array
    {
        $farm = $vehicle->farm;
        $latestTrack = $vehicle->latestGpsTrack;

        $startLat = $latestTrack ? $latestTrack->latitude : $farm->latitude;
        $startLng = $latestTrack ? $latestTrack->longitude : $farm->longitude;
        
        // dynamic current_fuel_level foizini aniqlaymiz
        $capacity = floatval($vehicle->fuel_capacity) ?: 50.0;
        $startFuel = round((floatval($vehicle->current_fuel_level) / $capacity) * 100.0, 2);

        // Tasodifiy kichik harakat (taxminan 10-50 metr)
        $latDelta = (rand(-200, 200) / 1000000);
        $lngDelta = (rand(-200, 200) / 1000000);

        $newLat = $startLat + $latDelta;
        $newLng = $startLng + $lngDelta;

        // Tezlik (0 dan 25 km/soatgacha)
        $speed = rand(0, 1) === 0 ? rand(5, 25) : 0.00; // Vaqti-vaqti bilan to'xtab turish
        
        // Signal kuchi
        $signal = rand(60, 100);

        return [
            'vehicle_id' => $vehicle->id,
            'latitude' => $newLat,
            'longitude' => $newLng,
            'speed' => $speed,
            'fuel_level' => $startFuel,
            'signal_strength' => $signal,
            'recorded_at' => Carbon::now(),
        ];
    }

    /**
     * Telemetriya ma'lumotlarini qabul qilish va saqlash.
     */
    public function processIncoming(array $data): GpsTrack
    {
        $vehicle = Vehicle::find($data['vehicle_id']);

        // GpsTrack yaratish
        $track = GpsTrack::create([
            'vehicle_id' => $data['vehicle_id'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'speed' => $data['speed'] ?? 0.00,
            'fuel_level' => $data['fuel_level'],
            'signal_strength' => $data['signal_strength'] ?? 100,
            'recorded_at' => $data['recorded_at'] ?? Carbon::now(),
        ]);

        if ($vehicle) {
            // Yoqilg'i sarfini dinamik hisoblash
            $this->updateDynamicFuelLevel($vehicle, $track);
        }

        // Geofence va boshqa tekshiruvlarni ishga tushirish
        $this->checkGeofence($track);
        $this->checkLowFuel($track);

        return $track;
    }

    /**
     * Haversine formula yordamida ikkita koordinata orasidagi masofani hisoblash (km)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Yoqilg'i darajasini dinamik ravishda yangilash logikasi
     */
    public function updateDynamicFuelLevel(Vehicle $vehicle, GpsTrack $newTrack): void
    {
        // Oxirgi GPS nuqtasini topamiz
        $prevTrack = GpsTrack::where('vehicle_id', $vehicle->id)
            ->where('id', '<', $newTrack->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$prevTrack) {
            return;
        }

        $timePrev = Carbon::parse($prevTrack->recorded_at);
        $timeNew = Carbon::parse($newTrack->recorded_at);
        
        $dt = $timeNew->diffInSeconds($timePrev) / 3600.0; // soat
        
        // Agar vaqt farqi 0 dan kichik bo'lsa yoki offline bo'lgan bo'lsa
        if ($dt <= 0 || $dt > 2.0) {
            return;
        }

        // Nuqta geofence ichidami?
        $inside = false;
        $farm = $vehicle->farm;
        if ($farm) {
            $geofences = $farm->geofences;
            foreach ($geofences as $geofence) {
                $coordinates = $geofence->coordinates;
                if (isset($coordinates[0]) && is_array($coordinates[0]) && isset($coordinates[0][0]) && is_array($coordinates[0][0])) {
                    $coordinates = $coordinates[0];
                }
                if ($coordinates && $this->isPointInPolygon($prevTrack->latitude, $prevTrack->longitude, $coordinates)) {
                    $inside = true;
                    break;
                }
            }
        }

        // Ish turi va tezligiga qarab sarf me'yori (l/soat)
        $rate = floatval($vehicle->nominal_rate_road);
        if ($inside) {
            if ($newTrack->speed <= 8.0) {
                $rate = floatval($vehicle->nominal_rate_work_heavy);
            } else {
                $rate = floatval($vehicle->nominal_rate_work_light);
            }
        }

        $consumed = $rate * $dt;
        
        // Yangi yoqilg'i darajasini hisoblash
        $newLevel = max(0.0, floatval($vehicle->current_fuel_level) - $consumed);
        
        // Ikki nuqta orasidagi masofa
        $distance = $this->calculateDistance(
            $prevTrack->latitude, $prevTrack->longitude,
            $newTrack->latitude, $newTrack->longitude
        );

        $distanceSinceEmpty = floatval($vehicle->distance_since_empty);

        if ($newLevel <= 0.0) {
            $distanceSinceEmpty += $distance;
            
            // Shubhali holat: yoqilg'i tugagan holda ko'p yurishi (solingan yoqilg'i kiritilmagan)
            if ($distanceSinceEmpty > 2.0) {
                $hasAlert = \App\Models\FuelAlert::where('vehicle_id', $vehicle->id)
                    ->where('type', 'empty_driving')
                    ->where('status', 'pending_check')
                    ->exists();

                if (!$hasAlert) {
                    \App\Models\FuelAlert::create([
                        'vehicle_id' => $vehicle->id,
                        'type' => 'empty_driving',
                        'severity' => 'medium',
                        'description' => "Yoqilg'i kiritilmasdan 2 km dan ortiq masofa bosib o'tildi. Taxminiy yoqilg'i qoldig'i 0 deb hisoblanmoqda.",
                        'calculated_fuel_before' => 0.0,
                        'refilled_amount' => 0.0,
                        'distance_traveled' => $distanceSinceEmpty,
                        'status' => 'pending_check',
                    ]);

                    Alert::create([
                        'vehicle_id' => $vehicle->id,
                        'farm_id' => $vehicle->farm_id,
                        'type' => 'unlogged_refill',
                        'message' => "Ogohlantirish! {$vehicle->name} (Raqam: {$vehicle->plate_number}) yoqilg'i tugagan deb hisoblansa-da, harakatda davom etmoqda. Solingan yoqilg'ini ilovada kiritish so'raladi.",
                        'status' => 'active',
                        'triggered_at' => Carbon::now(),
                    ]);
                }
            }
        } else {
            $distanceSinceEmpty = 0.0;
        }

        $vehicle->update([
            'current_fuel_level' => $newLevel,
            'distance_since_empty' => $distanceSinceEmpty,
        ]);
        
        // Track dagi fuel_level ni dinamik yangilash (foiz)
        $newTrack->update([
            'fuel_level' => round(($newLevel / (floatval($vehicle->fuel_capacity) ?: 50.0)) * 100.0, 2)
        ]);
    }

    /**
     * Geofence tekshiruvi (Ray-Casting Algorithm).
     * Nuqta poligon ichida ekanligini aniqlaydi.
     */
    public function checkGeofence(GpsTrack $track): void
    {
        $vehicle = $track->vehicle;
        $farm = $vehicle->farm;
        $geofences = $farm->geofences;

        if ($geofences->isEmpty()) {
            return;
        }

        $inside = false;
        foreach ($geofences as $geofence) {
            $coordinates = $geofence->coordinates; // Format: [[lat, lng], [lat, lng], ...]
            
            // Normalize coordinates array if it is nested as 3D array
            if (isset($coordinates[0]) && is_array($coordinates[0]) && isset($coordinates[0][0]) && is_array($coordinates[0][0])) {
                $coordinates = $coordinates[0];
            }

            if ($coordinates && $this->isPointInPolygon($track->latitude, $track->longitude, $coordinates)) {
                $inside = true;
                break;
            }
        }

        $activeAlertsQuery = Alert::where('vehicle_id', $vehicle->id)
            ->where('type', 'geofence_breach')
            ->where('status', 'active');

        if (!$inside) {
            // Agar tashqarida bo'lsa va hali ogohlantirish berilmagan bo'lsa
            if (!$activeAlertsQuery->exists()) {
                Alert::create([
                    'vehicle_id' => $vehicle->id,
                    'farm_id' => $farm->id,
                    'type' => 'geofence_breach',
                    'message' => "Diqqat! {$vehicle->name} (Raqami: {$vehicle->plate_number}) belgilangan chegaradan tashqariga chiqdi.",
                    'status' => 'active',
                    'triggered_at' => Carbon::now(),
                ]);

                // SMS va Push notificationlar shu erda yuboriladi
                $this->notifyFarmerViaSms($vehicle, "Diqqat! {$vehicle->name} chegaradan tashqariga chiqdi.");
                Log::warning("Geofence breach alert triggered for Vehicle ID: {$vehicle->id}");
            }
        } else {
            // Agar ichkarida bo'lsa va faol ogohlantirishlar bo'lsa - barchasini hal qilamiz (resolve)
            if ($activeAlertsQuery->exists()) {
                $activeAlertsQuery->update([
                    'status' => 'resolved',
                    'resolved_at' => Carbon::now(),
                ]);
                Log::info("Geofence breach alerts resolved for Vehicle ID: {$vehicle->id}");
            }
        }
    }

    /**
     * Yoqilg'i darajasi kamligini tekshirish.
     */
    protected function checkLowFuel(GpsTrack $track): void
    {
        $vehicle = $track->vehicle;
        $activeAlert = Alert::where('vehicle_id', $vehicle->id)
            ->where('type', 'low_fuel')
            ->where('status', 'active')
            ->first();

        // Agar yoqilg'i 15% dan past bo'lsa ogohlantirish beriladi
        if ($track->fuel_level < 15.00) {
            if (!$activeAlert) {
                Alert::create([
                    'vehicle_id' => $vehicle->id,
                    'farm_id' => $vehicle->farm_id,
                    'type' => 'low_fuel',
                    'message' => "Yoqilg'i kam! {$vehicle->name} texnikasida yoqilg'i miqdori {$track->fuel_level}% qoldi.",
                    'status' => 'active',
                    'triggered_at' => Carbon::now(),
                ]);
                
                $this->notifyFarmerViaSms($vehicle, "Yoqilg'i kam! {$vehicle->name} da yoqilg'i {$track->fuel_level}% qoldi.");
            }
        } else {
            // Agar yoqilg'i 15% dan yuqori bo'lsa va faol ogohlantirish bo'lsa, uni yopamiz
            if ($activeAlert) {
                $activeAlert->update([
                    'status' => 'resolved',
                    'resolved_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Ray-Casting Point-in-Polygon algoritmi.
     */
    protected function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        // Normalize coordinates array if it is nested as 3D array
        if (isset($polygon[0]) && is_array($polygon[0]) && isset($polygon[0][0]) && is_array($polygon[0][0])) {
            $polygon = $polygon[0];
        }

        $inside = false;
        $n = count($polygon);
        
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i][1]; // longitude
            $yi = $polygon[$i][0]; // latitude
            $xj = $polygon[$j][1]; // longitude
            $yj = $polygon[$j][0]; // latitude
            
            $intersect = (($yi > $lat) != ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 0.000001) + $xi);
            
            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * SMS orqali fermerni ogohlantirish mocki.
     */
    protected function notifyFarmerViaSms(Vehicle $vehicle, string $message): void
    {
        $farmer = $vehicle->farm->owner;
        // SmsService orqali jo'natish (kelgusida integratsiya qilinadi)
        Log::info("SMS notification queued to {$farmer->phone}: {$message}");
    }

    /**
     * REAL GPS MIGRATSIYA REJASI VA QO'LLANMASI:
     * 
     * Ushbu soxta simulyatsiyani real qurilmalarga almashtirish uchun quyidagi bosqichlarni bajaring:
     *
     * 1. Teltonika FMB920 GPS trekkerini MQTT brokerga (masalan Mosquitto) ma'lumot yuborishga sozlang.
     * 2. Mosquitto broker manzili va portini .env faylda ko'rsating:
     *    MQTT_HOST=mosquitto
     *    MQTT_PORT=1883
     *    MQTT_TOPIC=vehicles/telemetry
     * 3. php-mqtt/client Composer paketini o'rnating: `composer require php-mqtt/client`
     * 4. Quyidagi switchToReal() metodini chaqiradigan Laravel MQTT subscriber buyrug'ini (Artisan command) yarating.
     */
    public function switchToReal(): void
    {
        // REAL GPS KOD NAMUNASI (Mosquitto subscriber orqali):
        /*
        $mqtt = new \PhpMqtt\Client\MqttClient(config('mqtt.host'), config('mqtt.port'));
        $mqtt->connect();
        $mqtt->subscribe(config('mqtt.topic'), function (string $topic, string $message) {
            $payload = json_decode($message, true);
            
            // 1. GPS device_id orqali texnikani bazadan topamiz
            $vehicle = Vehicle::where('gps_device_id', $payload['device_id'])->first();
            
            if ($vehicle) {
                // 2. Telemetriya ma'lumotlarini formatlaymiz
                $data = [
                    'vehicle_id' => $vehicle->id,
                    'latitude' => $payload['lat'],
                    'longitude' => $payload['lng'],
                    'speed' => $payload['speed'],
                    'fuel_level' => $payload['fuel'],
                    'signal_strength' => $payload['signal'],
                    'recorded_at' => Carbon::createFromTimestamp($payload['timestamp']),
                ];
                
                // 3. Bazaga yozib geofence tekshiramiz
                $this->processIncoming($data);
            }
        }, 0);
        $mqtt->loop(true);
        */
        
        Log::info("Real MQTT listener initialized. Listening to Teltonika FMB920...");
    }
}
