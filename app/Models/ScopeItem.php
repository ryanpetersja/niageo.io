<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopeItem extends Model
{
    protected $fillable = [
        'scope_id',
        'title',
        'description',
        'category',
        'price',
        'is_mandatory',
        'is_optional',
        'is_recommended',
        'sort_order',
        'business_value_statement',
        'effort_description',
        'deliverable_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_optional' => 'boolean',
        'is_recommended' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scope(): BelongsTo
    {
        return $this->belongsTo(Scope::class);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    public function scopeRecommended($query)
    {
        return $query->where('is_recommended', true);
    }
}
