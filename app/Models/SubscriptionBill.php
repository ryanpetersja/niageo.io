<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SubscriptionBill extends Model
{
    use LogsActivity;

    protected $fillable = [
        'service_name',
        'category',
        'description',
        'amount',
        'billing_cycle',
        'next_due_date',
        'status',
        'last_paid_at',
        'is_active',
        'auto_renew',
        'url',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'next_due_date' => 'date',
        'last_paid_at' => 'datetime',
        'is_active' => 'boolean',
        'auto_renew' => 'boolean',
    ];

    public const CATEGORIES = [
        'hosting' => 'Hosting',
        'email' => 'Email',
        'devops' => 'DevOps',
        'workspace' => 'Workspace',
        'domain' => 'Domain',
        'monitoring' => 'Monitoring',
        'other' => 'Other',
    ];

    public const BILLING_CYCLES = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'annual' => 'Annual',
    ];

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'green',
            'upcoming' => 'blue',
            'due_soon' => 'yellow',
            'overdue' => 'red',
            default => 'gray',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function getCycleLabelAttribute(): string
    {
        return self::BILLING_CYCLES[$this->billing_cycle] ?? ucfirst($this->billing_cycle);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->is_active && $this->status !== 'paid' && $this->next_due_date->isPast();
    }

    public function getIsDueSoonAttribute(): bool
    {
        return $this->is_active
            && $this->status !== 'paid'
            && !$this->next_due_date->isPast()
            && $this->next_due_date->diffInDays(now()) <= 7;
    }

    public function markAsPaid(): void
    {
        $this->last_paid_at = now();
        $this->status = 'paid';
        $this->save();
    }

    public function advanceBillingCycle(): void
    {
        $this->next_due_date = match ($this->billing_cycle) {
            'monthly' => $this->next_due_date->addMonth(),
            'quarterly' => $this->next_due_date->addMonths(3),
            'annual' => $this->next_due_date->addYear(),
            default => $this->next_due_date->addMonth(),
        };
        $this->status = 'upcoming';
        $this->save();
    }

    public function refreshStatus(): void
    {
        if ($this->status === 'paid') {
            return;
        }

        if ($this->next_due_date->isPast()) {
            $this->status = 'overdue';
        } elseif ($this->next_due_date->diffInDays(now()) <= 7) {
            $this->status = 'due_soon';
        } else {
            $this->status = 'upcoming';
        }

        $this->saveQuietly();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeDueSoon($query)
    {
        return $query->where('status', 'due_soon');
    }
}
