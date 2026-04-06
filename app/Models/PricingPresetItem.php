<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingPresetItem extends Model
{
    protected $fillable = [
        'pricing_preset_id',
        'description',
        'quantity',
        'unit_price',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function preset(): BelongsTo
    {
        return $this->belongsTo(PricingPreset::class, 'pricing_preset_id');
    }

    public function getTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
