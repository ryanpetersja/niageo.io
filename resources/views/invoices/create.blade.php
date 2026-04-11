<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Invoices', 'url' => route('invoices.index')], ['label' => 'New Invoice']]" />
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Invoice</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="invoiceForm()">
                <form method="POST" action="{{ route('invoices.store') }}" @submit="prepareSubmit">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a client...</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id', $selectedClient?->id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>
                        <div></div>
                        <div>
                            <x-input-label for="issue_date" value="Issue Date" />
                            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', now()->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Due Date" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', now()->addDays(30)->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="tax_rate" value="Tax Rate (%)" />
                            <x-text-input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('tax_rate', '0')" />
                        </div>
                    </div>

                    <!-- Line Items -->
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Line Items</h3>
                    <div class="space-y-3 mb-4">
                        <template x-for="(item, index) in lineItems" :key="index">
                            <div class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input type="text" x-model="item.description" :name="'line_items['+index+'][description]'" placeholder="Description" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                                </div>
                                <div class="w-24">
                                    <input type="number" x-model="item.quantity" :name="'line_items['+index+'][quantity]'" placeholder="Qty" step="0.01" min="0.01" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                                </div>
                                <div class="w-32">
                                    <input type="number" x-model="item.unit_price" :name="'line_items['+index+'][unit_price]'" placeholder="Unit Price" step="0.01" min="0" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                                </div>
                                <div class="w-28 pt-2 text-right text-sm font-medium" x-text="'$' + (item.quantity * item.unit_price).toFixed(2)"></div>
                                <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 pt-2" x-show="lineItems.length > 1">&times;</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addItem()" class="text-sm text-indigo-600 hover:text-indigo-800 mb-6">+ Add Line Item</button>

                    <div class="border-t pt-4 mb-6">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-1 text-sm">
                                <div class="flex justify-between"><span class="text-gray-500">Subtotal:</span><span x-text="'$' + subtotal.toFixed(2)"></span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Tax:</span><span x-text="'$' + taxAmount.toFixed(2)"></span></div>
                                <div class="flex justify-between font-bold text-base border-t pt-1"><span>Total:</span><span x-text="'$' + total.toFixed(2)"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="notes" value="Notes (visible to client)" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('notes') }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="internal_notes" value="Internal Notes" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('internal_notes') }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
                        <x-primary-button>Create Invoice</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function invoiceForm() {
            return {
                lineItems: [{ description: '', quantity: 1, unit_price: 0 }],
                get subtotal() { return this.lineItems.reduce((sum, i) => sum + (i.quantity * i.unit_price), 0); },
                get taxAmount() { return this.subtotal * ((document.getElementById('tax_rate')?.value || 0) / 100); },
                get total() { return this.subtotal + this.taxAmount; },
                addItem() { this.lineItems.push({ description: '', quantity: 1, unit_price: 0 }); },
                removeItem(index) { this.lineItems.splice(index, 1); },
                prepareSubmit() { return true; }
            }
        }
    </script>
</x-app-layout>
