<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Mass assignable bo'lgan maydonlar ro'yxati.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'role',
        'region_id',
        'district',
        'password',
    ];

    /**
     * Serializatsiya paytida yashiriladigan maydonlar.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast qilinadigan maydonlar.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Foydalanuvchi biriktirilgan hudud (tuman/viloyat).
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Dehqon/Fermerning xo'jaliklari.
     */
    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    /**
     * Foydalanuvchi yuborgan chat xabarlari.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Foydalanuvchi e'lon qilgan texnikalar ro'yxati.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * Helper: Foydalanuvchi admin ekanligini tekshirish.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Helper: Foydalanuvchi monitor ekanligini tekshirish.
     */
    public function isMonitor(): bool
    {
        return $this->role === 'monitor';
    }

    /**
     * Helper: Foydalanuvchi dehqon ekanligini tekshirish.
     */
    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }
}
