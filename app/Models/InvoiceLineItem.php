<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceLineItem $item) {
            $item->total = round($item->quantity * $item->unit_price, 2);
        });

        static::saved(function (InvoiceLineItem $item) {
            $item->invoice->recalculateTotals();
        });

        static::deleted(function (InvoiceLineItem $item) {
            $item->invoice->recalculateTotals();
        });
    }
}
