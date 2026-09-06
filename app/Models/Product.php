<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalogue entry (hosting, backups, maintenance...) shared across clients.
 * Prices on billing plans override the default here.
 */
class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'unit',
        'default_unit_price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function planItems(): HasMany
    {
        return $this->hasMany(BillingPlanItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
