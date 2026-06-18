<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'location',
        'latitude',
        'longitude',
        'size',
        'soil_type',
        'region_id',
        'district',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'double',
            'longitude' => 'double',
            'size' => 'decimal:2',
        ];
    }

    /**
     * Fermer (ega).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Joylashgan hududi.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Tuproq tahlillari tarixi.
     */
    public function soilAnalyses(): HasMany
    {
        return $this->hasMany(SoilAnalysis::class);
    }

    /**
     * Fermadagi texnikalar (traktor, kombayn va h.k.).
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Fermaning geodezik chegaralari (geofence).
     */
    public function geofences(): HasMany
    {
        return $this->hasMany(Geofence::class);
    }

    /**
     * Ferma bilan bog'liq xavfsizlik va ogohlantirish signallari.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Suv limitlari va amalda sarflangan suv hajmlari.
     */
    public function waterRecords(): HasMany
    {
        return $this->hasMany(WaterRecord::class);
    }
}
