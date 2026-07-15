<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    protected $fillable = [
        'farm_id',
        'name',
        'type',
        'plate_number',
        'gps_device_id',
        'sim_number',
        'fuel_capacity',
        'is_blocked',
        'nominal_rate_road',
        'nominal_rate_work_light',
        'nominal_rate_work_heavy',
        'current_fuel_level',
        'distance_since_empty',
    ];

    protected function casts(): array
    {
        return [
            'fuel_capacity' => 'decimal:2',
            'is_blocked' => 'boolean',
            'nominal_rate_road' => 'decimal:2',
            'nominal_rate_work_light' => 'decimal:2',
            'nominal_rate_work_heavy' => 'decimal:2',
            'current_fuel_level' => 'decimal:2',
            'distance_since_empty' => 'decimal:2',
        ];
    }

    /**
     * Texnika biriktirilgan ferma.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Texnikaning barcha GPS harakatlari tarixi.
     */
    public function gpsTracks(): HasMany
    {
        return $this->hasMany(GpsTrack::class);
    }

    /**
     * Texnikaning oxirgi GPS harakati (telemetriyasi).
     */
    public function latestGpsTrack(): HasOne
    {
        return $this->hasOne(GpsTrack::class)->latestOfMany('recorded_at');
    }

    /**
     * Texnika bilan bog'liq faol ogohlantirishlar.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
    }

    public function fuelAlerts(): HasMany
    {
        return $this->hasMany(FuelAlert::class);
    }

    /**
     * Texnika uchun yoqilg'i kiritish tarixi.
     */
    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
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
     * Berilgan vaqt oralig'ida yoki jami bosib o'tgan masofani hisoblash (km)
     */
    public function getDistanceTraveled($startDate = null, $endDate = null)
    {
        $query = $this->gpsTracks();
        if ($startDate) {
            $query->where('recorded_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('recorded_at', '<=', $endDate);
        }
        
        $tracks = $query->orderBy('recorded_at', 'asc')->select('latitude', 'longitude')->get();
        
        $totalDistance = 0.0;
        $count = $tracks->count();
        if ($count < 2) {
            return 0.0;
        }
        
        for ($i = 0; $i < $count - 1; $i++) {
            $totalDistance += $this->calculateDistance(
                $tracks[$i]->latitude, $tracks[$i]->longitude,
                $tracks[$i+1]->latitude, $tracks[$i+1]->longitude
            );
        }
        
        return round($totalDistance, 2);
    }

    /**
     * Traktor modeliga qarab 1 km uchun o'rtacha/kutilgan yoqilg'i sarfi (litr)
     */
    public function getExpectedFuelRateAttribute(): float
    {
        $name = strtolower($this->name);
        
        if (str_contains($name, 'belarus') || str_contains($name, 'mtz')) {
            return 0.45; // Belarus 82.1 - taxminan 0.45 litr / km
        }
        if (str_contains($name, 'john deere')) {
            return 0.70; // John Deere - 0.70 litr / km
        }
        if (str_contains($name, 'case') || str_contains($name, 'new holland')) {
            return 0.65; // Case / New Holland - 0.65 litr / km
        }
        if (str_contains($name, 'claas')) {
            return 0.75;
        }
        
        if ($this->type === 'combine') {
            return 1.20; // Kombayn - 1.20 litr / km
        }
        
        return 0.50; // Standart traktor - 0.50 litr / km
    }

    /**
     * Haqiqiy yoqilg'i sarfini hisoblash (litr / km)
     */
    public function getActualFuelRate($startDate = null, $endDate = null): float
    {
        $distance = $this->getDistanceTraveled($startDate, $endDate);
        if ($distance <= 0.1) {
            return 0.0;
        }
        
        $query = $this->fuelEntries();
        if ($startDate) {
            $query->where('refilled_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('refilled_at', '<=', $endDate);
        }
        
        $totalFuel = $query->sum('fuel_amount');
        
        if ($totalFuel <= 0) {
            return 0.0;
        }
        
        return round($totalFuel / $distance, 2);
    }

    /**
     * Texnika uchun shubhali yoqilg'i ogohlantirishlari.
     */
    public function fuelAlerts(): HasMany
    {
        return $this->hasMany(FuelAlert::class);
    }

    public static $TRACTOR_MODELS_CATALOG = [
        'belarus 82' => [
            'road' => 3.5, // Liters per hour
            'light' => 6.0,
            'heavy' => 12.0,
        ],
        'belarus 80' => [
            'road' => 3.2,
            'light' => 5.5,
            'heavy' => 11.0,
        ],
        'mtz' => [
            'road' => 3.5,
            'light' => 6.0,
            'heavy' => 12.0,
        ],
        'john deere 6' => [
            'road' => 5.0,
            'light' => 9.0,
            'heavy' => 18.0,
        ],
        'john deere 7' => [
            'road' => 6.5,
            'light' => 12.0,
            'heavy' => 24.0,
        ],
        'case puma' => [
            'road' => 6.0,
            'light' => 11.0,
            'heavy' => 22.0,
        ],
        'new holland td5' => [
            'road' => 4.2,
            'light' => 7.5,
            'heavy' => 15.0,
        ],
        'claas axion' => [
            'road' => 7.0,
            'light' => 13.0,
            'heavy' => 26.0,
        ],
        'ttz' => [
            'road' => 3.0,
            'light' => 5.0,
            'heavy' => 10.0,
        ],
    ];

    /**
     * Traktor nomi bo'yicha katalogni tekshirish va mos keladigan default me'yorlarni qaytarish
     */
    public static function getNominalRatesForName(string $name): array
    {
        $lowercaseName = strtolower($name);
        foreach (self::$TRACTOR_MODELS_CATALOG as $key => $rates) {
            if (str_contains($lowercaseName, $key)) {
                return $rates;
            }
        }
        
        // Standart Belarus 82 me'yorlari
        return [
            'road' => 3.5,
            'light' => 6.0,
            'heavy' => 12.0,
        ];
    }

    /**
     * Fermerning ushbu texnika bo'yicha ishonch reytingi (Trust Score)
     */
    public function getTrustScoreAttribute(): int
    {
        // Tasdiqlangan va ko'rib chiqilayotgan shubhali holatlar sonini olamiz
        $alertsCount = $this->fuelAlerts()
            ->whereIn('status', ['pending_check', 'confirmed'])
            ->count();

        // Har bir shubhali holat uchun reyting 15% ga pasayadi (min: 20%)
        return max(100 - ($alertsCount * 15), 20);
    }

    /**
     * Tizim hisoblagan va kiritilgan yoqilg'i o'rtasidagi o'rtacha farq (%)
     */
    public function getAverageDifferenceAttribute(): float
    {
        $alerts = $this->fuelAlerts()
            ->whereIn('type', ['overflow', 'discrepancy'])
            ->get();

        if ($alerts->isEmpty()) {
            return 0.0;
        }

        $totalDiff = 0.0;
        foreach ($alerts as $alert) {
            $capacity = floatval($this->fuel_capacity) ?: 1.0;
            $calculated = floatval($alert->calculated_fuel_before);
            $refilled = floatval($alert->refilled_amount);
            
            // Expected max refill
            $expectedMax = max(0.0, $capacity - $calculated);
            if ($expectedMax > 0) {
                $totalDiff += abs(($refilled - $expectedMax) / $capacity) * 100;
            } else {
                $totalDiff += ($refilled / $capacity) * 100;
            }
        }

        return round($totalDiff / $alerts->count(), 1);
    }

    /**
     * Texnikaning joriy holatini aniqlash.
     * So'nggi 5 daqiqada koordinatalar kelgan bo'lsa - online.
     */
    public function getStatusAttribute(): string
    {
        $latest = $this->latestGpsTrack;

        if (!$latest) {
            return 'offline';
        }

        // Agar oxirgi signal 5 daqiqadan oshgan bo'lsa, uni offline deb hisoblaymiz
        if ($latest->recorded_at->diffInMinutes(now()) > 5) {
            return 'offline';
        }

        // Agar yoqilg'i darajasi 15% dan past bo'lsa, warning
        if ($latest->fuel_level < 15.00) {
            return 'problem';
        }

        return 'online';
    }
}
