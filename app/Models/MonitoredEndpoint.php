<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredEndpoint extends Model
{
    use LogsActivity;

    protected $fillable = [
        'client_id',
        'name',
        'url',
        'check_interval_minutes',
        'timeout_seconds',
        'degraded_threshold_ms',
        'current_status',
        'last_checked_at',
        'last_response_time_ms',
        'last_status_code',
        'last_error_message',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'check_interval_minutes' => 'integer',
        'timeout_seconds' => 'integer',
        'degraded_threshold_ms' => 'integer',
        'last_response_time_ms' => 'integer',
        'last_status_code' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uptimeChecks(): HasMany
    {
        return $this->hasMany(UptimeCheck::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->current_status) {
            'up' => 'green',
            'degraded' => 'yellow',
            'down' => 'red',
            default => 'gray',
        };
    }
}
