<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportFeedback extends Model
{
    protected $table = 'report_feedback';

    protected $fillable = [
        'report_id',
        'user_id',
        'feedback',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
