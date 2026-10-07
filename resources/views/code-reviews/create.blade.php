<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Clients', 'url' => route('clients.index')], ['label' => $client->company_name, 'url' => route('clients.show', $client)], ['label' => 'New Code Review']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Review pull requests</h2>
        <p class="mt-1 text-sm text-muted">Pick the pull requests to review. The AI reports what to merge, what each one does, and the deployment and database steps your standard script won't cover.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ session('error') }}</div>
            @endif
            @if(! $githubConfigured)
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--warn-soft); color: var(--warn);">GitHub is not connected. An admin needs to set <code>GITHUB_TOKEN</code> on the server before reviews can run.</div>
            @endif

            @if($repositories->isEmpty())
                <div class="card p-6 text-sm text-muted">
                    {{ $client->company_name }} has no GitHub repositories linked. <a href="{{ route('clients.show', $client) }}#repositories" class="accent-ink hover:underline">Link one on the client page</a> first.
                </div>
            @else
                <div class="card p-4 sm:p-6" x-data="reviewPicker({{ (int) $selectedRepositoryId }}, @js($repositories->map(fn ($r) => ['id' => $r->id, 'name' => $r->full_name, 'branch' => $r->default_branch, 'deploy_script' => (string) $r->deploy_script])->values()))">
                    <form method="POST" action="{{ route('code-reviews.store', $client) }}" @submit="submitting = true">
                        @csrf

                        <div class="mb-6">
                            <x-input-label for="client_repository_id" value="Repository" />
                            <select id="client_repository_id" name="client_repository_id" x-model.number="repositoryId" @change="loadPulls()" class="field mt-1 text-base sm:text-sm">
                                @foreach($repositories as $repo)
                                    <option value="{{ $repo->id }}">{{ $repo->full_name }} ({{ $repo->default_branch }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_repository_id')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <x-input-label value="Pull requests" />
                                <label x-show="pulls.length" class="inline-flex items-center gap-2 text-xs text-muted">
                                    <input type="checkbox" :checked="allSelected()" @change="selectAll($event.target.checked)" class="rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500"> Select all
                                </label>
                            </div>
                            <p class="text-xs text-faint mb-2">Open pull requests and those merged or closed in the last 30 days. Open ones are ticked by default; up to 15 per review.</p>

                            <div x-show="loading" class="text-sm text-muted py-3">Loading pull requests from GitHub…</div>
                            <p x-show="error" x-text="error" class="text-sm py-2" style="color: var(--danger);"></p>
                            <p x-show="!loading && !error && loaded && !pulls.length" class="text-sm text-muted py-3">No open or recent pull requests in this repository.</p>

                            <ul x-show="pulls.length" class="divide-hair rounded-lg border" style="border-color: var(--border);">
                                <template x-for="pr in pulls" :key="pr.number">
                                    <li>
                                        <label class="flex items-start gap-3 px-3 py-3 cursor-pointer">
                                            <input type="checkbox" name="pull_numbers[]" :value="pr.number" x-model="selected" class="mt-1 rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500">
                                            <span class="min-w-0 flex-1">
                                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                    <span class="text-sm font-medium text-slate-100 break-words"><span class="text-muted" x-text="'#' + pr.number"></span> <span x-text="pr.title"></span></span>
                                                    <span class="badge" :class="{ 'badge-good': pr.state === 'open' && !pr.draft, 'badge-gray': pr.draft, 'badge-accent': pr.state === 'merged', 'badge-warn': pr.state === 'closed' }" x-text="pr.draft ? 'Draft' : pr.state"></span>
                                                </span>
                                                <span class="block text-xs text-faint mt-0.5" x-text="pr.author + ' · ' + pr.head + ' → ' + pr.base + ' · updated ' + pr.updated_at"></span>
                                            </span>
                                            <a :href="pr.url" target="_blank" rel="noopener" class="text-xs accent-ink hover:underline mt-1 shrink-0" @click.stop>GitHub</a>
                                        </label>
                                    </li>
                                </template>
                            </ul>
                            <x-input-error :messages="$errors->get('pull_numbers')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="deploy_script" value="Standard deploy script (optional)" />
                            <p class="text-xs text-faint mt-0.5 mb-1">What already runs on every deploy (your Forge script, for example). The review only lists steps this doesn't cover. Saved with the repository.</p>
                            <textarea id="deploy_script" name="deploy_script" rows="7" x-model="deployScript" class="field mt-1 font-mono text-xs" placeholder="cd /home/forge/example.com&#10;git pull origin $FORGE_SITE_BRANCH&#10;$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader&#10;npm ci && npm run build&#10;$FORGE_PHP artisan migrate --force"></textarea>
                            <x-input-error :messages="$errors->get('deploy_script')" class="mt-2" />
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 [&>*]:justify-center">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="submitting || !selected.length || {{ $githubConfigured ? 'false' : 'true' }}">
                                <span x-show="!submitting" x-text="selected.length ? 'Review ' + selected.length + ' pull request' + (selected.length === 1 ? '' : 's') : 'Review'"></span>
                                <span x-show="submitting" x-cloak>Reviewing… this takes a minute</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <script>
        function reviewPicker(initialRepositoryId, repositories) {
            return {
                repositoryId: initialRepositoryId,
                repositories,
                pulls: [],
                selected: [],
                deployScript: '',
                loading: false,
                loaded: false,
                error: '',
                submitting: false,

                init() {
                    this.loadPulls();
                },
                allSelected() { return this.pulls.length > 0 && this.pulls.every((p) => this.selected.includes(p.number)); },
                selectAll(on) { this.selected = on ? this.pulls.slice(0, 15).map((p) => p.number) : []; },

                async loadPulls() {
                    const repo = this.repositories.find((r) => r.id === this.repositoryId);
                    this.deployScript = repo ? repo.deploy_script : '';
                    this.pulls = []; this.selected = []; this.error = ''; this.loaded = false;
                    if (!repo) return;
                    this.loading = true;
                    try {
                        const response = await fetch(`{{ url('/clients/' . $client->id . '/repositories') }}/${repo.id}/pulls`, { headers: { Accept: 'application/json' } });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(data.message || 'GitHub lookup failed.');
                        this.pulls = data.pulls || [];
                        this.selected = this.pulls.filter((p) => p.state === 'open' && !p.draft).slice(0, 15).map((p) => p.number);
                        this.loaded = true;
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
