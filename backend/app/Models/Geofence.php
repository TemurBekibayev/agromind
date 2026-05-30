<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Geofence extends Model
{
    protected $fillable = [
        'farm_id',
        'name',
        'coordinates',
    ];

    protected function casts(): array
    {
        return [
            'coordinates' => 'array', // Array of arrays: [[lat1, lng1], [lat2, lng2], ...]
        ];
    }

    /**
     * Geodezik chegara biriktirilgan ferma.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Yer maydonining barcha tuproq tahlillari.
     */
    public function soilAnalyses(): HasMany
    {
        return $this->hasMany(SoilAnalysis::class);
    }

    /**
     * Yer maydonining oxirgi tuproq tahlili.
     */
    public function latestSoilAnalysis(): HasOne
    {
        return $this->hasOne(SoilAnalysis::class)->latestOfMany();
    }
}
