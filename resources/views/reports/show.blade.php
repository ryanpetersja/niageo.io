<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Reports', 'url' => route('reports.index')], ['label' => $report->report_number]]" />
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Report {{ $report->report_number }}</h2>
            <div class="flex gap-2">
                @if(in_array($report->status, ['generated', 'sent', 'archived']))
                    <a href="{{ route('reports.pdf', $report) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-gray-700 transition">Preview PDF</a>
                    <a href="{{ route('reports.pdf.download', $report) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition">Download PDF</a>
                @endif
                @if(!in_array($report->status, ['sent', 'archived']))
                    <a href="{{ route('reports.edit', $report) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase text-gray-700 hover:bg-gray-50 transition">Edit</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Header Info -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-lg font-semibold">{{ $report->title }}</h3>
                                <div class="text-sm text-gray-500 mt-1">
                                    <a href="{{ route('clients.show', $report->client) }}" class="text-indigo-600 hover:text-indigo-800">{{ $report->client->company_name }}</a>
                                    &middot; Created by {{ $report->creator->name }} on {{ $report->created_at->format('M d, Y') }}
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $report->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $report->status === 'generated' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $report->status === 'sent' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $report->status === 'archived' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            ">{{ ucfirst($report->status) }}</span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-{{ $report->uptime_score !== null ? '6' : '5' }} gap-4 text-sm">
                            <div><span class="text-gray-500">Period</span><div class="font-medium">{{ $report->date_from->format('M d') }} — {{ $report->date_to->format('M d, Y') }}</div></div>
                            <div><span class="text-gray-500">Commits</span><div class="font-medium">{{ $report->commit_count }}</div></div>
                            <div><span class="text-gray-500">Repositories</span><div class="font-medium">{{ $report->repo_count }}</div></div>
                            <div><span class="text-gray-500">Server Commands</span><div class="font-medium">{{ $report->raw_server_activity ? count($report->raw_server_activity) : 0 }}</div></div>
                            @if($report->uptime_score !== null)
                                <div><span class="text-gray-500">Uptime</span><div class="font-medium text-green-700">{{ number_format($report->uptime_score, 2) }}%</div></div>
                            @endif
                            <div><span class="text-gray-500">Summary Items</span><div class="font-medium">{{ $report->summary_item_count }}</div></div>
                        </div>
                    </div>

                    <!-- Services Provided -->
                    @if($report->service_snapshot && count($report->service_snapshot) > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Services Provided</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($report->service_snapshot as $service)
                                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="mt-0.5 flex-shrink-0">
                                            @if($service['service_type'] === 'hosting')
                                                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" /></svg>
                                            @elseif($service['service_type'] === 'email')
                                                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            @elseif($service['service_type'] === 'backups')
                                                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" /></svg>
                                            @else
                                                <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-sm text-gray-800">{{ $service['display_name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $service['metric_text'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- AI Summary -->
                    @if($report->has_summary)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="summaryEditor()">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Maintenance Activity Summary</h3>
                                @if(!in_array($report->status, ['sent', 'archived']))
                                    <div class="flex items-center gap-2">
                                        <template x-if="dirty">
                                            <button @click="save()" :disabled="saving" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                                                <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                                            </button>
                                        </template>
                                        <button @click="editing = !editing" class="text-sm text-indigo-600 hover:text-indigo-800" x-text="editing ? 'Done Editing' : 'Edit'"></button>
                                    </div>
                                @endif
                            </div>

                            <template x-if="saveMessage">
                                <div class="mb-3 text-xs font-medium" :class="saveError ? 'text-red-600' : 'text-green-600'" x-text="saveMessage"></div>
                            </template>

                            <!-- Diff Preview Panel -->
                            <template x-if="feedbackPreview">
                                <div class="mb-4 border border-indigo-200 rounded-lg p-4 bg-indigo-50/50">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-sm font-semibold text-indigo-800">Proposed Changes Preview</h4>
                                        <span class="text-xs text-indigo-500">Review the AI's suggested revisions</span>
                                    </div>
                                    <div class="space-y-3">
                                        <template x-for="cat in categoryOrder" :key="'diff-' + cat.key">
                                            <div x-show="getDiffItems(cat.key).removed.length > 0 || getDiffItems(cat.key).added.length > 0" class="border-l-4 rounded-r-lg p-3" :class="cat.borderClass">
                                                <h5 class="font-semibold text-xs mb-1.5" :class="cat.textClass" x-text="cat.label"></h5>
                                                <ul class="space-y-1">
                                                    <template x-for="item in getDiffItems(cat.key).unchanged" :key="'u-' + cat.key + '-' + item">
                                                        <li class="text-sm text-gray-400 flex items-start gap-2">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-gray-300 flex-shrink-0"></span>
                                                            <span x-text="item"></span>
                                                        </li>
                                                    </template>
                                                    <template x-for="item in getDiffItems(cat.key).removed" :key="'r-' + cat.key + '-' + item">
                                                        <li class="text-sm flex items-start gap-2 bg-red-100 rounded px-2 py-1">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                                            <span class="line-through text-red-700" x-text="item"></span>
                                                        </li>
                                                    </template>
                                                    <template x-for="item in getDiffItems(cat.key).added" :key="'a-' + cat.key + '-' + item">
                                                        <li class="text-sm flex items-start gap-2 bg-green-100 rounded px-2 py-1">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                                                            <span class="text-green-700" x-text="item"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-4">
                                        <button @click="handleReject()" :disabled="feedbackLoading" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-xs font-semibold uppercase hover:bg-gray-50 transition disabled:opacity-50">Reject Changes</button>
                                        <button @click="handleAccept()" :disabled="feedbackLoading" class="px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition disabled:opacity-50">
                                            <span x-text="feedbackLoading ? 'Saving...' : 'Accept Changes'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <div class="space-y-4" x-show="!feedbackPreview">
                                <template x-for="cat in categoryOrder" :key="cat.key">
                                    <div x-show="summary[cat.key] && summary[cat.key].length > 0 || editing" class="border-l-4 rounded-r-lg p-4" :class="cat.borderClass">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-semibold text-sm" :class="cat.textClass">
                                                <span x-text="cat.label"></span>
                                                <span class="font-normal" x-text="'(' + (summary[cat.key] || []).length + ')'"></span>
                                            </h4>
                                            <button x-show="editing" @click="addItem(cat.key)" class="text-xs hover:underline" :class="cat.textClass">+ Add</button>
                                        </div>
                                        <ul class="space-y-1.5">
                                            <template x-for="(item, idx) in summary[cat.key]" :key="cat.key + '-' + idx">
                                                <li class="text-sm text-gray-700 group">
                                                    <template x-if="!editing">
                                                        <div>
                                                            <div class="flex items-start gap-2">
                                                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-current flex-shrink-0" :class="cat.textClass"></span>
                                                                <span class="flex-1" x-text="item"></span>
                                                                @if(!in_array($report->status, ['sent', 'archived']))
                                                                    <button @click="openItemFeedback(cat.key, idx, item)"
                                                                        class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 p-1 rounded hover:bg-gray-200" title="Give feedback on this item">
                                                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            <!-- Commit ref badges (internal only, hidden during editing/preview) -->
                                                            <template x-if="getCommitRefs(cat.key, idx).length > 0">
                                                                <div class="ml-4 mt-1 flex flex-wrap gap-1">
                                                                    <template x-for="sha in getCommitRefs(cat.key, idx)" :key="sha">
                                                                        <template x-if="getCommitUrl(sha)">
                                                                            <a :href="getCommitUrl(sha)" target="_blank" rel="noopener noreferrer"
                                                                                class="inline-block px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] font-mono leading-tight hover:bg-gray-200 hover:text-gray-700 transition"
                                                                                x-text="sha.substring(0, 7)"></a>
                                                                        </template>
                                                                        <template x-if="!getCommitUrl(sha)">
                                                                            <span class="inline-block px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] font-mono leading-tight"
                                                                                x-text="sha.substring(0, 7)"></span>
                                                                        </template>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <!-- Inline per-item feedback popover -->
                                                            <div x-show="feedbackItem && feedbackItem.category === cat.key && feedbackItem.index === idx" x-cloak
                                                                class="mt-2 ml-4 p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                                                                <textarea x-model="feedbackText" rows="2" maxlength="2000"
                                                                    placeholder="e.g., This should be infrastructure, not a feature"
                                                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                                                <template x-if="feedbackError">
                                                                    <p class="text-xs text-red-600 mt-1" x-text="feedbackError"></p>
                                                                </template>
                                                                <div class="flex justify-end gap-2 mt-2">
                                                                    <button @click="closeItemFeedback()" class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                                                    <button @click="previewItemFeedback()" :disabled="feedbackLoading || !feedbackText.trim()"
                                                                        class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                                                                        <span x-text="feedbackLoading ? 'Generating...' : 'Preview Changes'"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="editing">
                                                        <span class="flex items-start gap-1.5 w-full">
                                                            <span class="mt-2.5 w-1.5 h-1.5 rounded-full bg-current flex-shrink-0" :class="cat.textClass"></span>
                                                            <textarea x-model="summary[cat.key][idx]" @input="dirty = true" rows="2"
                                                                class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1 px-2"></textarea>
                                                            <button @click="removeItem(cat.key, idx)" class="text-red-400 hover:text-red-600 mt-1 flex-shrink-0" title="Remove">&times;</button>
                                                        </span>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                        <p x-show="editing && (!summary[cat.key] || summary[cat.key].length === 0)" class="text-xs text-gray-400 italic">No items. Click "+ Add" to create one.</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <script>
                            function summaryEditor() {
                                return {
                                    summary: @json($report->ai_summary),
                                    rawCommits: @json($report->raw_commits ?? []),
                                    editing: false,
                                    dirty: false,
                                    saving: false,
                                    saveMessage: '',
                                    saveError: false,
                                    reportId: {{ $report->id }},
                                    summaryType: 'ai_summary',
                                    // Per-item feedback state
                                    feedbackItem: null,
                                    feedbackText: '',
                                    feedbackLoading: false,
                                    feedbackError: '',
                                    feedbackPreview: null,
                                    categoryOrder: [
                                        { key: 'features', label: 'Features Delivered', borderClass: 'border-green-400 bg-green-50', textClass: 'text-green-800' },
                                        { key: 'bugs', label: 'Bug Fixes', borderClass: 'border-red-400 bg-red-50', textClass: 'text-red-800' },
                                        { key: 'improvements', label: 'Improvements', borderClass: 'border-blue-400 bg-blue-50', textClass: 'text-blue-800' },
                                        { key: 'security', label: 'Security & Stability', borderClass: 'border-purple-400 bg-purple-50', textClass: 'text-purple-800' },
                                        { key: 'infrastructure', label: 'Infrastructure & Maintenance', borderClass: 'border-gray-400 bg-gray-50', textClass: 'text-gray-800' },
                                    ],

                                    addItem(category) {
                                        if (!this.summary[category]) this.summary[category] = [];
                                        this.summary[category].push('');
                                        if (this.summary.commit_refs && this.summary.commit_refs[category]) {
                                            this.summary.commit_refs[category].push([]);
                                        }
                                        this.dirty = true;
                                    },

                                    removeItem(category, index) {
                                        this.summary[category].splice(index, 1);
                                        if (this.summary.commit_refs && this.summary.commit_refs[category]) {
                                            this.summary.commit_refs[category].splice(index, 1);
                                        }
                                        this.dirty = true;
                                    },

                                    getCommitRefs(category, index) {
                                        if (!this.summary.commit_refs) return [];
                                        const refs = this.summary.commit_refs[category];
                                        if (!refs || !refs[index]) return [];
                                        return refs[index];
                                    },

                                    getCommitUrl(sha) {
                                        const commit = this.rawCommits.find(c => c.sha && c.sha.startsWith(sha));
                                        if (commit && commit.repo) {
                                            return `https://github.com/${commit.repo}/commit/${sha}`;
                                        }
                                        const repos = [...new Set(this.rawCommits.map(c => c.repo).filter(Boolean))];
                                        if (repos.length === 1) {
                                            return `https://github.com/${repos[0]}/commit/${sha}`;
                                        }
                                        return null;
                                    },

                                    openItemFeedback(category, index, text) {
                                        if (this.feedbackItem && this.feedbackItem.category === category && this.feedbackItem.index === index) {
                                            this.closeItemFeedback();
                                            return;
                                        }
                                        this.feedbackItem = { category, index, text };
                                        this.feedbackText = '';
                                        this.feedbackError = '';
                                        this.feedbackPreview = null;
                                    },

                                    closeItemFeedback() {
                                        this.feedbackItem = null;
                                        this.feedbackText = '';
                                        this.feedbackError = '';
                                    },

                                    async previewItemFeedback() {
                                        this.feedbackLoading = true;
                                        this.feedbackError = '';
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/preview`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    feedback: this.feedbackText,
                                                    summary_type: this.summaryType,
                                                    category: this.feedbackItem?.category || null,
                                                    item_index: this.feedbackItem?.index ?? null,
                                                    item_text: this.feedbackItem?.text || null,
                                                })
                                            });
                                            if (resp.ok) {
                                                this.feedbackPreview = await resp.json();
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to generate preview.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    getDiffItems(category) {
                                        if (!this.feedbackPreview) return { unchanged: [], removed: [], added: [] };
                                        const original = this.feedbackPreview.original[category] || [];
                                        const proposed = this.feedbackPreview.proposed[category] || [];
                                        const originalSet = new Set(original);
                                        const proposedSet = new Set(proposed);
                                        return {
                                            unchanged: original.filter(i => proposedSet.has(i)),
                                            removed: original.filter(i => !proposedSet.has(i)),
                                            added: proposed.filter(i => !originalSet.has(i)),
                                        };
                                    },

                                    async handleAccept() {
                                        this.feedbackLoading = true;
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/accept`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    feedback: this.feedbackText,
                                                    summary_type: this.summaryType,
                                                    category: this.feedbackItem?.category || null,
                                                    item_index: this.feedbackItem?.index ?? null,
                                                    item_text: this.feedbackItem?.text || null,
                                                    proposed_summary: this.feedbackPreview.proposed,
                                                })
                                            });
                                            if (resp.ok) {
                                                const data = await resp.json();
                                                this.summary = data.summary;
                                                this.feedbackPreview = null;
                                                this.closeItemFeedback();
                                                this.saveMessage = 'Changes accepted and saved.';
                                                this.saveError = false;
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to accept changes.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    async handleReject() {
                                        this.feedbackLoading = true;
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/reject`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    feedback: this.feedbackText,
                                                    summary_type: this.summaryType,
                                                    category: this.feedbackItem?.category || null,
                                                    item_index: this.feedbackItem?.index ?? null,
                                                    item_text: this.feedbackItem?.text || null,
                                                })
                                            });
                                            if (resp.ok) {
                                                this.feedbackPreview = null;
                                                this.closeItemFeedback();
                                                this.saveMessage = 'Changes rejected. Feedback saved for future reports.';
                                                this.saveError = false;
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to reject changes.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    async save() {
                                        this.saving = true;
                                        this.saveMessage = '';
                                        this.saveError = false;

                                        const cleaned = {};
                                        const cleanedRefs = {};
                                        for (const key of ['features', 'bugs', 'improvements', 'security', 'infrastructure']) {
                                            const items = this.summary[key] || [];
                                            const refs = (this.summary.commit_refs && this.summary.commit_refs[key]) || [];
                                            cleaned[key] = [];
                                            cleanedRefs[key] = [];
                                            items.forEach((s, i) => {
                                                if (s.trim() !== '') {
                                                    cleaned[key].push(s);
                                                    cleanedRefs[key].push(refs[i] || []);
                                                }
                                            });
                                        }
                                        if (this.summary.commit_refs) {
                                            cleaned.commit_refs = cleanedRefs;
                                        }

                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/summary`, {
                                                method: 'PUT',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({ ai_summary: cleaned })
                                            });

                                            if (resp.ok) {
                                                this.summary = cleaned;
                                                this.dirty = false;
                                                this.saveMessage = 'Summary saved successfully.';
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.saveError = true;
                                                this.saveMessage = err.message || 'Failed to save.';
                                            }
                                        } catch (e) {
                                            this.saveError = true;
                                            this.saveMessage = 'Network error. Please try again.';
                                        }
                                        this.saving = false;
                                    }
                                }
                            }
                        </script>
                    @elseif($report->status === 'draft')
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                            <div class="py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No report data yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Click "Generate Report" to fetch commits, server activity, and create the AI summary.</p>
                            </div>
                        </div>
                    @endif

                    <!-- Server Activity Summary -->
                    @if($report->has_server_summary)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="serverSummaryEditor()">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Server Activity Summary</h3>
                                @if(!in_array($report->status, ['sent', 'archived']))
                                    <div class="flex items-center gap-2">
                                        <template x-if="dirty">
                                            <button @click="save()" :disabled="saving" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                                                <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                                            </button>
                                        </template>
                                        <button @click="editing = !editing" class="text-sm text-indigo-600 hover:text-indigo-800" x-text="editing ? 'Done Editing' : 'Edit'"></button>
                                    </div>
                                @endif
                            </div>

                            <template x-if="saveMessage">
                                <div class="mb-3 text-xs font-medium" :class="saveError ? 'text-red-600' : 'text-green-600'" x-text="saveMessage"></div>
                            </template>

                            <!-- Diff Preview Panel -->
                            <template x-if="feedbackPreview">
                                <div class="mb-4 border border-indigo-200 rounded-lg p-4 bg-indigo-50/50">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-sm font-semibold text-indigo-800">Proposed Changes Preview</h4>
                                        <span class="text-xs text-indigo-500">Review the AI's suggested revisions</span>
                                    </div>
                                    <div class="space-y-3">
                                        <template x-for="cat in categoryOrder" :key="'diff-' + cat.key">
                                            <div x-show="getDiffItems(cat.key).removed.length > 0 || getDiffItems(cat.key).added.length > 0" class="border-l-4 rounded-r-lg p-3" :class="cat.borderClass">
                                                <h5 class="font-semibold text-xs mb-1.5" :class="cat.textClass" x-text="cat.label"></h5>
                                                <ul class="space-y-1">
                                                    <template x-for="item in getDiffItems(cat.key).unchanged" :key="'u-' + cat.key + '-' + item">
                                                        <li class="text-sm text-gray-400 flex items-start gap-2">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-gray-300 flex-shrink-0"></span>
                                                            <span x-text="item"></span>
                                                        </li>
                                                    </template>
                                                    <template x-for="item in getDiffItems(cat.key).removed" :key="'r-' + cat.key + '-' + item">
                                                        <li class="text-sm flex items-start gap-2 bg-red-100 rounded px-2 py-1">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                                            <span class="line-through text-red-700" x-text="item"></span>
                                                        </li>
                                                    </template>
                                                    <template x-for="item in getDiffItems(cat.key).added" :key="'a-' + cat.key + '-' + item">
                                                        <li class="text-sm flex items-start gap-2 bg-green-100 rounded px-2 py-1">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                                                            <span class="text-green-700" x-text="item"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-4">
                                        <button @click="handleReject()" :disabled="feedbackLoading" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-xs font-semibold uppercase hover:bg-gray-50 transition disabled:opacity-50">Reject Changes</button>
                                        <button @click="handleAccept()" :disabled="feedbackLoading" class="px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition disabled:opacity-50">
                                            <span x-text="feedbackLoading ? 'Saving...' : 'Accept Changes'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <div class="space-y-4" x-show="!feedbackPreview">
                                <template x-for="cat in categoryOrder" :key="cat.key">
                                    <div x-show="summary[cat.key] && summary[cat.key].length > 0 || editing" class="border-l-4 rounded-r-lg p-4" :class="cat.borderClass">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-semibold text-sm" :class="cat.textClass">
                                                <span x-text="cat.label"></span>
                                                <span class="font-normal" x-text="'(' + (summary[cat.key] || []).length + ')'"></span>
                                            </h4>
                                            <button x-show="editing" @click="addItem(cat.key)" class="text-xs hover:underline" :class="cat.textClass">+ Add</button>
                                        </div>
                                        <ul class="space-y-1.5">
                                            <template x-for="(item, idx) in summary[cat.key]" :key="cat.key + '-' + idx">
                                                <li class="text-sm text-gray-700 group">
                                                    <template x-if="!editing">
                                                        <div>
                                                            <div class="flex items-start gap-2">
                                                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-current flex-shrink-0" :class="cat.textClass"></span>
                                                                <span class="flex-1" x-text="item"></span>
                                                                @if(!in_array($report->status, ['sent', 'archived']))
                                                                    <button @click="openItemFeedback(cat.key, idx, item)"
                                                                        class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 p-1 rounded hover:bg-gray-200" title="Give feedback on this item">
                                                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            <div x-show="feedbackItem && feedbackItem.category === cat.key && feedbackItem.index === idx" x-cloak
                                                                class="mt-2 ml-4 p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                                                                <textarea x-model="feedbackText" rows="2" maxlength="2000"
                                                                    placeholder="e.g., This should be infrastructure, not a feature"
                                                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                                                <template x-if="feedbackError">
                                                                    <p class="text-xs text-red-600 mt-1" x-text="feedbackError"></p>
                                                                </template>
                                                                <div class="flex justify-end gap-2 mt-2">
                                                                    <button @click="closeItemFeedback()" class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                                                    <button @click="previewItemFeedback()" :disabled="feedbackLoading || !feedbackText.trim()"
                                                                        class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                                                                        <span x-text="feedbackLoading ? 'Generating...' : 'Preview Changes'"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="editing">
                                                        <span class="flex items-start gap-1.5 w-full">
                                                            <span class="mt-2.5 w-1.5 h-1.5 rounded-full bg-current flex-shrink-0" :class="cat.textClass"></span>
                                                            <textarea x-model="summary[cat.key][idx]" @input="dirty = true" rows="2"
                                                                class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1 px-2"></textarea>
                                                            <button @click="removeItem(cat.key, idx)" class="text-red-400 hover:text-red-600 mt-1 flex-shrink-0" title="Remove">&times;</button>
                                                        </span>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                        <p x-show="editing && (!summary[cat.key] || summary[cat.key].length === 0)" class="text-xs text-gray-400 italic">No items. Click "+ Add" to create one.</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <script>
                            function serverSummaryEditor() {
                                return {
                                    summary: @json($report->server_summary),
                                    editing: false,
                                    dirty: false,
                                    saving: false,
                                    saveMessage: '',
                                    saveError: false,
                                    reportId: {{ $report->id }},
                                    summaryType: 'server_summary',
                                    feedbackItem: null,
                                    feedbackText: '',
                                    feedbackLoading: false,
                                    feedbackError: '',
                                    feedbackPreview: null,
                                    categoryOrder: [
                                        { key: 'features', label: 'Deployments & Updates', borderClass: 'border-green-400 bg-green-50', textClass: 'text-green-800' },
                                        { key: 'bugs', label: 'Server-Side Fixes', borderClass: 'border-red-400 bg-red-50', textClass: 'text-red-800' },
                                        { key: 'improvements', label: 'Performance & Optimization', borderClass: 'border-blue-400 bg-blue-50', textClass: 'text-blue-800' },
                                        { key: 'security', label: 'Security & Certificates', borderClass: 'border-purple-400 bg-purple-50', textClass: 'text-purple-800' },
                                        { key: 'infrastructure', label: 'Server Maintenance', borderClass: 'border-gray-400 bg-gray-50', textClass: 'text-gray-800' },
                                    ],

                                    addItem(category) {
                                        if (!this.summary[category]) this.summary[category] = [];
                                        this.summary[category].push('');
                                        this.dirty = true;
                                    },

                                    removeItem(category, index) {
                                        this.summary[category].splice(index, 1);
                                        this.dirty = true;
                                    },

                                    openItemFeedback(category, index, text) {
                                        if (this.feedbackItem && this.feedbackItem.category === category && this.feedbackItem.index === index) {
                                            this.closeItemFeedback();
                                            return;
                                        }
                                        this.feedbackItem = { category, index, text };
                                        this.feedbackText = '';
                                        this.feedbackError = '';
                                        this.feedbackPreview = null;
                                    },

                                    closeItemFeedback() {
                                        this.feedbackItem = null;
                                        this.feedbackText = '';
                                        this.feedbackError = '';
                                    },

                                    async previewItemFeedback() {
                                        this.feedbackLoading = true;
                                        this.feedbackError = '';
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/preview`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    feedback: this.feedbackText,
                                                    summary_type: this.summaryType,
                                                    category: this.feedbackItem?.category || null,
                                                    item_index: this.feedbackItem?.index ?? null,
                                                    item_text: this.feedbackItem?.text || null,
                                                })
                                            });
                                            if (resp.ok) {
                                                this.feedbackPreview = await resp.json();
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to generate preview.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    getDiffItems(category) {
                                        if (!this.feedbackPreview) return { unchanged: [], removed: [], added: [] };
                                        const original = this.feedbackPreview.original[category] || [];
                                        const proposed = this.feedbackPreview.proposed[category] || [];
                                        const originalSet = new Set(original);
                                        const proposedSet = new Set(proposed);
                                        return {
                                            unchanged: original.filter(i => proposedSet.has(i)),
                                            removed: original.filter(i => !proposedSet.has(i)),
                                            added: proposed.filter(i => !originalSet.has(i)),
                                        };
                                    },

                                    async handleAccept() {
                                        this.feedbackLoading = true;
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/accept`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    feedback: this.feedbackText,
                                                    summary_type: this.summaryType,
                                                    category: this.feedbackItem?.category || null,
                                                    item_index: this.feedbackItem?.index ?? null,
                                                    item_text: this.feedbackItem?.text || null,
                                                    proposed_summary: this.feedbackPreview.proposed,
                                                })
                                            });
                                            if (resp.ok) {
                                                const data = await resp.json();
                                                this.summary = data.summary;
                                                this.feedbackPreview = null;
                                                this.closeItemFeedback();
                                                this.saveMessage = 'Changes accepted and saved.';
                                                this.saveError = false;
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to accept changes.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    async handleReject() {
                                        this.feedbackLoading = true;
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/reject`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    feedback: this.feedbackText,
                                                    summary_type: this.summaryType,
                                                    category: this.feedbackItem?.category || null,
                                                    item_index: this.feedbackItem?.index ?? null,
                                                    item_text: this.feedbackItem?.text || null,
                                                })
                                            });
                                            if (resp.ok) {
                                                this.feedbackPreview = null;
                                                this.closeItemFeedback();
                                                this.saveMessage = 'Changes rejected. Feedback saved for future reports.';
                                                this.saveError = false;
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to reject changes.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    async save() {
                                        this.saving = true;
                                        this.saveMessage = '';
                                        this.saveError = false;

                                        const cleaned = {};
                                        for (const key of ['features', 'bugs', 'improvements', 'security', 'infrastructure']) {
                                            cleaned[key] = (this.summary[key] || []).filter(s => s.trim() !== '');
                                        }

                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/server-summary`, {
                                                method: 'PUT',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({ server_summary: cleaned })
                                            });

                                            if (resp.ok) {
                                                this.summary = cleaned;
                                                this.dirty = false;
                                                this.saveMessage = 'Server summary saved successfully.';
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.saveError = true;
                                                this.saveMessage = err.message || 'Failed to save.';
                                            }
                                        } catch (e) {
                                            this.saveError = true;
                                            this.saveMessage = 'Network error. Please try again.';
                                        }
                                        this.saving = false;
                                    }
                                }
                            }
                        </script>
                    @endif

                    <!-- Report Feedback -->
                    @if(in_array($report->status, ['generated']))
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="generalFeedback()">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Report Feedback</h3>
                                <button @click="showForm = !showForm; if(showForm) feedbackPreview = null;" class="text-sm text-indigo-600 hover:text-indigo-800" x-text="showForm ? 'Cancel' : 'Give Feedback'"></button>
                            </div>

                            <div x-show="showForm && !feedbackPreview" x-cloak class="mb-4">
                                <div class="mb-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Apply feedback to:</label>
                                    <select x-model="summaryType" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @if($report->has_summary)
                                            <option value="ai_summary">Maintenance Activity Summary</option>
                                        @endif
                                        @if($report->has_server_summary)
                                            <option value="server_summary">Server Activity Summary</option>
                                        @endif
                                    </select>
                                </div>
                                <textarea x-model="feedbackText" rows="3" maxlength="2000"
                                    placeholder="e.g., Don't list routine updates as features. Be more specific about what changed for end users."
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                <p class="text-xs text-gray-400 mt-1">Feedback will revise the current report and be distilled into rules for future reports.</p>
                                <template x-if="feedbackError">
                                    <p class="text-xs text-red-600 mt-1" x-text="feedbackError"></p>
                                </template>
                                <div class="flex justify-end mt-2">
                                    <button @click="previewFeedback()" :disabled="feedbackLoading || !feedbackText.trim()"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-indigo-700 transition disabled:opacity-50">
                                        <span x-text="feedbackLoading ? 'Generating Preview...' : 'Preview Changes'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Diff Preview for General Feedback -->
                            <template x-if="feedbackPreview">
                                <div class="mb-4 border border-indigo-200 rounded-lg p-4 bg-indigo-50/50">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-sm font-semibold text-indigo-800">Proposed Changes Preview</h4>
                                        <span class="text-xs text-indigo-500">Review the AI's suggested revisions</span>
                                    </div>
                                    <div class="space-y-3">
                                        <template x-for="cat in categoryOrder" :key="'gdiff-' + cat.key">
                                            <div x-show="getGeneralDiffItems(cat.key).removed.length > 0 || getGeneralDiffItems(cat.key).added.length > 0" class="border-l-4 rounded-r-lg p-3" :class="cat.borderClass">
                                                <h5 class="font-semibold text-xs mb-1.5" :class="cat.textClass" x-text="cat.label"></h5>
                                                <ul class="space-y-1">
                                                    <template x-for="item in getGeneralDiffItems(cat.key).unchanged" :key="'gu-' + cat.key + '-' + item">
                                                        <li class="text-sm text-gray-400 flex items-start gap-2">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-gray-300 flex-shrink-0"></span>
                                                            <span x-text="item"></span>
                                                        </li>
                                                    </template>
                                                    <template x-for="item in getGeneralDiffItems(cat.key).removed" :key="'gr-' + cat.key + '-' + item">
                                                        <li class="text-sm flex items-start gap-2 bg-red-100 rounded px-2 py-1">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                                            <span class="line-through text-red-700" x-text="item"></span>
                                                        </li>
                                                    </template>
                                                    <template x-for="item in getGeneralDiffItems(cat.key).added" :key="'ga-' + cat.key + '-' + item">
                                                        <li class="text-sm flex items-start gap-2 bg-green-100 rounded px-2 py-1">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                                                            <span class="text-green-700" x-text="item"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </template>
                                    </div>
                                    <template x-if="feedbackError">
                                        <p class="text-xs text-red-600 mt-2" x-text="feedbackError"></p>
                                    </template>
                                    <div class="flex justify-end gap-2 mt-4">
                                        <button @click="rejectFeedback()" :disabled="feedbackLoading" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-xs font-semibold uppercase hover:bg-gray-50 transition disabled:opacity-50">Reject Changes</button>
                                        <button @click="acceptFeedback()" :disabled="feedbackLoading" class="px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition disabled:opacity-50">
                                            <span x-text="feedbackLoading ? 'Saving...' : 'Accept Changes'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="statusMessage">
                                <div class="mb-3 text-xs font-medium" :class="statusError ? 'text-red-600' : 'text-green-600'" x-text="statusMessage"></div>
                            </template>

                            @if($report->feedback->count() > 0)
                                <div class="space-y-2">
                                    @foreach($report->feedback as $entry)
                                        <div class="p-3 bg-gray-50 rounded text-sm">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="text-gray-700">{{ $entry->feedback }}</p>
                                                    @if($entry->category)
                                                        <span class="text-xs text-gray-400 mt-0.5 block">on {{ $entry->category }}{{ $entry->item_text ? ': "' . Str::limit($entry->item_text, 60) . '"' : '' }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex gap-1 ml-2 flex-shrink-0">
                                                    @if($entry->resolution === 'accepted')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Accepted</span>
                                                    @elseif($entry->resolution === 'rejected')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pending</span>
                                                    @endif
                                                    @if($entry->processed)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Distilled</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-400 mt-1">{{ $entry->user->name }} &middot; {{ $entry->created_at->format('M d, Y H:i') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500" x-show="!showForm">No feedback submitted for this report yet.</p>
                            @endif
                        </div>

                        <script>
                            function generalFeedback() {
                                return {
                                    showForm: false,
                                    summaryType: '{{ $report->has_summary ? "ai_summary" : "server_summary" }}',
                                    feedbackText: '',
                                    feedbackLoading: false,
                                    feedbackError: '',
                                    feedbackPreview: null,
                                    statusMessage: '',
                                    statusError: false,
                                    reportId: {{ $report->id }},
                                    categoryOrder: [
                                        { key: 'features', label: 'Features', borderClass: 'border-green-400 bg-green-50', textClass: 'text-green-800' },
                                        { key: 'bugs', label: 'Bug Fixes', borderClass: 'border-red-400 bg-red-50', textClass: 'text-red-800' },
                                        { key: 'improvements', label: 'Improvements', borderClass: 'border-blue-400 bg-blue-50', textClass: 'text-blue-800' },
                                        { key: 'security', label: 'Security', borderClass: 'border-purple-400 bg-purple-50', textClass: 'text-purple-800' },
                                        { key: 'infrastructure', label: 'Infrastructure', borderClass: 'border-gray-400 bg-gray-50', textClass: 'text-gray-800' },
                                    ],

                                    async previewFeedback() {
                                        this.feedbackLoading = true;
                                        this.feedbackError = '';
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/preview`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({ feedback: this.feedbackText, summary_type: this.summaryType })
                                            });
                                            if (resp.ok) {
                                                this.feedbackPreview = await resp.json();
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to generate preview.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    getGeneralDiffItems(category) {
                                        if (!this.feedbackPreview) return { unchanged: [], removed: [], added: [] };
                                        const original = this.feedbackPreview.original[category] || [];
                                        const proposed = this.feedbackPreview.proposed[category] || [];
                                        const originalSet = new Set(original);
                                        const proposedSet = new Set(proposed);
                                        return {
                                            unchanged: original.filter(i => proposedSet.has(i)),
                                            removed: original.filter(i => !proposedSet.has(i)),
                                            added: proposed.filter(i => !originalSet.has(i)),
                                        };
                                    },

                                    async acceptFeedback() {
                                        this.feedbackLoading = true;
                                        this.feedbackError = '';
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/accept`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    feedback: this.feedbackText,
                                                    summary_type: this.summaryType,
                                                    proposed_summary: this.feedbackPreview.proposed,
                                                })
                                            });
                                            if (resp.ok) {
                                                this.feedbackPreview = null;
                                                this.showForm = false;
                                                this.feedbackText = '';
                                                this.statusMessage = 'Changes accepted and saved. Reload to see updated summary.';
                                                this.statusError = false;
                                                setTimeout(() => this.statusMessage = '', 5000);
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to accept changes.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },

                                    async rejectFeedback() {
                                        this.feedbackLoading = true;
                                        this.feedbackError = '';
                                        try {
                                            const resp = await fetch(`/reports/${this.reportId}/feedback/reject`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({ feedback: this.feedbackText, summary_type: this.summaryType })
                                            });
                                            if (resp.ok) {
                                                this.feedbackPreview = null;
                                                this.showForm = false;
                                                this.feedbackText = '';
                                                this.statusMessage = 'Changes rejected. Feedback saved for future reports.';
                                                this.statusError = false;
                                                setTimeout(() => this.statusMessage = '', 5000);
                                            } else {
                                                const err = await resp.json();
                                                this.feedbackError = err.message || 'Failed to reject changes.';
                                            }
                                        } catch (e) {
                                            this.feedbackError = 'Network error. Please try again.';
                                        }
                                        this.feedbackLoading = false;
                                    },
                                }
                            }
                        </script>
                    @elseif(in_array($report->status, ['sent', 'archived']) && $report->feedback->count() > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Report Feedback</h3>
                            <div class="space-y-2">
                                @foreach($report->feedback as $entry)
                                    <div class="p-3 bg-gray-50 rounded text-sm">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-gray-700">{{ $entry->feedback }}</p>
                                                @if($entry->category)
                                                    <span class="text-xs text-gray-400 mt-0.5 block">on {{ $entry->category }}{{ $entry->item_text ? ': "' . Str::limit($entry->item_text, 60) . '"' : '' }}</span>
                                                @endif
                                            </div>
                                            <div class="flex gap-1 ml-2 flex-shrink-0">
                                                @if($entry->resolution === 'accepted')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Accepted</span>
                                                @elseif($entry->resolution === 'rejected')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pending</span>
                                                @endif
                                                @if($entry->processed)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Distilled</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">{{ $entry->user->name }} &middot; {{ $entry->created_at->format('M d, Y H:i') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Raw Commits (Collapsible) -->
                    @if($report->raw_commits && count($report->raw_commits) > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ showCommits: false }">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Raw Commits ({{ count($report->raw_commits) }})</h3>
                                <button @click="showCommits = !showCommits" class="text-sm text-indigo-600 hover:text-indigo-800" x-text="showCommits ? 'Hide' : 'Show'"></button>
                            </div>
                            <div x-show="showCommits" x-cloak class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">SHA</th>
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Repo</th>
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report->raw_commits as $commit)
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="py-2 font-mono text-xs text-gray-600">{{ $commit['sha'] }}</td>
                                                <td class="py-2 text-xs text-gray-500">{{ $commit['repo'] }}</td>
                                                <td class="py-2 text-sm">{{ Str::limit(strtok($commit['message'], "\n"), 80) }}</td>
                                                <td class="py-2 text-xs text-gray-500">{{ $commit['author_name'] }}</td>
                                                <td class="py-2 text-xs text-gray-500">{{ \Carbon\Carbon::parse($commit['date'])->format('M d, H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Raw Server Activity (Collapsible) -->
                    @if($report->raw_server_activity && count($report->raw_server_activity) > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ showActivity: false }">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Raw Server Activity ({{ count($report->raw_server_activity) }})</h3>
                                <button @click="showActivity = !showActivity" class="text-sm text-indigo-600 hover:text-indigo-800" x-text="showActivity ? 'Hide' : 'Show'"></button>
                            </div>
                            <div x-show="showActivity" x-cloak class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Server</th>
                                            <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Command</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report->raw_server_activity as $activity)
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="py-2 text-xs text-gray-500 whitespace-nowrap">{{ $activity['timestamp'] ?? '—' }}</td>
                                                <td class="py-2 text-xs text-gray-500">{{ $activity['server_label'] ?? '—' }}</td>
                                                <td class="py-2 text-sm font-mono text-gray-700">{{ Str::limit($activity['command'], 120) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($report->notes || $report->internal_notes)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Notes</h3>
                            @if($report->notes)
                                <div class="p-3 bg-gray-50 rounded text-sm mb-3"><strong>Client Notes:</strong> {{ $report->notes }}</div>
                            @endif
                            @if($report->internal_notes)
                                <div class="p-3 bg-yellow-50 rounded text-sm"><strong>Internal Notes:</strong> {{ $report->internal_notes }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Sidebar (1/3) -->
                <div class="space-y-6">
                    <!-- Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
                        <div class="space-y-2">
                            @if($report->status === 'draft')
                                <form method="POST" action="{{ route('reports.generate', $report) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 rounded-md text-sm font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition" onclick="this.disabled=true;this.innerText='Generating...';this.form.submit();">Generate Report</button>
                                </form>
                            @endif

                            @if($report->status === 'generated')
                                <form method="POST" action="{{ route('reports.regenerate', $report) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 rounded-md text-sm font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition" onclick="this.disabled=true;this.innerText='Regenerating...';this.form.submit();">Regenerate</button>
                                </form>
                            @endif

                            @if($report->status === 'generated')
                                <div x-data="{ showSend: false }">
                                    <button @click="showSend = !showSend" class="w-full text-left px-4 py-2 rounded-md text-sm font-medium bg-green-100 text-green-800 hover:bg-green-200 transition">Send Report</button>
                                    <div x-show="showSend" x-cloak class="mt-2">
                                        <form method="POST" action="{{ route('reports.send', $report) }}" class="space-y-2">
                                            @csrf
                                            <input type="email" name="email" required placeholder="Recipient email"
                                                value="{{ $report->client->billing_email ?? $report->client->primaryContact()?->email ?? '' }}"
                                                class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <button type="submit" class="w-full px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700">Send</button>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            @foreach($validTransitions as $transition)
                                @if($transition === 'archived')
                                    <form method="POST" action="{{ route('reports.transition', $report) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="archived">
                                        <button type="submit" class="w-full text-left px-4 py-2 rounded-md text-sm font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition">Archive</button>
                                    </form>
                                @endif
                            @endforeach

                            @if($report->status === 'draft')
                                <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Delete this report?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full text-left px-4 py-2 rounded-md text-sm font-medium bg-red-100 text-red-800 hover:bg-red-200 transition">Delete Report</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Uptime Score -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="uptimeScoreEditor()">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Uptime Score</h3>
                        @if(!in_array($report->status, ['sent', 'archived']))
                            <div class="flex items-center gap-2">
                                <input type="number" x-model="score" step="0.01" min="0" max="100" placeholder="e.g., 99.95"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <button @click="saveScore()" :disabled="saving" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                                    <span x-text="saving ? 'Saving...' : 'Save'"></span>
                                </button>
                                <span x-show="message" x-cloak class="text-xs" :class="messageError ? 'text-red-600' : 'text-green-600'" x-text="message"></span>
                            </div>
                        @else
                            <div class="text-2xl font-bold {{ $report->uptime_score !== null ? 'text-green-700' : 'text-gray-400' }}">
                                {{ $report->uptime_score !== null ? number_format($report->uptime_score, 2) . '%' : 'N/A' }}
                            </div>
                        @endif

                        <script>
                            function uptimeScoreEditor() {
                                return {
                                    score: '{{ $report->uptime_score ?? "" }}',
                                    saving: false,
                                    message: '',
                                    messageError: false,
                                    async saveScore() {
                                        this.saving = true;
                                        this.message = '';
                                        try {
                                            const resp = await fetch(`/reports/{{ $report->id }}/uptime-score`, {
                                                method: 'PUT',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({ uptime_score: this.score || null })
                                            });
                                            if (resp.ok) {
                                                this.message = 'Saved';
                                                this.messageError = false;
                                                setTimeout(() => this.message = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.message = err.message || 'Failed to save.';
                                                this.messageError = true;
                                            }
                                        } catch (e) {
                                            this.message = 'Network error.';
                                            this.messageError = true;
                                        }
                                        this.saving = false;
                                    }
                                }
                            }
                        </script>
                    </div>

                    <!-- Invoice Link -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Linked Invoice</h3>
                        @if($report->invoice)
                            <div class="flex justify-between items-center">
                                <a href="{{ route('invoices.show', $report->invoice) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">{{ $report->invoice->invoice_number }}</a>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $report->invoice->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $report->invoice->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $report->invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $report->invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $report->invoice->status === 'cancelled' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                ">${{ number_format($report->invoice->total, 2) }}</span>
                            </div>
                            @if(!in_array($report->status, ['sent', 'archived']))
                                <form method="POST" action="{{ route('reports.unlink-invoice', $report) }}" class="mt-2">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">Unlink</button>
                                </form>
                            @endif
                        @else
                            <p class="text-sm text-gray-500 mb-2">No invoice linked.</p>
                            @if(!in_array($report->status, ['sent', 'archived']))
                                <div x-data="{ showLink: false, invoices: [], loading: false }">
                                    <button @click="showLink = !showLink; if(showLink && invoices.length === 0) { loading = true; fetch('/api/clients/{{ $report->client_id }}/invoices', {headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{invoices=d;loading=false;}).catch(()=>{loading=false;}); }" class="text-sm text-indigo-600 hover:text-indigo-800">+ Link Invoice</button>
                                    <div x-show="showLink" x-cloak class="mt-2">
                                        <form method="POST" action="{{ route('reports.link-invoice', $report) }}" class="space-y-2">
                                            @csrf
                                            <select name="invoice_id" required class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option value="">Select invoice...</option>
                                                <template x-for="inv in invoices" :key="inv.id">
                                                    <option :value="inv.id" x-text="inv.invoice_number + ' ($' + Number(inv.total).toFixed(2) + ')'"></option>
                                                </template>
                                            </select>
                                            <button type="submit" class="w-full px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">Link</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- AI Preferences -->
                    @if(!empty($preferences->rules) && count($preferences->rules) > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">AI Preferences</h3>
                                <span class="text-xs text-gray-400">{{ count($preferences->rules) }} rules</span>
                            </div>
                            <ul class="space-y-1">
                                @foreach($preferences->rules as $rule)
                                    <li class="text-xs text-gray-600 flex items-start gap-1.5">
                                        <span class="mt-1 w-1 h-1 rounded-full bg-indigo-400 flex-shrink-0"></span>
                                        {{ Str::limit($rule, 80) }}
                                    </li>
                                @endforeach
                            </ul>
                            @if($preferences->last_distilled_at)
                                <p class="text-xs text-gray-400 mt-3">Updated {{ $preferences->last_distilled_at->diffForHumans() }}</p>
                            @endif
                            @can('manage-settings')
                                <a href="{{ route('settings.report-preferences') }}" class="text-xs text-indigo-600 hover:text-indigo-800 mt-2 inline-block">View all preferences</a>
                            @endcan
                        </div>
                    @endif

                    <!-- Status History -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">History</h3>
                        @foreach($report->statusHistory as $history)
                            <div class="py-2 border-b last:border-b-0 text-sm">
                                <div class="flex justify-between">
                                    <span>{{ $history->from_status ? ucfirst($history->from_status) . ' → ' : '' }}{{ ucfirst($history->to_status) }}</span>
                                    <span class="text-xs text-gray-400">{{ $history->created_at->format('M d, H:i') }}</span>
                                </div>
                                @if($history->changedBy)<div class="text-xs text-gray-500">by {{ $history->changedBy->name }}</div>@endif
                                @if($history->notes)<div class="text-xs text-gray-500 mt-0.5">{{ $history->notes }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
