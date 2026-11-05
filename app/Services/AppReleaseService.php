<?php

namespace App\Services;

use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores uploaded app builds (from the CI pipeline or the Mobile app page) on the local disk.
 */
class AppReleaseService
{
    public const DISK = 'local';

    public const MAX_SIZE_KB = 204800; // 200 MB

    /**
     * @param  array{version: string, build_number?: int|null, git_sha?: string|null, notes?: string|null, platform?: string}  $meta
     *
     * @throws \InvalidArgumentException when the file is not an APK
     */
    public function store(UploadedFile $file, array $meta, ?User $user = null): AppRelease
    {
        if (strtolower((string) $file->getClientOriginalExtension()) !== 'apk') {
            throw new \InvalidArgumentException('The file must be an Android package (.apk).');
        }

        $platform = $meta['platform'] ?? AppRelease::PLATFORM_ANDROID;
        $version = trim($meta['version']);
        $buildNumber = (int) ($meta['build_number'] ?? 0);
        if ($buildNumber <= 0) {
            $buildNumber = (int) AppRelease::forPlatform($platform)->max('build_number') + 1;
        }

        $fileName = 'niageo-ops-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $version) . '-' . $buildNumber . '.apk';
        $storedName = now()->format('YmdHis') . '-' . $fileName;
        $path = Storage::disk(self::DISK)->putFileAs("releases/{$platform}", $file, $storedName);

        return AppRelease::create([
            'platform' => $platform,
            'version' => $version,
            'build_number' => $buildNumber,
            'file_path' => $path,
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'git_sha' => $meta['git_sha'] ?? null,
            'notes' => $meta['notes'] ?? null,
            'uploaded_by' => $user?->id,
            'released_at' => now(),
        ]);
    }

    public function delete(AppRelease $release): void
    {
        Storage::disk(self::DISK)->delete($release->file_path);
        $release->delete();
    }
}
