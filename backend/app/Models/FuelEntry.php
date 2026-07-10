<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelEntry extends Model
{
    protected $fillable = [
        'vehicle_id',
        'user_id',
        'fuel_amount',
        'refilled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fuel_amount' => 'decimal:2',
            'refilled_at' => 'datetime',
        ];
    }

    /**
     * Fuel entry belongs to a vehicle.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Fuel entry was recorded by a user (farmer).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
