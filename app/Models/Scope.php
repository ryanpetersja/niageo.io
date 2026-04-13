<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scope extends Model
{
    use LogsActivity;

    protected $fillable = [
        'scope_number',
        'client_id',
        'created_by',
        'invoice_id',
        'title',
        'description',
        'status',
        'sections',
        'currency',
        'notes',
        'internal_notes',
        'sent_at',
        'approved_at',
    ];

    protected $casts = [
        'sections' => 'array',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ScopeItem::class)->orderBy('sort_order');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'approved' => 'green',
            'archived' => 'yellow',
            default => 'gray',
        };
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->items()->sum('price');
    }

    public function getMandatoryTotalAttribute(): float
    {
        return (float) $this->items()->where('is_mandatory', true)->sum('price');
    }

    public function getCurrencySymbolAttribute(): string
    {
        return match ($this->currency) {
            'USD' => '$',
            'CAD' => 'CA$',
            'GBP' => '£',
            'EUR' => '€',
            'JMD' => 'J$',
            default => '$',
        };
    }

    public static function generateScopeNumber(): string
    {
        $prefix = 'SCP-' . now()->format('Ym') . '-';
        $last = static::where('scope_number', 'like', $prefix . '%')
            ->orderBy('scope_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->scope_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (Scope $scope) {
            if (empty($scope->scope_number)) {
                $scope->scope_number = static::generateScopeNumber();
            }
        });
    }
}
