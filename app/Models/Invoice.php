<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'created_by',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'amount_paid',
        'notes',
        'internal_notes',
        'pricing_preset_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date', 'desc');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(InvoiceStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function pricingPreset(): BelongsTo
    {
        return $this->belongsTo(PricingPreset::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->lineItems()->sum('total');
        $this->tax_amount = round($this->subtotal * ($this->tax_rate / 100), 2);
        $this->total = $this->subtotal + $this->tax_amount;
        $this->saveQuietly();
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['sent', 'overdue'])
            && $this->due_date->isPast();
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->amount_paid >= $this->total && $this->total > 0;
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';
        $lastInvoice = static::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
