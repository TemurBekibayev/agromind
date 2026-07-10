<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'sender_name',
        'sender_phone',
        'message',
        'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
    ];

    /**
     * Murojaat qilgan foydalanuvchi (ro'yxatdan o'tgan bo'lsa).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
