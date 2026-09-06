<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A client's recurring package: the line items billed every period
 * (monthly or quarterly) and the schedule used to generate invoices.
 */
class BillingPlan extends Model
{
    use LogsActivity;

    /** Billing cycle => months per period. */
    public const CYCLES = [
        'monthly' => 1,
        'quarterly' => 3,
    ];

    public const STATUSES = ['active', 'paused', 'ended'];

    public const DEFAULT_TITLE_TEMPLATE = '{plan} — {period}';

    protected $fillable = [
        'client_id',
        'created_by',
        'name',
        'billing_cycle',
        'starts_on',
        'ends_on',
        'next_period_start',
        'issue_days_before',
        'due_days',
        'tax_rate',
        'title_template',
        'notes',
        'internal_notes',
        'status',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'next_period_start' => 'date',
        'issue_days_before' => 'integer',
        'due_days' => 'integer',
        'tax_rate' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingPlanItem::class)->orderBy('sort_order');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('period_start', 'desc');
    }

    public function getMonthsPerPeriodAttribute(): int
    {
        return self::CYCLES[$this->billing_cycle] ?? 1;
    }

    public function getCycleLabelAttribute(): string
    {
        return ucfirst($this->billing_cycle);
    }

    /** Subtotal billed each period (before tax). */
    public function getPeriodSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_price);
    }

    /** Total billed each period including tax. */
    public function getPeriodTotalAttribute(): float
    {
        $subtotal = $this->period_subtotal;

        return round($subtotal + $subtotal * ((float) $this->tax_rate / 100), 2);
    }

    /** Period subtotal normalised to one month, for recurring-revenue summaries. */
    public function getMonthlyValueAttribute(): float
    {
        return round($this->period_subtotal / $this->months_per_period, 2);
    }
}
