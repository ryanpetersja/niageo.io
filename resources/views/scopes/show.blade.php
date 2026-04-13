<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Scopes', 'url' => route('scopes.index')], ['label' => $scope->scope_number]]" />
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $scope->scope_number }}</h2>
            <div class="flex gap-2">
                @if(in_array($scope->status, ['draft', 'sent', 'approved']))
                    <a href="{{ route('scopes.pdf', $scope) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-gray-700 transition">Preview PDF</a>
                    <a href="{{ route('scopes.pdf.download', $scope) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition">Download PDF</a>
                @endif
                @if($scope->status === 'draft')
                    <a href="{{ route('scopes.edit', $scope) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase text-gray-700 hover:bg-gray-50 transition">Edit</a>
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
                                <h3 class="text-lg font-semibold">{{ $scope->title }}</h3>
                                <div class="text-sm text-gray-500 mt-1">
                                    <a href="{{ route('clients.show', $scope->client) }}" class="text-indigo-600 hover:text-indigo-800">{{ $scope->client->company_name }}</a>
                                    &middot; Created by {{ $scope->creator->name }} on {{ $scope->created_at->format('M d, Y') }}
                                </div>
                                @if($scope->description)
                                    <p class="text-sm text-gray-600 mt-2">{{ $scope->description }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $scope->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $scope->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $scope->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $scope->status === 'archived' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            ">{{ ucfirst($scope->status) }}</span>
                        </div>

                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div><span class="text-gray-500">Items</span><div class="font-medium">{{ $scope->items->count() }}</div></div>
                            <div><span class="text-gray-500">Mandatory</span><div class="font-medium">{{ $scope->items->where('is_mandatory', true)->count() }}</div></div>
                            <div><span class="text-gray-500">Total Value</span><div class="font-medium text-green-700">{{ $scope->currency_symbol }}{{ number_format($scope->total_price, 2) }}</div></div>
                        </div>
                    </div>

                    <!-- Sections -->
                    @if($scope->sections)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="sectionEditor()">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Scope Content</h3>
                                @if($scope->status === 'draft')
                                    <div class="flex items-center gap-2">
                                        <template x-if="dirty">
                                            <button @click="saveSections()" :disabled="saving" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
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

                            <div class="space-y-6">
                                <template x-for="sec in sectionOrder" :key="sec.key">
                                    <div x-show="sections[sec.key] || editing">
                                        <h4 class="font-semibold text-sm text-gray-800 mb-2 flex items-center gap-2">
                                            <span x-text="sec.label"></span>
                                            @if($scope->status === 'draft')
                                                <button x-show="!editing && sections[sec.key]" @click="openRefine(sec.key)" class="text-xs text-indigo-500 hover:text-indigo-700 font-normal">Refine with AI</button>
                                            @endif
                                        </h4>

                                        <template x-if="!editing">
                                            <div class="text-sm text-gray-600 whitespace-pre-line" x-text="sections[sec.key]"></div>
                                        </template>
                                        <template x-if="editing">
                                            <textarea x-model="sections[sec.key]" @input="dirty = true" rows="4"
                                                class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                        </template>

                                        <!-- Refine popover -->
                                        <div x-show="refineKey === sec.key" x-cloak class="mt-2 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                                            <textarea x-model="refineInstruction" rows="2" placeholder="e.g., Make this more concise, add more detail about timeline..."
                                                class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                            <div class="flex justify-end gap-2 mt-2">
                                                <button @click="refineKey = null" class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                                <button @click="submitRefine(sec.key)" :disabled="refining || !refineInstruction.trim()"
                                                    class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                                                    <span x-text="refining ? 'Refining...' : 'Refine'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <script>
                            function sectionEditor() {
                                return {
                                    sections: @json($scope->sections ?? []),
                                    editing: false,
                                    dirty: false,
                                    saving: false,
                                    saveMessage: '',
                                    saveError: false,
                                    refineKey: null,
                                    refineInstruction: '',
                                    refining: false,
                                    sectionOrder: [
                                        { key: 'purpose_statement', label: 'Purpose Statement' },
                                        { key: 'problem_description', label: 'Problem Description' },
                                        { key: 'solution_overview', label: 'Solution Overview' },
                                        { key: 'goals_objectives', label: 'Goals & Objectives' },
                                        { key: 'assumptions', label: 'Assumptions' },
                                        { key: 'out_of_scope', label: 'Out of Scope' },
                                        { key: 'timeline_summary', label: 'Timeline Summary' },
                                        { key: 'next_steps', label: 'Next Steps' },
                                    ],

                                    openRefine(key) {
                                        this.refineKey = this.refineKey === key ? null : key;
                                        this.refineInstruction = '';
                                    },

                                    async submitRefine(key) {
                                        this.refining = true;
                                        try {
                                            const resp = await fetch(`/scopes/{{ $scope->id }}/refine-section`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({ section_key: key, instruction: this.refineInstruction })
                                            });
                                            if (resp.ok) {
                                                const data = await resp.json();
                                                this.sections = data.sections;
                                                this.refineKey = null;
                                                this.saveMessage = 'Section refined and saved.';
                                                this.saveError = false;
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.saveMessage = err.message || 'Failed to refine.';
                                                this.saveError = true;
                                            }
                                        } catch (e) {
                                            this.saveMessage = 'Network error.';
                                            this.saveError = true;
                                        }
                                        this.refining = false;
                                    },

                                    async saveSections() {
                                        this.saving = true;
                                        this.saveMessage = '';
                                        try {
                                            const resp = await fetch(`/scopes/{{ $scope->id }}/sections`, {
                                                method: 'PUT',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({ sections: this.sections })
                                            });
                                            if (resp.ok) {
                                                this.dirty = false;
                                                this.saveMessage = 'Sections saved.';
                                                this.saveError = false;
                                                setTimeout(() => this.saveMessage = '', 3000);
                                            } else {
                                                const err = await resp.json();
                                                this.saveMessage = err.message || 'Failed to save.';
                                                this.saveError = true;
                                            }
                                        } catch (e) {
                                            this.saveMessage = 'Network error.';
                                            this.saveError = true;
                                        }
                                        this.saving = false;
                                    }
                                }
                            }
                        </script>
                    @elseif($scope->status === 'draft')
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                            <div class="py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No scope content yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Generate content with AI or add sections manually via Edit.</p>
                                @if($scope->description)
                                    <form method="POST" action="{{ route('scopes.generate', $scope) }}" class="mt-4">
                                        @csrf
                                        <input type="hidden" name="generate" value="all">
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-indigo-700 transition">Generate with AI</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Scope Items -->
                    @if($scope->items->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Scope Items</h3>
                                @if($scope->status === 'draft')
                                    <a href="{{ route('scopes.edit', $scope) }}#items" class="text-sm text-indigo-600 hover:text-indigo-800">Edit Items</a>
                                @endif
                            </div>

                            @php
                                $grouped = $scope->items->groupBy('category');
                            @endphp

                            @foreach($grouped as $category => $items)
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 border-b pb-2">{{ $category ?: 'General' }}</h4>
                                    <div class="space-y-3">
                                        @foreach($items as $item)
                                            <div class="flex items-start justify-between p-3 bg-gray-50 rounded-lg">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-medium text-sm text-gray-800">{{ $item->title }}</span>
                                                        @if($item->is_mandatory)
                                                            <span class="px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-[10px] font-semibold uppercase">Required</span>
                                                        @elseif($item->is_recommended)
                                                            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-semibold uppercase">Recommended</span>
                                                        @else
                                                            <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px] font-semibold uppercase">Optional</span>
                                                        @endif
                                                    </div>
                                                    @if($item->description)
                                                        <p class="text-xs text-gray-500 mt-1">{{ $item->description }}</p>
                                                    @endif
                                                    @if($item->business_value_statement)
                                                        <p class="text-xs text-indigo-600 mt-1 italic">{{ $item->business_value_statement }}</p>
                                                    @endif
                                                </div>
                                                <div class="text-right ml-4 flex-shrink-0">
                                                    <div class="font-semibold text-sm text-gray-800">{{ $scope->currency_symbol }}{{ number_format($item->price, 2) }}</div>
                                                    @if($item->effort_description)
                                                        <div class="text-[10px] text-gray-400">{{ $item->effort_description }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <!-- Totals -->
                            <div class="border-t pt-4 mt-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Mandatory Items</span>
                                    <span class="font-medium">{{ $scope->currency_symbol }}{{ number_format($scope->mandatory_total, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Optional Items</span>
                                    <span class="font-medium">{{ $scope->currency_symbol }}{{ number_format($scope->total_price - $scope->mandatory_total, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-base font-bold border-t pt-2">
                                    <span>Total</span>
                                    <span class="text-indigo-700">{{ $scope->currency_symbol }}{{ number_format($scope->total_price, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar (1/3) -->
                <div class="space-y-6">
                    <!-- Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Actions</h3>
                        <div class="space-y-2">
                            @if($scope->status === 'draft')
                                @if($scope->description)
                                    <form method="POST" action="{{ route('scopes.generate', $scope) }}">
                                        @csrf
                                        <input type="hidden" name="generate" value="all">
                                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-indigo-700 transition text-center">
                                            {{ $scope->sections ? 'Regenerate with AI' : 'Generate with AI' }}
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('scopes.send', $scope) }}">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-blue-700 transition text-center">Mark as Sent</button>
                                </form>
                            @endif
                            @if($scope->status === 'sent')
                                <form method="POST" action="{{ route('scopes.approve', $scope) }}">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition text-center">Mark as Approved</button>
                                </form>
                            @endif
                            @if($scope->status !== 'archived')
                                <form method="POST" action="{{ route('scopes.destroy', $scope) }}" x-data x-on:submit.prevent="if(confirm('Delete this scope?')) $el.submit()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full px-4 py-2 bg-white border border-red-300 text-red-600 rounded-md text-xs font-semibold uppercase hover:bg-red-50 transition text-center">Delete Scope</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Linked Invoice -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ showLink: false, invoices: [] }">
                        <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Linked Invoice</h3>
                        @if($scope->invoice)
                            <div class="flex items-center justify-between">
                                <div>
                                    <a href="{{ route('invoices.show', $scope->invoice) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">{{ $scope->invoice->invoice_number }}</a>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium ml-1
                                        {{ $scope->invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $scope->invoice->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $scope->invoice->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $scope->invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                    ">{{ ucfirst($scope->invoice->status) }}</span>
                                    <div class="text-sm font-semibold mt-1">${{ number_format($scope->invoice->total, 2) }}</div>
                                </div>
                                <form method="POST" action="{{ route('scopes.unlink-invoice', $scope) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Unlink</button>
                                </form>
                            </div>
                        @else
                            <button @click="showLink = !showLink; if(showLink && invoices.length === 0) { fetch('/api/clients/{{ $scope->client_id }}/invoices', { headers: {'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'} }).then(r => r.json()).then(d => invoices = d) }"
                                class="text-sm text-indigo-600 hover:text-indigo-800">+ Link Invoice</button>
                            <div x-show="showLink" x-cloak class="mt-3">
                                <form method="POST" action="{{ route('scopes.link-invoice', $scope) }}">
                                    @csrf
                                    <select name="invoice_id" required class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-2">
                                        <option value="">Select invoice...</option>
                                        <template x-for="inv in invoices" :key="inv.id">
                                            <option :value="inv.id" x-text="inv.invoice_number + ' — $' + Number(inv.total).toFixed(2) + ' (' + inv.status + ')'"></option>
                                        </template>
                                    </select>
                                    <button type="submit" class="w-full px-3 py-1.5 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700">Link</button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Details</h3>
                        <dl class="space-y-3 text-sm">
                            <div><dt class="text-gray-500">Currency</dt><dd class="font-medium">{{ $scope->currency }}</dd></div>
                            @if($scope->sent_at)
                                <div><dt class="text-gray-500">Sent</dt><dd class="font-medium">{{ $scope->sent_at->format('M d, Y') }}</dd></div>
                            @endif
                            @if($scope->approved_at)
                                <div><dt class="text-gray-500">Approved</dt><dd class="font-medium">{{ $scope->approved_at->format('M d, Y') }}</dd></div>
                            @endif
                            @if($scope->notes)
                                <div><dt class="text-gray-500">Notes</dt><dd class="text-gray-600">{{ $scope->notes }}</dd></div>
                            @endif
                            @if($scope->internal_notes)
                                <div><dt class="text-gray-500">Internal Notes</dt><dd class="text-gray-600">{{ $scope->internal_notes }}</dd></div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
