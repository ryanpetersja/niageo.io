<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Scopes', 'url' => route('scopes.index')], ['label' => $scope->scope_number, 'url' => route('scopes.show', $scope)], ['label' => 'Edit']]" />
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Scope &mdash; {{ $scope->scope_number }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8" x-data="scopeEditor()">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <!-- Metadata Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Scope Details</h3>
                <form method="POST" action="{{ route('scopes.update', $scope) }}">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id', $scope->client_id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="title" value="Scope Title" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $scope->title)" required />
                        </div>
                    </div>
                    <div class="mb-6">
                        <x-input-label for="description" value="Project Description" />
                        <textarea id="description" name="description" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('description', $scope->description) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <x-input-label for="currency" value="Currency" />
                            <select id="currency" name="currency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['USD' => 'USD ($)', 'JMD' => 'JMD (J$)', 'CAD' => 'CAD (CA$)', 'GBP' => 'GBP (£)', 'EUR' => 'EUR (€)'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('currency', $scope->currency) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="notes" value="Notes" />
                            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('notes', $scope->notes) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="internal_notes" value="Internal Notes" />
                            <textarea id="internal_notes" name="internal_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('internal_notes', $scope->internal_notes) }}</textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-4">
                        <a href="{{ route('scopes.show', $scope) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
                        <x-primary-button>Update Scope</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Scope Items Editor -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" id="items">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Scope Items</h3>
                    <div class="flex items-center gap-2">
                        <template x-if="itemsDirty">
                            <button @click="saveItems()" :disabled="itemsSaving" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                                <span x-text="itemsSaving ? 'Saving...' : 'Save Items'"></span>
                            </button>
                        </template>
                        <button @click="addItem()" class="px-3 py-1 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700">+ Add Item</button>
                    </div>
                </div>

                <template x-if="itemsMessage">
                    <div class="mb-3 text-xs font-medium" :class="itemsError ? 'text-red-600' : 'text-green-600'" x-text="itemsMessage"></div>
                </template>

                <div class="space-y-4">
                    <template x-for="(item, idx) in items" :key="'item-' + idx">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-medium text-gray-400" x-text="'#' + (idx + 1)"></span>
                                <button @click="removeItem(idx)" class="text-red-400 hover:text-red-600 text-sm">&times; Remove</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                    <input type="text" x-model="item.title" @input="itemsDirty = true"
                                        class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Item title">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                                    <input type="text" x-model="item.category" @input="itemsDirty = true"
                                        class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., Development">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Price</label>
                                    <input type="number" step="0.01" min="0" x-model="item.price" @input="itemsDirty = true"
                                        class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="flex items-end gap-4 md:col-span-3">
                                    <label class="flex items-center gap-1.5 text-xs">
                                        <input type="checkbox" x-model="item.is_mandatory" @change="if(item.is_mandatory){item.is_optional=false}; itemsDirty=true" class="rounded border-gray-300 text-indigo-600">
                                        Mandatory
                                    </label>
                                    <label class="flex items-center gap-1.5 text-xs">
                                        <input type="checkbox" x-model="item.is_optional" @change="if(item.is_optional){item.is_mandatory=false}; itemsDirty=true" class="rounded border-gray-300 text-indigo-600">
                                        Optional
                                    </label>
                                    <label class="flex items-center gap-1.5 text-xs">
                                        <input type="checkbox" x-model="item.is_recommended" @change="itemsDirty=true" class="rounded border-gray-300 text-indigo-600">
                                        Recommended
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                <textarea x-model="item.description" @input="itemsDirty = true" rows="2"
                                    class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="What this scope item covers..."></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Business Value</label>
                                    <textarea x-model="item.business_value_statement" @input="itemsDirty = true" rows="2"
                                        class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Why this matters..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Effort Estimate</label>
                                    <input type="text" x-model="item.effort_description" @input="itemsDirty = true"
                                        class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., 2-3 weeks">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Deliverable</label>
                                    <textarea x-model="item.deliverable_description" @input="itemsDirty = true" rows="2"
                                        class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="What will be delivered..."></textarea>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <p x-show="items.length === 0" class="text-center text-gray-400 py-8 text-sm">No scope items. Click "+ Add Item" or generate items with AI from the scope show page.</p>

                <!-- Totals -->
                <div x-show="items.length > 0" class="mt-6 border-t pt-4">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Mandatory</span><span class="font-medium" x-text="'$' + mandatoryTotal().toFixed(2)"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Optional</span><span class="font-medium" x-text="'$' + optionalTotal().toFixed(2)"></span></div>
                            <div class="flex justify-between font-bold text-base border-t pt-2"><span>Total</span><span class="text-indigo-700" x-text="'$' + grandTotal().toFixed(2)"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function scopeEditor() {
            return {
                items: @json($scope->items->map(fn($i) => $i->only(['id','title','description','category','price','is_mandatory','is_optional','is_recommended','business_value_statement','effort_description','deliverable_description']))),
                itemsDirty: false,
                itemsSaving: false,
                itemsMessage: '',
                itemsError: false,

                addItem() {
                    this.items.push({
                        id: null, title: '', description: '', category: '', price: 0,
                        is_mandatory: false, is_optional: true, is_recommended: false,
                        business_value_statement: '', effort_description: '', deliverable_description: ''
                    });
                    this.itemsDirty = true;
                },

                removeItem(idx) {
                    this.items.splice(idx, 1);
                    this.itemsDirty = true;
                },

                mandatoryTotal() {
                    return this.items.filter(i => i.is_mandatory).reduce((s, i) => s + parseFloat(i.price || 0), 0);
                },
                optionalTotal() {
                    return this.items.filter(i => !i.is_mandatory).reduce((s, i) => s + parseFloat(i.price || 0), 0);
                },
                grandTotal() {
                    return this.items.reduce((s, i) => s + parseFloat(i.price || 0), 0);
                },

                async saveItems() {
                    this.itemsSaving = true;
                    this.itemsMessage = '';
                    try {
                        const resp = await fetch(`/scopes/{{ $scope->id }}/items`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ items: this.items })
                        });
                        if (resp.ok) {
                            this.itemsDirty = false;
                            this.itemsMessage = 'Items saved successfully.';
                            this.itemsError = false;
                            setTimeout(() => this.itemsMessage = '', 3000);
                        } else {
                            const err = await resp.json();
                            this.itemsMessage = err.message || 'Failed to save items.';
                            this.itemsError = true;
                        }
                    } catch (e) {
                        this.itemsMessage = 'Network error.';
                        this.itemsError = true;
                    }
                    this.itemsSaving = false;
                }
            }
        }
    </script>
</x-app-layout>
