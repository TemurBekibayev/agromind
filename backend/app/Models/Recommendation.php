<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    protected $fillable = [
        'soil_analysis_id',
        'content',
        'recommended_crops',
        'fertilizer_plan',
        'ai_model',
        'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'recommended_crops' => 'array',
            'fertilizer_plan' => 'array',
            'tokens_used' => 'integer',
        ];
    }

    /**
     * Bog'liq tuproq tahlili.
     */
    public function soilAnalysis(): BelongsTo
    {
        return $this->belongsTo(SoilAnalysis::class);
    }
}
