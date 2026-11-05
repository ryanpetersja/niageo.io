<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Mobile app']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Mobile app</h2>
        <p class="mt-1 text-sm text-muted">Niageo Ops for Android. Open this page on your phone and download the latest build.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ $errors->first() }}</div>
            @endif

            {{-- Latest build --}}
            <div class="card p-5 sm:p-6">
                @if($latest)
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold uppercase tracking-wider text-muted">Latest build</div>
                            <div class="mt-1 text-2xl font-bold text-white">Version {{ $latest->version }} <span class="text-muted font-medium text-base">build {{ $latest->build_number }}</span></div>
                            <div class="mt-2 text-sm text-slate-200">
                                Released
                                <time datetime="{{ $latest->released_at->toIso8601String() }}" data-local-time class="font-semibold">{{ $latest->released_at->format('M j, Y \a\t H:i') }} {{ $latest->released_at->format('T') }}</time>
                                <span class="text-muted">· <span data-relative-time="{{ $latest->released_at->toIso8601String() }}">{{ $latest->released_at->diffForHumans() }}</span></span>
                            </div>
                            <div class="mt-1 text-xs text-faint">
                                {{ $latest->size_label }}
                                @if($latest->short_sha) · commit {{ $latest->short_sha }} @endif
                                @if($latest->uploader) · uploaded by {{ $latest->uploader->name }} @else · built by CI @endif
                            </div>
                            @if($latest->notes)
                                <p class="mt-3 text-sm text-slate-200">{{ $latest->notes }}</p>
                            @endif
                        </div>
                        <a href="{{ route('app.releases.download', $latest) }}" class="btn btn-primary w-full sm:w-auto justify-center" style="padding: .8rem 1.4rem; font-size: .95rem;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                            Download APK
                        </a>
                    </div>
                    <div class="mt-5 rounded-lg p-4 text-sm text-muted" style="background: var(--surface-2);">
                        <span class="font-semibold text-slate-200">Installing:</span> on your Android phone, tap Download, open the file, and allow installs from your browser if Android asks. Updating over an older build keeps you signed in. The app also checks this page from Settings and offers the download when a newer build is here.
                    </div>
                @else
                    <div class="text-sm text-muted">
                        No build has been published yet.
                        @if($ciConfigured)
                            The next push to <code>main</code> that changes <code>mobile/</code> will build one and publish it here automatically.
                        @else
                            Set <code>APP_RELEASE_TOKEN</code> on the server and in the GitHub repository secrets so the CI pipeline can publish builds, or upload one below.
                        @endif
                    </div>
                @endif
            </div>

            {{-- History --}}
            @if($history->count() > 1)
                <div class="card p-5 sm:p-6">
                    <h3 class="text-lg font-semibold text-white mb-3">Earlier builds</h3>
                    <div class="overflow-x-auto">
                        <table class="table-cards min-w-full">
                            <thead>
                                <tr class="border-b" style="border-color: var(--border);">
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">Version</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">Released</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">Size</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-muted uppercase">Commit</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-muted uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-hair">
                                @foreach($history->skip(1) as $release)
                                    <tr class="row-item">
                                        <td data-label="Version" class="px-3 py-2 text-sm text-slate-200">{{ $release->display_version }}</td>
                                        <td data-label="Released" class="px-3 py-2 text-sm text-muted"><time datetime="{{ $release->released_at->toIso8601String() }}" data-local-time>{{ $release->released_at->format('M j, Y H:i') }}</time></td>
                                        <td data-label="Size" class="px-3 py-2 text-sm text-muted">{{ $release->size_label }}</td>
                                        <td data-label="Commit" class="px-3 py-2 text-sm text-muted">{{ $release->short_sha ?? '—' }}</td>
                                        <td data-label="Actions" class="px-3 py-2 text-sm text-right">
                                            <a href="{{ route('app.releases.download', $release) }}" class="accent-ink hover:underline">Download</a>
                                            @can('manage-settings')
                                                <form method="POST" action="{{ route('app.releases.destroy', $release) }}" class="inline ml-3" onsubmit="return confirm('Remove build {{ $release->display_version }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-slate-500 hover:text-rose-400">Remove</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Manual upload (admins) --}}
            @can('manage-settings')
                <div class="card p-5 sm:p-6">
                    <h3 class="text-lg font-semibold text-white mb-1">Upload a build</h3>
                    <p class="text-sm text-muted mb-4">For builds made on your own machine (<code>flutter build apk --release</code>). CI builds arrive here on their own.</p>
                    <form method="POST" action="{{ route('app.releases.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @csrf
                        <div>
                            <x-input-label for="version" value="Version" />
                            <x-text-input id="version" name="version" type="text" class="mt-1 block w-full" :value="old('version', $latest?->version)" placeholder="e.g. 0.2.0" required />
                        </div>
                        <div>
                            <x-input-label for="build_number" value="Build number (optional)" />
                            <x-text-input id="build_number" name="build_number" type="number" min="1" class="mt-1 block w-full" :value="old('build_number')" placeholder="next one if left blank" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="notes" value="What changed (optional)" />
                            <textarea id="notes" name="notes" rows="2" class="field mt-1">{{ old('notes') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="apk" value="APK file" />
                            <input id="apk" name="apk" type="file" accept=".apk" required class="field mt-1 text-sm file:mr-3 file:rounded-md file:border-0 file:px-3 file:py-1.5 file:text-xs file:font-semibold" style="padding: .4rem;">
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <x-primary-button>Publish build</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    <script>
        // Server times are UTC; show them in the viewer's own time zone.
        document.querySelectorAll('time[data-local-time]').forEach((el) => {
            const date = new Date(el.getAttribute('datetime'));
            if (!isNaN(date)) el.textContent = date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        });
    </script>
</x-app-layout>
