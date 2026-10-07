<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Clients', 'url' => route('clients.index')], ['label' => $review->client->company_name, 'url' => route('clients.show', $review->client)], ['label' => 'Code Review']]" />
        <div class="flex flex-wrap justify-between items-start gap-3">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-white leading-tight break-words">{{ $review->title }}</h2>
                <p class="mt-1 text-sm text-muted">
                    <a href="https://github.com/{{ $review->repository->full_name }}" target="_blank" rel="noopener" class="accent-ink hover:underline">{{ $review->repository->full_name }}</a>
                    · reviewed {{ $review->created_at->format('M d, Y H:i') }}{{ $review->creator ? ' by ' . $review->creator->name : '' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('code-reviews.create', ['client' => $review->client, 'repository' => $review->client_repository_id]) }}" class="btn btn-secondary">New review</a>
                <form method="POST" action="{{ route('code-reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </x-slot>

    @php
        $s = $review->sections;
        $verdicts = $review->verdicts();
        $badge = ['merge' => 'badge-good', 'merge_with_caution' => 'badge-warn', 'hold' => 'badge-danger'];
        $counts = array_count_values(array_column($s['pull_requests'] ?? [], 'recommendation'));
        $stateBadge = ['open' => 'badge-good', 'merged' => 'badge-accent', 'closed' => 'badge-warn'];
    @endphp

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif

            {{-- Summary --}}
            <div class="card p-4 sm:p-6">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <h3 class="text-lg font-semibold text-white mr-auto">Summary</h3>
                    @foreach(\App\Models\CodeReview::RECOMMENDATIONS as $key => $label)
                        @if(! empty($counts[$key]))
                            <span class="badge {{ $badge[$key] }}">{{ $counts[$key] }} {{ strtolower($label) }}</span>
                        @endif
                    @endforeach
                </div>
                <p class="text-sm text-slate-200 leading-relaxed">{{ $s['summary'] ?: 'No summary.' }}</p>
                @if(! empty($s['merge_order']))
                    <div class="mt-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-muted mb-1">Merge order</div>
                        <ol class="list-decimal pl-5 text-sm text-slate-200 space-y-1">
                            @foreach($s['merge_order'] as $step)<li>{{ $step }}</li>@endforeach
                        </ol>
                    </div>
                @endif
            </div>

            {{-- Deployment + database: the two things the deployer needs before pressing the button --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card p-4 sm:p-6">
                    <h3 class="text-lg font-semibold text-white mb-1">Additional deployment steps</h3>
                    <p class="text-xs text-faint mb-3">Beyond the standard deploy script{{ $review->repository->deploy_script ? '' : ' (none recorded, so every step is listed)' }}.</p>
                    @if(empty($s['deployment_steps']))
                        <p class="text-sm" style="color: var(--good);">None — the standard deploy script covers everything.</p>
                    @else
                        <ol class="space-y-2">
                            @foreach($s['deployment_steps'] as $i => $step)
                                <li class="flex gap-3 text-sm text-slate-200">
                                    <span class="shrink-0 w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-bold" style="background: var(--accent-soft); color: var(--accent-ink);">{{ $i + 1 }}</span>
                                    <span class="break-words">{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>

                <div class="card p-4 sm:p-6">
                    <h3 class="text-lg font-semibold text-white mb-1">Database updates</h3>
                    <p class="text-xs text-faint mb-3">Migrations and data changes these pull requests bring.</p>
                    @if(empty($s['database_updates']))
                        <p class="text-sm" style="color: var(--good);">None — no schema or data changes.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach($s['database_updates'] as $item)
                                <li class="flex gap-3 text-sm text-slate-200">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/></svg>
                                    <span class="break-words">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Per pull request --}}
            <div>
                <h3 class="text-lg font-semibold text-white mb-3">Pull requests</h3>
                <div class="space-y-4">
                    @foreach($review->pull_requests as $pr)
                        @php $v = $verdicts[$pr['number']] ?? null; @endphp
                        <div class="card p-4 sm:p-6">
                            <div class="flex flex-wrap items-start gap-2 mb-2">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $pr['url'] }}" target="_blank" rel="noopener" class="text-base font-semibold text-white hover:underline break-words"><span class="text-muted">#{{ $pr['number'] }}</span> {{ $pr['title'] }}</a>
                                    <div class="text-xs text-faint mt-1">
                                        {{ $pr['author'] }} · {{ $pr['head'] }} → {{ $pr['base'] }} · {{ $pr['changed_files'] }} files, <span style="color: var(--good);">+{{ $pr['additions'] }}</span> <span style="color: var(--danger);">−{{ $pr['deletions'] }}</span>
                                        @if($pr['merged_at']) · merged {{ $pr['merged_at'] }} @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="badge {{ $stateBadge[$pr['state']] ?? 'badge-gray' }}">{{ $pr['draft'] ? 'Draft' : ucfirst($pr['state']) }}</span>
                                    @if($v)
                                        <span class="badge {{ $badge[$v['recommendation']] ?? 'badge-gray' }}">{{ \App\Models\CodeReview::RECOMMENDATIONS[$v['recommendation']] ?? $v['recommendation'] }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($v)
                                @if($v['headline'] !== '' && $v['headline'] !== $pr['title'])
                                    <p class="text-sm font-medium text-slate-100 mb-2">{{ $v['headline'] }}</p>
                                @endif
                                <div class="text-xs font-semibold uppercase tracking-wider text-muted mb-1">What it does and how</div>
                                <p class="text-sm text-slate-200 leading-relaxed mb-3">{{ $v['functionality'] }}</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if(! empty($v['reasons']))
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wider text-muted mb-1">Why {{ strtolower(\App\Models\CodeReview::RECOMMENDATIONS[$v['recommendation']] ?? '') }}</div>
                                            <ul class="list-disc pl-5 text-sm text-slate-200 space-y-1">
                                                @foreach($v['reasons'] as $r)<li>{{ $r }}</li>@endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if(! empty($v['risks']))
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wider mb-1" style="color: var(--warn);">Risks</div>
                                            <ul class="list-disc pl-5 text-sm text-slate-200 space-y-1">
                                                @foreach($v['risks'] as $r)<li>{{ $r }}</li>@endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if(! empty($pr['deploy_sensitive_files']))
                                <details class="mt-3">
                                    <summary class="text-xs text-muted cursor-pointer hover:text-white">{{ count($pr['deploy_sensitive_files']) }} deployment-sensitive file(s) touched</summary>
                                    <ul class="mt-1 text-xs font-mono text-faint space-y-0.5 break-all">
                                        @foreach($pr['deploy_sensitive_files'] as $f)<li>{{ $f }}</li>@endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Post-deploy checks --}}
            @if(! empty($s['post_deploy_checks']))
                <div class="card p-4 sm:p-6" x-data="{ done: [] }">
                    <h3 class="text-lg font-semibold text-white mb-3">After deploying, check</h3>
                    <ul class="space-y-2">
                        @foreach($s['post_deploy_checks'] as $i => $check)
                            <li>
                                <label class="flex items-start gap-3 text-sm cursor-pointer" :class="done.includes({{ $i }}) ? 'text-faint line-through' : 'text-slate-200'">
                                    <input type="checkbox" value="{{ $i }}" x-model.number="done" class="mt-0.5 rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500">
                                    <span class="break-words">{{ $check }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($review->repository->deploy_script)
                <details class="card p-4 sm:p-6">
                    <summary class="text-sm font-semibold text-white cursor-pointer">Standard deploy script the review assumed</summary>
                    <pre class="mt-3 text-xs font-mono text-slate-300 whitespace-pre-wrap break-all">{{ $review->repository->deploy_script }}</pre>
                </details>
            @endif
        </div>
    </div>
</x-app-layout>
