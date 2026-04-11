<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use LogsActivity;

    protected $fillable = [
        'report_number',
        'client_id',
        'created_by',
        'title',
        'date_from',
        'date_to',
        'status',
        'raw_commits',
        'ai_summary',
        'raw_server_activity',
        'server_summary',
        'commit_count',
        'repo_count',
        'server_count',
        'invoice_id',
        'uptime_score',
        'service_snapshot',
        'notes',
        'internal_notes',
        'generated_at',
        'sent_at',
        'sent_to_email',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'raw_commits' => 'array',
        'ai_summary' => 'array',
        'raw_server_activity' => 'array',
        'server_summary' => 'array',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'commit_count' => 'integer',
        'repo_count' => 'integer',
        'server_count' => 'integer',
        'uptime_score' => 'decimal:2',
        'service_snapshot' => 'array',
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

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(ReportFeedback::class)->orderBy('created_at', 'desc');
    }

    public function getHasSummaryAttribute(): bool
    {
        return !empty($this->ai_summary);
    }

    public function getHasServerSummaryAttribute(): bool
    {
        return !empty($this->server_summary);
    }

    public function getSummaryItemCountAttribute(): int
    {
        $count = 0;
        $categories = ['features', 'bugs', 'improvements', 'security', 'infrastructure'];

        if ($this->ai_summary) {
            foreach ($categories as $key) {
                if (isset($this->ai_summary[$key]) && is_array($this->ai_summary[$key])) {
                    $count += count($this->ai_summary[$key]);
                }
            }
        }

        if ($this->server_summary) {
            foreach ($categories as $key) {
                if (isset($this->server_summary[$key]) && is_array($this->server_summary[$key])) {
                    $count += count($this->server_summary[$key]);
                }
            }
        }

        return $count;
    }

    public static function generateReportNumber(): string
    {
        $prefix = 'RPT-' . now()->format('Ym') . '-';
        $lastReport = static::where('report_number', 'like', $prefix . '%')
            ->orderBy('report_number', 'desc')
            ->first();

        if ($lastReport) {
            $lastNumber = (int) substr($lastReport->report_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
