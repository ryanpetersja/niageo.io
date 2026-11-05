<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * One build of the mobile app (an Android APK), stored on the local disk and served
 * from the Mobile app page or through a short-lived signed link for phones.
 */
class AppRelease extends Model
{
    public const PLATFORM_ANDROID = 'android';

    protected $fillable = [
        'platform',
        'version',
        'build_number',
        'file_path',
        'file_name',
        'file_size',
        'sha256',
        'git_sha',
        'notes',
        'uploaded_by',
        'released_at',
    ];

    protected $casts = [
        'build_number' => 'integer',
        'file_size' => 'integer',
        'released_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /** The newest build for a platform (highest build number, then most recent). */
    public static function latestFor(string $platform = self::PLATFORM_ANDROID): ?self
    {
        return static::forPlatform($platform)->orderByDesc('build_number')->orderByDesc('id')->first();
    }

    public function getDisplayVersionAttribute(): string
    {
        return "{$this->version} (build {$this->build_number})";
    }

    public function getSizeLabelAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return number_format(max(1, $bytes / 1024)) . ' KB';
    }

    public function getShortShaAttribute(): ?string
    {
        return $this->git_sha ? substr($this->git_sha, 0, 7) : null;
    }

    /** A download link that works without being signed in (for the phone's browser). */
    public function signedDownloadUrl(int $minutes = 120): string
    {
        return URL::temporarySignedRoute('app.releases.download', now()->addMinutes($minutes), ['release' => $this->id]);
    }

    public function toApi(bool $withDownloadUrl = true): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'version' => $this->version,
            'build_number' => $this->build_number,
            'display_version' => $this->display_version,
            'released_at' => $this->released_at?->toIso8601String(),
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'size_label' => $this->size_label,
            'sha256' => $this->sha256,
            'git_sha' => $this->git_sha,
            'notes' => $this->notes,
            'download_url' => $withDownloadUrl ? $this->signedDownloadUrl() : null,
        ];
    }
}
