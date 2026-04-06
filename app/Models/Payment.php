<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            $invoice = $payment->invoice;
            $invoice->amount_paid = $invoice->payments()->sum('amount');
            $invoice->saveQuietly();
        });

        static::deleted(function (Payment $payment) {
            $invoice = $payment->invoice;
            $invoice->amount_paid = $invoice->payments()->sum('amount');
            $invoice->saveQuietly();
        });
    }
}
