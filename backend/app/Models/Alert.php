<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'vehicle_id',
        'farm_id',
        'type',
        'message',
        'status',
        'triggered_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Signalga sabab bo'lgan texnika.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Signal yuz bergan ferma.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Faol signallarni filtrlash uchun local scope.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Hal qilingan signallarni filtrlash uchun local scope.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
