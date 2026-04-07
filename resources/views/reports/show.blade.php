<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Report {{ $report->report_number }}</h2>
            <div class="flex gap-2">
                @if(in_array($report->status, ['generated', 'sent', 'archived']))
                    <a href="{{ route('reports.pdf', $report) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-gray-700 transition">Preview PDF</a>
                    <a href="{{ route('reports.pdf.download', $report) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition">Download PDF</a>
                @endif
                @if($report->status === 'draft')
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

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                            <div><span class="text-gray-500">Period</span><div class="font-medium">{{ $report->date_from->format('M d') }} — {{ $report->date_to->format('M d, Y') }}</div></div>
                            <div><span class="text-gray-500">Commits</span><div class="font-medium">{{ $report->commit_count }}</div></div>
                            <div><span class="text-gray-500">Repositories</span><div class="font-medium">{{ $report->repo_count }}</div></div>
                            <div><span class="text-gray-500">Server Commands</span><div class="font-medium">{{ $report->raw_server_activity ? count($report->raw_server_activity) : 0 }}</div></div>
                            <div><span class="text-gray-500">Summary Items</span><div class="font-medium">{{ $report->summary_item_count }}</div></div>
                        </div>
                    </div>

                    <!-- AI Summary -->
                    @if($report->has_summary)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="summaryEditor()">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Development Summary</h3>
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

                            <div class="space-y-4">
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
                                                <li class="text-sm text-gray-700 flex items-start gap-2">
                                                    <template x-if="!editing">
                                                        <span class="flex items-start gap-2">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-current flex-shrink-0" :class="cat.textClass"></span>
                                                            <span x-text="item"></span>
                                                        </span>
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
                                    editing: false,
                                    dirty: false,
                                    saving: false,
                                    saveMessage: '',
                                    saveError: false,
                                    reportId: {{ $report->id }},
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
                                        this.dirty = true;
                                    },

                                    removeItem(category, index) {
                                        this.summary[category].splice(index, 1);
                                        this.dirty = true;
                                    },

                                    async save() {
                                        this.saving = true;
                                        this.saveMessage = '';
                                        this.saveError = false;

                                        // Filter out empty strings
                                        const cleaned = {};
                                        for (const key of ['features', 'bugs', 'improvements', 'security', 'infrastructure']) {
                                            cleaned[key] = (this.summary[key] || []).filter(s => s.trim() !== '');
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

                            <div class="space-y-4">
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
                                                <li class="text-sm text-gray-700 flex items-start gap-2">
                                                    <template x-if="!editing">
                                                        <span class="flex items-start gap-2">
                                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-current flex-shrink-0" :class="cat.textClass"></span>
                                                            <span x-text="item"></span>
                                                        </span>
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
                    @if(in_array($report->status, ['generated', 'sent']))
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ showFeedbackForm: false }">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Report Feedback</h3>
                                <button @click="showFeedbackForm = !showFeedbackForm" class="text-sm text-indigo-600 hover:text-indigo-800" x-text="showFeedbackForm ? 'Cancel' : 'Give Feedback'"></button>
                            </div>

                            <div x-show="showFeedbackForm" x-cloak class="mb-4">
                                <form method="POST" action="{{ route('reports.feedback', $report) }}">
                                    @csrf
                                    <textarea name="feedback" rows="3" required maxlength="2000"
                                        placeholder="e.g., Don't list routine updates as features. Be more specific about what changed for end users."
                                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('feedback') }}</textarea>
                                    <p class="text-xs text-gray-400 mt-1">This feedback will be distilled into reusable rules that guide all future AI-generated reports.</p>
                                    @error('feedback')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                    <div class="flex justify-end mt-2">
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-indigo-700 transition">Submit Feedback</button>
                                    </div>
                                </form>
                            </div>

                            @if($report->feedback->count() > 0)
                                <div class="space-y-2">
                                    @foreach($report->feedback as $entry)
                                        <div class="p-3 bg-gray-50 rounded text-sm">
                                            <div class="flex justify-between items-start">
                                                <p class="text-gray-700">{{ $entry->feedback }}</p>
                                                @if($entry->processed)
                                                    <span class="ml-2 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Processed</span>
                                                @else
                                                    <span class="ml-2 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-400 mt-1">{{ $entry->user->name }} &middot; {{ $entry->created_at->format('M d, Y H:i') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(!$report->feedback->count() && !isset($showFeedbackForm))
                                <p class="text-sm text-gray-500">No feedback submitted for this report yet.</p>
                            @endif
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
