<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterRecord extends Model
{
    protected $fillable = [
        'farm_id',
        'year',
        'month',
        'decade',
        'water_source',
        'limit_m3',
        'used_m3',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'decade' => 'integer',
        'limit_m3' => 'double',
        'used_m3' => 'double',
    ];

    /**
     * Bog'langan fermer xo'jaligi.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
