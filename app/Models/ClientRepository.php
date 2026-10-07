<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientRepository extends Model
{
    use LogsActivity;

    protected $fillable = [
        'client_id',
        'owner',
        'repo_name',
        'default_branch',
        'deploy_script',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function codeReviews(): HasMany
    {
        return $this->hasMany(CodeReview::class)->latest();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->owner}/{$this->repo_name}";
    }
}
