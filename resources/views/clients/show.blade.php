<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $client->company_name }}</h2>
            <div class="flex gap-2 items-center">
                <div x-data="{ open: false, from: '{{ now()->startOfYear()->format('Y-m-d') }}', to: '{{ now()->format('Y-m-d') }}' }" class="relative">
                    <button @click="open = !open" type="button" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 transition">
                        Generate Statement
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 p-4 z-50">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Statement Date Range</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                                <input type="date" x-model="from" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                                <input type="date" x-model="to" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="flex gap-2 pt-1">
                                <a :href="`{{ route('clients.statement', $client) }}?from=${from}&to=${to}`" target="_blank" class="flex-1 text-center px-3 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-indigo-700 transition">Preview</a>
                                <a :href="`{{ route('clients.statement.download', $client) }}?from=${from}&to=${to}`" target="_blank" class="flex-1 text-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-xs font-semibold uppercase hover:bg-gray-50 transition">Download</a>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('reports.create', ['client_id' => $client->id]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">New Report</a>
                <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">New Invoice</a>
                <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Client Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Details</h3>
                    <dl class="space-y-3">
                        <div><dt class="text-sm text-gray-500">Billing Terms</dt><dd class="font-medium">{{ $client->billing_terms_label }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Billing Email</dt><dd class="font-medium">{{ $client->billing_email ?: 'N/A' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Outstanding Balance</dt><dd class="font-medium text-red-600">${{ number_format($client->outstanding_balance, 2) }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Status</dt><dd><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $client->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $client->is_active ? 'Active' : 'Inactive' }}</span></dd></div>
                        @if($client->notes)
                            <div><dt class="text-sm text-gray-500">Notes</dt><dd class="text-sm">{{ $client->notes }}</dd></div>
                        @endif
                    </dl>
                </div>

                <!-- Contacts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="contactManager()">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Contacts</h3>
                        <button @click="showForm = !showForm" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add</button>
                    </div>

                    <template x-if="showForm">
                        <form method="POST" action="{{ route('clients.show', $client) }}" @submit.prevent="saveContact" class="mb-4 p-3 bg-gray-50 rounded-lg space-y-2">
                            <input type="text" x-model="newContact.name" placeholder="Name" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                            <input type="email" x-model="newContact.email" placeholder="Email" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <input type="text" x-model="newContact.phone" placeholder="Phone" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" x-model="newContact.is_primary" class="rounded border-gray-300 text-indigo-600">
                                <label class="text-sm text-gray-600">Primary contact</label>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Save</button>
                                <button type="button" @click="showForm = false" class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-sm">Cancel</button>
                            </div>
                        </form>
                    </template>

                    @forelse($client->contacts as $contact)
                        <div class="py-2 border-b last:border-b-0">
                            <div class="font-medium text-sm">{{ $contact->name }} @if($contact->is_primary)<span class="text-xs text-indigo-600">(Primary)</span>@endif</div>
                            @if($contact->email)<div class="text-xs text-gray-500">{{ $contact->email }}</div>@endif
                            @if($contact->phone)<div class="text-xs text-gray-500">{{ $contact->phone }}</div>@endif
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No contacts yet.</p>
                    @endforelse

                    <script>
                        function contactManager() {
                            return {
                                showForm: false,
                                newContact: { name: '', email: '', phone: '', is_primary: false },
                                async saveContact() {
                                    const resp = await fetch('/clients/{{ $client->id }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                        body: JSON.stringify({ _action: 'add_contact', ...this.newContact })
                                    });
                                    if (resp.ok) location.reload();
                                }
                            }
                        }
                    </script>
                </div>

                <!-- Pricing Presets -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="presetManager()">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Pricing Presets</h3>
                        <button @click="openCreateForm()" x-show="!showForm" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add</button>
                    </div>

                    <!-- Create / Edit Form -->
                    <template x-if="showForm">
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg space-y-3">
                            <div class="flex items-center gap-3">
                                <input type="text" x-model="form.name" placeholder="Preset name" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <label class="flex items-center gap-1.5 text-sm text-gray-600 whitespace-nowrap">
                                    <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Active
                                </label>
                            </div>

                            <!-- Items Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                                            <th class="pb-2">Description</th>
                                            <th class="pb-2 w-20">Qty</th>
                                            <th class="pb-2 w-24">Price</th>
                                            <th class="pb-2 w-20 text-right">Total</th>
                                            <th class="pb-2 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, index) in form.items" :key="index">
                                            <tr>
                                                <td class="py-1 pr-2">
                                                    <input type="text" x-model="item.description" placeholder="Line item description" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                </td>
                                                <td class="py-1 pr-2">
                                                    <input type="number" x-model.number="item.quantity" step="0.01" min="0.01" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                </td>
                                                <td class="py-1 pr-2">
                                                    <input type="number" x-model.number="item.unit_price" step="0.01" min="0" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                </td>
                                                <td class="py-1 text-right text-gray-600 text-sm" x-text="'$' + ((item.quantity || 0) * (item.unit_price || 0)).toFixed(2)"></td>
                                                <td class="py-1 pl-1">
                                                    <button type="button" @click="removeItem(index)" x-show="form.items.length > 1" class="text-red-400 hover:text-red-600" title="Remove item">&times;</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-between items-center">
                                <button type="button" @click="addItem()" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add Item</button>
                                <div class="text-sm font-semibold text-gray-700">
                                    Total: $<span x-text="grandTotal()"></span>
                                </div>
                            </div>

                            <div class="flex gap-2 pt-1">
                                <button type="button" @click="savePreset()" :disabled="saving" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 disabled:opacity-50">
                                    <span x-text="saving ? 'Saving...' : (editingId ? 'Update Preset' : 'Save Preset')"></span>
                                </button>
                                <button type="button" @click="cancelForm()" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Cancel</button>
                            </div>

                            <template x-if="formError">
                                <p class="text-red-600 text-xs" x-text="formError"></p>
                            </template>
                        </div>
                    </template>

                    <!-- Preset List -->
                    <template x-for="preset in presets" :key="preset.id">
                        <div class="py-2 border-b last:border-b-0">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-sm" x-text="preset.name"></span>
                                    <span x-show="!preset.is_active" class="text-xs text-gray-400 italic">(inactive)</span>
                                </div>
                                <span class="text-sm font-medium text-gray-600" x-text="'$' + presetTotal(preset).toFixed(2)"></span>
                            </div>
                            <div class="mt-1 space-y-0.5">
                                <template x-for="item in preset.items" :key="item.id">
                                    <div class="text-xs text-gray-500" x-text="item.description + ' (' + Number(item.quantity) + ' \u00d7 $' + Number(item.unit_price).toFixed(2) + ')'"></div>
                                </template>
                            </div>
                            <div class="mt-1.5 flex gap-2 justify-end">
                                <button @click="openEditForm(preset)" class="text-xs text-indigo-600 hover:text-indigo-800">Edit</button>
                                <button @click="deletePreset(preset)" class="text-xs text-red-600 hover:text-red-800">Delete</button>
                            </div>
                        </div>
                    </template>

                    <p x-show="presets.length === 0 && !showForm" class="text-gray-500 text-sm">No pricing presets.</p>

                    @php
                        $presetsJson = $client->pricingPresets->map(function ($p) {
                            return [
                                'id' => $p->id,
                                'name' => $p->name,
                                'is_active' => $p->is_active,
                                'items' => $p->items->map(function ($i) {
                                    return ['id' => $i->id, 'description' => $i->description, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price];
                                })->toArray(),
                            ];
                        })->toArray();
                    @endphp
                    <script>
                        function presetManager() {
                            return {
                                presets: @json($presetsJson),
                                showForm: false,
                                saving: false,
                                editingId: null,
                                formError: '',
                                form: { name: '', is_active: true, items: [{ description: '', quantity: 1, unit_price: 0 }] },
                                clientId: {{ $client->id }},

                                openCreateForm() {
                                    this.editingId = null;
                                    this.form = { name: '', is_active: true, items: [{ description: '', quantity: 1, unit_price: 0 }] };
                                    this.formError = '';
                                    this.showForm = true;
                                },

                                openEditForm(preset) {
                                    this.editingId = preset.id;
                                    this.form = {
                                        name: preset.name,
                                        is_active: preset.is_active,
                                        items: preset.items.map(i => ({ description: i.description, quantity: Number(i.quantity), unit_price: Number(i.unit_price) }))
                                    };
                                    this.formError = '';
                                    this.showForm = true;
                                },

                                cancelForm() {
                                    this.showForm = false;
                                    this.editingId = null;
                                    this.formError = '';
                                },

                                addItem() {
                                    this.form.items.push({ description: '', quantity: 1, unit_price: 0 });
                                },

                                removeItem(index) {
                                    this.form.items.splice(index, 1);
                                },

                                grandTotal() {
                                    return this.form.items.reduce((sum, i) => sum + ((i.quantity || 0) * (i.unit_price || 0)), 0).toFixed(2);
                                },

                                presetTotal(preset) {
                                    return preset.items.reduce((sum, i) => sum + (Number(i.quantity) * Number(i.unit_price)), 0);
                                },

                                async savePreset() {
                                    this.formError = '';
                                    if (!this.form.name.trim()) { this.formError = 'Preset name is required.'; return; }
                                    if (this.form.items.length === 0) { this.formError = 'At least one item is required.'; return; }
                                    for (const item of this.form.items) {
                                        if (!item.description.trim()) { this.formError = 'All items must have a description.'; return; }
                                        if (!item.quantity || item.quantity < 0.01) { this.formError = 'Quantity must be at least 0.01.'; return; }
                                        if (item.unit_price === null || item.unit_price === '' || item.unit_price < 0) { this.formError = 'Unit price cannot be negative.'; return; }
                                    }

                                    this.saving = true;
                                    const url = this.editingId
                                        ? `/clients/${this.clientId}/presets/${this.editingId}`
                                        : `/clients/${this.clientId}/presets`;
                                    const method = this.editingId ? 'PUT' : 'POST';

                                    try {
                                        const resp = await fetch(url, {
                                            method,
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify(this.form)
                                        });

                                        if (!resp.ok) {
                                            const err = await resp.json();
                                            this.formError = err.message || 'Failed to save preset.';
                                            this.saving = false;
                                            return;
                                        }

                                        const data = await resp.json();
                                        const saved = data.preset;

                                        if (this.editingId) {
                                            const idx = this.presets.findIndex(p => p.id === this.editingId);
                                            if (idx !== -1) this.presets[idx] = saved;
                                        } else {
                                            this.presets.push(saved);
                                        }

                                        this.showForm = false;
                                        this.editingId = null;
                                    } catch (e) {
                                        this.formError = 'Network error. Please try again.';
                                    }
                                    this.saving = false;
                                },

                                async deletePreset(preset) {
                                    if (!confirm(`Delete preset "${preset.name}"? This cannot be undone.`)) return;

                                    try {
                                        const resp = await fetch(`/clients/${this.clientId}/presets/${preset.id}`, {
                                            method: 'DELETE',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json'
                                            }
                                        });

                                        if (resp.ok) {
                                            this.presets = this.presets.filter(p => p.id !== preset.id);
                                        }
                                    } catch (e) {
                                        alert('Failed to delete preset.');
                                    }
                                }
                            }
                        }
                    </script>
                </div>
            </div>

            <!-- GitHub Repositories -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="repoManager()">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">GitHub Repositories</h3>
                    <button @click="openForm()" x-show="!showForm" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add</button>
                </div>

                <template x-if="showForm">
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <!-- Mode Toggle -->
                        <div class="flex items-center gap-3 mb-3">
                            <button type="button" @click="manualMode = false" :class="!manualMode ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300'" class="px-3 py-1 rounded text-xs font-semibold transition">Browse Repos</button>
                            <button type="button" @click="manualMode = true" :class="manualMode ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300'" class="px-3 py-1 rounded text-xs font-semibold transition">Enter Manually</button>
                        </div>

                        <!-- Browse Mode -->
                        <div x-show="!manualMode" class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Repository</label>
                                <div x-show="loadingRepos" class="text-sm text-gray-400 py-2">Loading repositories...</div>
                                <select x-show="!loadingRepos" x-model="selectedRepoFullName" @change="onRepoSelected()"
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select a repository...</option>
                                    <template x-for="gr in githubRepos" :key="gr.full_name">
                                        <option :value="gr.full_name" x-text="gr.full_name + (gr.private ? ' (private)' : '')"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Branch Select -->
                            <div x-show="selectedRepoFullName">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Default Branch</label>
                                <div x-show="loadingBranches" class="text-sm text-gray-400 py-2">Loading branches...</div>
                                <select x-show="!loadingBranches" x-model="newRepo.default_branch"
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <template x-for="br in branches" :key="br">
                                        <option :value="br" x-text="br"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Manual Entry Mode -->
                        <div x-show="manualMode" class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Owner (username or org)</label>
                                <input type="text" x-model="newRepo.owner" placeholder="e.g., octocat" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Repository Name</label>
                                <input type="text" x-model="newRepo.repo_name" placeholder="e.g., my-project" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Default Branch</label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="newRepo.default_branch" placeholder="main" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <button type="button" @click="fetchManualBranches()" :disabled="!newRepo.owner || !newRepo.repo_name || loadingBranches" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded text-xs hover:bg-gray-300 disabled:opacity-50 whitespace-nowrap">
                                        <span x-text="loadingBranches ? 'Loading...' : 'Fetch Branches'"></span>
                                    </button>
                                </div>
                                <template x-if="manualBranches.length > 0">
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        <template x-for="br in manualBranches" :key="br">
                                            <button type="button" @click="newRepo.default_branch = br" :class="newRepo.default_branch === br ? 'bg-indigo-100 text-indigo-800 border-indigo-300' : 'bg-gray-100 text-gray-600 border-gray-200'" class="px-2 py-0.5 rounded border text-xs hover:bg-indigo-50 transition" x-text="br"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-gray-400">Your GitHub token must have access to this repo. It will be validated before adding.</p>
                        </div>

                        <div class="flex gap-2 mt-3">
                            <button type="button" @click="addRepo()" :disabled="saving || (!manualMode && !selectedRepoFullName) || (manualMode && (!newRepo.owner || !newRepo.repo_name))" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 disabled:opacity-50">
                                <span x-text="saving ? 'Adding...' : 'Add Repository'"></span>
                            </button>
                            <button type="button" @click="closeForm()" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Cancel</button>
                        </div>
                        <template x-if="formError">
                            <p class="text-red-600 text-xs mt-2" x-text="formError"></p>
                        </template>
                    </div>
                </template>

                <div class="space-y-0">
                    <template x-for="repo in repos" :key="repo.id">
                        <div class="flex justify-between items-center py-2.5 border-b last:border-b-0">
                            <div>
                                <span class="font-medium text-sm" x-text="repo.full_name"></span>
                                <span class="text-xs text-gray-400 ml-2" x-text="repo.default_branch"></span>
                                <span x-show="!repo.is_active" class="text-xs text-gray-400 italic ml-1">(inactive)</span>
                            </div>
                            <button @click="deleteRepo(repo)" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                        </div>
                    </template>
                </div>

                <p x-show="repos.length === 0 && !showForm" class="text-gray-500 text-sm">No repositories linked. Add GitHub repos to enable report generation.</p>

                @php
                    $reposJson = $client->repositories->map(function ($r) {
                        return [
                            'id' => $r->id,
                            'owner' => $r->owner,
                            'repo_name' => $r->repo_name,
                            'default_branch' => $r->default_branch,
                            'is_active' => $r->is_active,
                            'full_name' => $r->full_name,
                        ];
                    })->toArray();
                @endphp
                <script>
                    function repoManager() {
                        return {
                            repos: @json($reposJson),
                            showForm: false,
                            saving: false,
                            formError: '',
                            newRepo: { owner: '', repo_name: '', default_branch: 'main' },
                            clientId: {{ $client->id }},

                            // GitHub API data
                            githubRepos: [],
                            branches: [],
                            manualBranches: [],
                            selectedRepoFullName: '',
                            loadingRepos: false,
                            loadingBranches: false,
                            reposLoaded: false,
                            manualMode: false,

                            async openForm() {
                                this.showForm = true;
                                this.formError = '';
                                this.selectedRepoFullName = '';
                                this.branches = [];
                                this.manualBranches = [];
                                this.newRepo = { owner: '', repo_name: '', default_branch: 'main' };
                                this.manualMode = false;

                                if (!this.reposLoaded) {
                                    this.loadingRepos = true;
                                    try {
                                        const resp = await fetch('/api/github/repos', {
                                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                        });
                                        if (resp.ok) {
                                            const all = await resp.json();
                                            // Filter out repos already linked to this client
                                            const linked = new Set(this.repos.map(r => r.full_name));
                                            this.githubRepos = all.filter(r => !linked.has(r.full_name));
                                            this.reposLoaded = true;
                                        } else {
                                            this.formError = 'Failed to load repositories. Check your GitHub token.';
                                        }
                                    } catch (e) {
                                        this.formError = 'Network error loading repositories.';
                                    }
                                    this.loadingRepos = false;
                                } else {
                                    // Re-filter on re-open in case repos were added
                                    const linked = new Set(this.repos.map(r => r.full_name));
                                    this.githubRepos = this.githubRepos.filter(r => !linked.has(r.full_name));
                                }
                            },

                            closeForm() {
                                this.showForm = false;
                                this.formError = '';
                                this.selectedRepoFullName = '';
                                this.branches = [];
                                this.manualBranches = [];
                            },

                            async onRepoSelected() {
                                this.branches = [];
                                this.formError = '';
                                if (!this.selectedRepoFullName) return;

                                const selected = this.githubRepos.find(r => r.full_name === this.selectedRepoFullName);
                                if (!selected) return;

                                this.newRepo.owner = selected.owner;
                                this.newRepo.repo_name = selected.name;
                                this.newRepo.default_branch = selected.default_branch;

                                this.loadingBranches = true;
                                try {
                                    const resp = await fetch(`/api/github/branches?owner=${encodeURIComponent(selected.owner)}&repo=${encodeURIComponent(selected.name)}`, {
                                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                    });
                                    if (resp.ok) {
                                        this.branches = await resp.json();
                                        // Keep default_branch selected if it exists in branches
                                        if (!this.branches.includes(this.newRepo.default_branch) && this.branches.length > 0) {
                                            this.newRepo.default_branch = this.branches[0];
                                        }
                                    }
                                } catch (e) {
                                    // Silently fall back — branch dropdown just stays empty
                                }
                                this.loadingBranches = false;
                            },

                            async fetchManualBranches() {
                                if (!this.newRepo.owner || !this.newRepo.repo_name) return;
                                this.loadingBranches = true;
                                this.manualBranches = [];
                                this.formError = '';
                                try {
                                    const resp = await fetch(`/api/github/branches?owner=${encodeURIComponent(this.newRepo.owner)}&repo=${encodeURIComponent(this.newRepo.repo_name)}`, {
                                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                    });
                                    if (resp.ok) {
                                        this.manualBranches = await resp.json();
                                        if (this.manualBranches.length > 0 && !this.manualBranches.includes(this.newRepo.default_branch)) {
                                            this.newRepo.default_branch = this.manualBranches[0];
                                        }
                                    } else {
                                        this.formError = 'Could not fetch branches. Check that the owner/repo is correct and your token has access.';
                                    }
                                } catch (e) {
                                    this.formError = 'Network error fetching branches.';
                                }
                                this.loadingBranches = false;
                            },

                            async addRepo() {
                                this.formError = '';
                                if (this.manualMode) {
                                    if (!this.newRepo.owner.trim() || !this.newRepo.repo_name.trim()) {
                                        this.formError = 'Owner and repository name are required.';
                                        return;
                                    }
                                    this.newRepo.owner = this.newRepo.owner.trim();
                                    this.newRepo.repo_name = this.newRepo.repo_name.trim();
                                    if (!this.newRepo.default_branch.trim()) {
                                        this.newRepo.default_branch = 'main';
                                    }
                                } else {
                                    if (!this.newRepo.owner || !this.newRepo.repo_name) {
                                        this.formError = 'Please select a repository.';
                                        return;
                                    }
                                }

                                this.saving = true;
                                try {
                                    const resp = await fetch(`/clients/${this.clientId}/repositories`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify(this.newRepo)
                                    });

                                    if (!resp.ok) {
                                        const err = await resp.json();
                                        this.formError = err.message || 'Failed to add repository.';
                                        this.saving = false;
                                        return;
                                    }

                                    const data = await resp.json();
                                    this.repos.push(data.repository);

                                    // Remove from available list if it was from browse mode
                                    if (!this.manualMode) {
                                        this.githubRepos = this.githubRepos.filter(r => r.full_name !== this.selectedRepoFullName);
                                    }

                                    this.selectedRepoFullName = '';
                                    this.branches = [];
                                    this.manualBranches = [];
                                    this.newRepo = { owner: '', repo_name: '', default_branch: 'main' };
                                    this.showForm = false;
                                } catch (e) {
                                    this.formError = 'Network error. Please try again.';
                                }
                                this.saving = false;
                            },

                            async deleteRepo(repo) {
                                if (!confirm(`Remove "${repo.full_name}" from this client?`)) return;

                                try {
                                    const resp = await fetch(`/clients/${this.clientId}/repositories/${repo.id}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    });

                                    if (resp.ok) {
                                        this.repos = this.repos.filter(r => r.id !== repo.id);
                                        // Add back to available list if repos were loaded
                                        if (this.reposLoaded) {
                                            this.githubRepos.push({
                                                owner: repo.owner,
                                                name: repo.repo_name,
                                                full_name: repo.full_name,
                                                default_branch: repo.default_branch,
                                                private: false
                                            });
                                            this.githubRepos.sort((a, b) => a.full_name.localeCompare(b.full_name));
                                        }
                                    }
                                } catch (e) {
                                    alert('Failed to remove repository.');
                                }
                            }
                        }
                    }
                </script>
            </div>

            <!-- SSH Servers -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="serverManager()">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">SSH Servers</h3>
                    <button @click="openForm()" x-show="!showForm" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add</button>
                </div>

                <template x-if="showForm">
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                                <input type="text" x-model="newServer.label" placeholder="e.g., Production" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Host</label>
                                <input type="text" x-model="newServer.host" placeholder="IP or hostname" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Port</label>
                                <input type="number" x-model.number="newServer.port" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                                <input type="text" x-model="newServer.username" placeholder="root" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Auth Type</label>
                                <select x-model="newServer.auth_type" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="key">SSH Key</option>
                                    <option value="password">Password</option>
                                </select>
                            </div>
                            <div class="md:col-span-2" x-show="newServer.auth_type === 'key'">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Private Key Path</label>
                                <input type="text" x-model="newServer.private_key_path" placeholder="C:\Users\Ryan\.ssh\id_rsa" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="md:col-span-2" x-show="newServer.auth_type === 'password'">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                                <input type="password" x-model="newServer.password" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="flex gap-2 mt-3">
                            <button type="button" @click="testSsh()" :disabled="testing" class="px-3 py-1.5 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 disabled:opacity-50">
                                <span x-text="testing ? 'Testing...' : 'Test Connection'"></span>
                            </button>
                            <button type="button" @click="addServer()" :disabled="saving || !newServer.host || !newServer.label" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 disabled:opacity-50">
                                <span x-text="saving ? 'Adding...' : 'Add Server'"></span>
                            </button>
                            <button type="button" @click="closeForm()" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Cancel</button>
                        </div>
                        <template x-if="testResult !== null">
                            <p class="mt-2 text-xs font-medium" :class="testResult ? 'text-green-600' : 'text-red-600'" x-text="testMessage"></p>
                        </template>
                        <template x-if="formError">
                            <p class="text-red-600 text-xs mt-2" x-text="formError"></p>
                        </template>
                    </div>
                </template>

                <div class="space-y-0">
                    <template x-for="server in servers" :key="server.id">
                        <div class="flex justify-between items-center py-2.5 border-b last:border-b-0">
                            <div>
                                <span class="font-medium text-sm" x-text="server.label"></span>
                                <span class="text-xs text-gray-400 ml-2" x-text="server.username + '@' + server.host + ':' + server.port"></span>
                                <span class="text-xs ml-1 px-1.5 py-0.5 rounded" :class="server.auth_type === 'key' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'" x-text="server.auth_type === 'key' ? 'key' : 'password'"></span>
                            </div>
                            <button @click="deleteServer(server)" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                        </div>
                    </template>
                </div>

                <p x-show="servers.length === 0 && !showForm" class="text-gray-500 text-sm">No SSH servers linked. Add servers to include server activity in reports.</p>

                @php
                    $serversJson = $client->servers->map(function ($s) {
                        return [
                            'id' => $s->id,
                            'label' => $s->label,
                            'host' => $s->host,
                            'port' => $s->port,
                            'username' => $s->username,
                            'auth_type' => $s->auth_type,
                            'is_active' => $s->is_active,
                            'display_name' => $s->display_name,
                        ];
                    })->toArray();
                @endphp
                <script>
                    function serverManager() {
                        return {
                            servers: @json($serversJson),
                            showForm: false,
                            saving: false,
                            testing: false,
                            formError: '',
                            testResult: null,
                            testMessage: '',
                            newServer: { label: '', host: '', port: 22, username: 'root', auth_type: 'key', private_key_path: '', password: '' },
                            clientId: {{ $client->id }},

                            openForm() {
                                this.showForm = true;
                                this.formError = '';
                                this.testResult = null;
                                this.testMessage = '';
                                this.newServer = { label: '', host: '', port: 22, username: 'root', auth_type: 'key', private_key_path: '', password: '' };
                            },

                            closeForm() {
                                this.showForm = false;
                                this.formError = '';
                                this.testResult = null;
                            },

                            async testSsh() {
                                this.testing = true;
                                this.testResult = null;
                                this.formError = '';

                                try {
                                    const resp = await fetch('/api/test-ssh', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify(this.newServer)
                                    });

                                    const data = await resp.json();
                                    this.testResult = data.success;
                                    this.testMessage = data.message;
                                } catch (e) {
                                    this.testResult = false;
                                    this.testMessage = 'Network error. Please try again.';
                                }
                                this.testing = false;
                            },

                            async addServer() {
                                this.formError = '';
                                this.saving = true;

                                try {
                                    const resp = await fetch(`/clients/${this.clientId}/servers`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify(this.newServer)
                                    });

                                    if (!resp.ok) {
                                        const err = await resp.json();
                                        this.formError = err.message || 'Failed to add server.';
                                        this.saving = false;
                                        return;
                                    }

                                    const data = await resp.json();
                                    this.servers.push(data.server);
                                    this.showForm = false;
                                } catch (e) {
                                    this.formError = 'Network error. Please try again.';
                                }
                                this.saving = false;
                            },

                            async deleteServer(server) {
                                if (!confirm(`Remove "${server.display_name}" from this client?`)) return;

                                try {
                                    const resp = await fetch(`/clients/${this.clientId}/servers/${server.id}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    });

                                    if (resp.ok) {
                                        this.servers = this.servers.filter(s => s.id !== server.id);
                                    }
                                } catch (e) {
                                    alert('Failed to remove server.');
                                }
                            }
                        }
                    }
                </script>
            </div>

            <!-- Monitored Endpoints -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Monitored Endpoints</h3>
                    <a href="{{ route('uptime.create', ['client_id' => $client->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add</a>
                </div>
                @forelse($client->monitoredEndpoints as $endpoint)
                    <div class="flex justify-between items-center py-2.5 border-b last:border-b-0">
                        <div class="flex items-center">
                            @php
                                $epColor = match($endpoint->current_status) {
                                    'up' => 'green',
                                    'degraded' => 'yellow',
                                    'down' => 'red',
                                    default => 'gray',
                                };
                            @endphp
                            <div class="w-2.5 h-2.5 rounded-full bg-{{ $epColor }}-500 mr-3"></div>
                            <div>
                                <a href="{{ route('uptime.show', $endpoint) }}" class="font-medium text-sm text-indigo-600 hover:text-indigo-800">{{ $endpoint->name }}</a>
                                <span class="text-xs text-gray-400 ml-2">{{ $endpoint->url }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            @if($endpoint->last_response_time_ms !== null)
                                <span class="text-gray-500">{{ $endpoint->last_response_time_ms }}ms</span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $endpoint->last_checked_at ? $endpoint->last_checked_at->diffForHumans() : 'Never' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No endpoints monitored. Add endpoints to track uptime.</p>
                @endforelse
            </div>

            <!-- Recent Reports -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Reports</h3>
                    <a href="{{ route('reports.create', ['client_id' => $client->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">+ New Report</a>
                </div>
                @forelse($client->reports()->latest()->take(5)->get() as $report)
                    <div class="flex justify-between items-center py-3 border-b last:border-b-0">
                        <div>
                            <a href="{{ route('reports.show', $report) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $report->report_number }}</a>
                            <span class="text-sm text-gray-500 ml-2">{{ $report->title }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $report->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $report->status === 'generated' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $report->status === 'sent' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $report->status === 'archived' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            ">{{ ucfirst($report->status) }}</span>
                            <span class="text-sm text-gray-500">{{ $report->commit_count }} commits</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No reports yet.</p>
                @endforelse
            </div>

            <!-- Recent Invoices -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Invoices</h3>
                @forelse($client->invoices as $invoice)
                    <div class="flex justify-between items-center py-3 border-b last:border-b-0">
                        <div>
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $invoice->invoice_number }}</a>
                            <span class="text-sm text-gray-500 ml-2">{{ $invoice->issue_date->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $invoice->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $invoice->status === 'cancelled' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            ">{{ ucfirst($invoice->status) }}</span>
                            <span class="text-sm font-medium">${{ number_format($invoice->total, 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No invoices yet.</p>
                @endforelse
            </div>

            <!-- Delete Client -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ confirmDelete: false }">
                <h3 class="text-lg font-semibold text-red-700 mb-2">Danger Zone</h3>
                <p class="text-sm text-gray-600 mb-4">Deleting this client will permanently remove all associated data.</p>

                <button @click="confirmDelete = true" x-show="!confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-red-700 transition">Delete Client</button>

                <div x-show="confirmDelete" x-cloak class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm font-semibold text-red-800 mb-2">This will permanently delete:</p>
                    <ul class="text-sm text-red-700 list-disc list-inside mb-3 space-y-0.5">
                        <li>{{ $client->contacts->count() }} contact(s)</li>
                        <li>{{ $client->invoices->count() }} invoice(s) with all line items, payments & history</li>
                        <li>{{ $client->reports()->count() }} report(s) with all summaries & history</li>
                        <li>{{ $client->repositories->count() }} repository link(s)</li>
                        <li>{{ $client->servers->count() }} SSH server(s)</li>
                        <li>{{ $client->monitoredEndpoints->count() }} monitored endpoint(s)</li>
                        <li>{{ $client->pricingPresets->count() }} pricing preset(s)</li>
                    </ul>
                    <p class="text-sm text-red-800 mb-3">Type <strong>{{ $client->company_name }}</strong> to confirm:</p>
                    <form method="POST" action="{{ route('clients.destroy', $client) }}" x-data="{ typed: '' }">
                        @csrf
                        @method('DELETE')
                        <input type="text" x-model="typed" placeholder="Type client name to confirm" class="block w-full rounded-md border-red-300 shadow-sm text-sm focus:border-red-500 focus:ring-red-500 mb-3">
                        <div class="flex gap-2">
                            <button type="submit" :disabled="typed !== '{{ addslashes($client->company_name) }}'" class="px-4 py-2 bg-red-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-red-700 transition disabled:opacity-40 disabled:cursor-not-allowed">Permanently Delete</button>
                            <button type="button" @click="confirmDelete = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-xs font-semibold uppercase hover:bg-gray-300 transition">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
