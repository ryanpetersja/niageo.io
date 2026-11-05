<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Services\AppReleaseService;
use Illuminate\Http\Request;

class AppReleaseController extends Controller
{
    public function __construct(private AppReleaseService $releases) {}

    /**
     * Publish a build. Called by the GitHub Actions workflow with the APP_RELEASE_TOKEN
     * (a deploy secret, not a user token), so it sits outside Sanctum.
     */
    public function store(Request $request)
    {
        $token = (string) config('services.app_releases.token');
        if ($token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Invalid release token.'], 401);
        }

        $validated = $request->validate([
            'version' => 'required|string|max:40',
            'build_number' => 'nullable|integer|min:1',
            'git_sha' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:2000',
            'apk' => 'required|file|max:' . AppReleaseService::MAX_SIZE_KB,
        ]);

        try {
            $release = $this->releases->store($request->file('apk'), $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['apk' => [$e->getMessage()]]], 422);
        }

        return response()->json($release->toApi(false), 201);
    }

    /** The newest Android build, with a link the phone's browser can download without signing in. */
    public function latest()
    {
        $release = AppRelease::latestFor();
        if (! $release) {
            return response()->json(['message' => 'No build has been published yet.'], 404);
        }

        return response()->json($release->toApi());
    }
}
