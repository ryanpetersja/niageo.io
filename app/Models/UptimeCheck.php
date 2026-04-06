<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UptimeCheck extends Model
{
    use LogsActivity;

    protected $fillable = [
        'monitored_endpoint_id',
        'status',
        'response_time_ms',
        'status_code',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'response_time_ms' => 'integer',
        'status_code' => 'integer',
    ];

    public function monitoredEndpoint(): BelongsTo
    {
        return $this->belongsTo(MonitoredEndpoint::class);
    }
}
