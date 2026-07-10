<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelAlert extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'severity',
        'description',
        'calculated_fuel_before',
        'refilled_amount',
        'distance_traveled',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'calculated_fuel_before' => 'decimal:2',
            'refilled_amount' => 'decimal:2',
            'distance_traveled' => 'decimal:2',
        ];
    }

    /**
     * Alert belongs to a vehicle.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
