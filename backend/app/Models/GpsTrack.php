<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsTrack extends Model
{
    protected $table = 'gps_tracks';

    protected $fillable = [
        'vehicle_id',
        'latitude',
        'longitude',
        'speed',
        'fuel_level',
        'signal_strength',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'double',
            'longitude' => 'double',
            'speed' => 'decimal:2',
            'fuel_level' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Koordinataga bog'langan texnika.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
