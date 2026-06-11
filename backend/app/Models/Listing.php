<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'equipment_type',
        'price',
        'contact_phone',
        'status',
    ];

    /**
     * E'lon bergan foydalanuvchi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Faol e'lonlarni filtrlash.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
