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
        'summary_type',
        'category',
        'item_index',
        'item_text',
        'proposed_summary',
        'resolution',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'proposed_summary' => 'array',
        'item_index' => 'integer',
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
