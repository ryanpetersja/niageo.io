<?php

namespace App\Http\Controllers;

use App\Models\AppRelease;
use App\Services\AppReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The "Mobile app" page: download the latest APK, see earlier builds, upload one by hand.
 */
class AppReleaseController extends Controller
{
    public function __construct(private AppReleaseService $releases) {}

    public function index()
    {
        $latest = AppRelease::latestFor();
        $history = AppRelease::forPlatform(AppRelease::PLATFORM_ANDROID)
            ->with('uploader')
            ->orderByDesc('build_number')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('app.releases', [
            'latest' => $latest,
            'history' => $history,
            'ciConfigured' => ! empty(config('services.app_releases.token')),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:40',
            'build_number' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:2000',
            'apk' => 'required|file|max:' . AppReleaseService::MAX_SIZE_KB,
        ]);

        try {
            $release = $this->releases->store($request->file('apk'), $validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['apk' => $e->getMessage()]);
        }

        return redirect()->route('app.releases')->with('success', "Build {$release->display_version} published.");
    }

    /** Signed-in users download directly; phones use a short-lived signed link from the API. */
    public function download(Request $request, AppRelease $release)
    {
        if (! $request->user() && ! $request->hasValidSignature()) {
            return redirect()->guest(route('login'));
        }

        if (! Storage::disk(AppReleaseService::DISK)->exists($release->file_path)) {
            abort(404, 'The APK file is no longer on the server.');
        }

        return Storage::disk(AppReleaseService::DISK)->download($release->file_path, $release->file_name, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    public function destroy(AppRelease $release)
    {
        $this->releases->delete($release);

        return redirect()->route('app.releases')->with('success', "Build {$release->display_version} removed.");
    }
}
