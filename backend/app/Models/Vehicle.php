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
        'fuel_capacity',
    ];

    protected function casts(): array
    {
        return [
            'fuel_capacity' => 'decimal:2',
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
