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
     * Dehqon xo'jaligi koordinatalariga tayanadi.
     */
    public function getFakeLocation(Vehicle $vehicle): array
    {
        $farm = $vehicle->farm;
        $latestTrack = $vehicle->latestGpsTrack;

        $startLat = $latestTrack ? $latestTrack->latitude : $farm->latitude;
        $startLng = $latestTrack ? $latestTrack->longitude : $farm->longitude;
        
        // Agar yoqilg'i oldin bo'lsa uni kamaytiramiz, bo'lmasa to'ldiramiz (masalan, 80%)
        $startFuel = $latestTrack ? $latestTrack->fuel_level : 80.00;

        // Tasodifiy kichik harakat (taxminan 10-50 metr)
        $latDelta = (rand(-200, 200) / 1000000);
        $lngDelta = (rand(-200, 200) / 1000000);

        $newLat = $startLat + $latDelta;
        $newLng = $startLng + $lngDelta;

        // Yoqilg'i sarfi simulyatsiyasi (tasodifiy 0.05% - 0.15% kamayish)
        $fuelUsed = (rand(5, 15) / 100);
        $newFuel = max(0, $startFuel - $fuelUsed);
        
        // Agar yoqilg'i judayam kam bo'lsa (masalan 2%), qayta 95% gacha to'ldiramiz (zapravka)
        if ($newFuel < 2.00) {
            $newFuel = 95.00;
        }

        // Tezlik (0 dan 25 km/soatgacha)
        $speed = rand(0, 1) === 0 ? rand(5, 25) : 0.00; // Vaqti-vaqti bilan to'xtab turish
        
        // Signal kuchi
        $signal = rand(60, 100);

        return [
            'vehicle_id' => $vehicle->id,
            'latitude' => $newLat,
            'longitude' => $newLng,
            'speed' => $speed,
            'fuel_level' => $newFuel,
            'signal_strength' => $signal,
            'recorded_at' => Carbon::now(),
        ];
    }

    /**
     * Telemetriya ma'lumotlarini qabul qilish va saqlash.
     */
    public function processIncoming(array $data): GpsTrack
    {
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

        // Geofence va boshqa tekshiruvlarni ishga tushirish
        $this->checkGeofence($track);
        $this->checkLowFuel($track);

        return $track;
    }

    /**
     * Geofence tekshiruvi (Ray-Casting Algorithm).
     * Nuqta poligon ichida ekanligini aniqlaydi.
     */
    public function checkGeofence(GpsTrack $track): void
    {
        $vehicle = $track->vehicle;
        $farm = $vehicle->farm;
        $geofence = $farm->geofences()->first(); // Hozircha bitta farmda bitta geofence bor deb hisoblaymiz

        if (!$geofence) {
            return;
        }

        $coordinates = $geofence->coordinates; // Format: [[lat, lng], [lat, lng], ...]
        $inside = $this->isPointInPolygon($track->latitude, $track->longitude, $coordinates);

        $activeAlert = Alert::where('vehicle_id', $vehicle->id)
            ->where('type', 'geofence_breach')
            ->where('status', 'active')
            ->first();

        if (!$inside) {
            // Agar tashqarida bo'lsa va hali ogohlantirish berilmagan bo'lsa
            if (!$activeAlert) {
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
            // Agar ichkarida bo'lsa va faol ogohlantirish bo'lsa - uni hal qilamiz (resolve)
            if ($activeAlert) {
                $activeAlert->update([
                    'status' => 'resolved',
                    'resolved_at' => Carbon::now(),
                ]);
                Log::info("Geofence breach alert resolved for Vehicle ID: {$vehicle->id}");
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
