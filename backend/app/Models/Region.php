<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'geojson',
    ];

    protected function casts(): array
    {
        return [
            'geojson' => 'array',
        ];
    }

    /**
     * Ota hudud (masalan, Tuman uchun Viloyat).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    /**
     * Bola hududlar (masalan, Viloyat ichidagi Tumanlar).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    /**
     * Ushbu hududda ro'yxatdan o'tgan foydalanuvchilar.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Ushbu hududdagi fermer xo'jaliklari.
     */
    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }
}
