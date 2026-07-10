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
        'limit_m3',
        'used_m3',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'limit_m3' => 'double',
        'used_m3' => 'double',
    ];

    protected $appends = [
        'remaining_m3',
    ];

    /**
     * Qoldiq suv miqdorini hisoblab beradigan attribute (Limit - Amalda).
     */
    public function getRemainingM3Attribute(): float
    {
        return $this->limit_m3 - $this->used_m3;
    }

    /**
     * Bog'langan fermer xo'jaligi.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
