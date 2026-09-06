<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPlanItem extends Model
{
    protected $fillable = [
        'billing_plan_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class, 'billing_plan_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }
}
