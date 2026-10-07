<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An AI review of one or more pull requests in a client's repository, written for the person
 * deploying: what to merge, what each PR does, and what the standard deploy script won't cover.
 */
class CodeReview extends Model
{
    use LogsActivity;

    public const RECOMMENDATIONS = [
        'merge' => 'Merge',
        'merge_with_caution' => 'Merge with caution',
        'hold' => 'Hold',
    ];

    protected $fillable = [
        'client_id',
        'client_repository_id',
        'created_by',
        'title',
        'pull_requests',
        'sections',
        'model',
    ];

    protected $casts = [
        'pull_requests' => 'array',
        'sections' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(ClientRepository::class, 'client_repository_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The per-PR verdict from the review, keyed by PR number. */
    public function verdicts(): array
    {
        $out = [];
        foreach ((array) ($this->sections['pull_requests'] ?? []) as $pr) {
            if (is_array($pr) && isset($pr['number'])) {
                $out[(int) $pr['number']] = $pr;
            }
        }

        return $out;
    }
}
