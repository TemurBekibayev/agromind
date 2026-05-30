<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SoilAnalysis extends Model
{
    // Laraveldagi modelning jadval nomi (ko'plikda bo'lgani uchun avtomatik topiladi, lekin aniqlik uchun yozamiz)
    protected $table = 'soil_analyses';

    protected $fillable = [
        'farm_id',
        'geofence_id',
        'target_crop',
        'ph',
        'fertility',
        'moisture',
        'temperature',
        'sunlight',
        'humidity',
        'analysis_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ph' => 'decimal:2',
            'fertility' => 'decimal:2',
            'moisture' => 'decimal:2',
            'temperature' => 'decimal:2',
            'sunlight' => 'decimal:2',
            'humidity' => 'decimal:2',
            'analysis_date' => 'date',
        ];
    }

    /**
     * Tahlil qilingan ferma.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Tahlil qilingan aniq yer maydoni (geofence).
     */
    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }

    /**
     * Tahlil asosida olingan sun'iy intellekt tavsiyasi.
     */
    public function recommendation(): HasOne
    {
        return $this->hasOne(Recommendation::class);
    }
}
