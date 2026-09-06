<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per Claude API call, with token counts and the estimated cost.
 */
class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'model',
        'input_tokens',
        'cache_read_tokens',
        'cache_write_tokens',
        'output_tokens',
        'estimated_cost',
        'price_known',
        'duration_ms',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'cache_read_tokens' => 'integer',
        'cache_write_tokens' => 'integer',
        'output_tokens' => 'integer',
        'estimated_cost' => 'float',
        'price_known' => 'boolean',
        'duration_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTotalTokensAttribute(): int
    {
        return $this->input_tokens + $this->cache_read_tokens + $this->cache_write_tokens + $this->output_tokens;
    }
}
