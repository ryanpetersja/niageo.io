<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Invoice: {{ $invoice->invoice_number }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <!-- Apply Preset -->
            @if($invoice->client->pricingPresets->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6">
                    <form method="POST" action="{{ route('invoices.apply-preset', $invoice) }}" class="flex items-center gap-4">
                        @csrf
                        <span class="text-sm font-medium text-gray-700">Apply Pricing Preset:</span>
                        <select name="pricing_preset_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                            @foreach($invoice->client->pricingPresets as $preset)
                                <option value="{{ $preset->id }}">{{ $preset->name }} (${{ number_format($preset->total, 2) }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">Apply</button>
                    </form>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="invoiceForm()">
                <form method="POST" action="{{ route('invoices.update', $invoice) }}">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id', $invoice->client_id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div></div>
                        <div>
                            <x-input-label for="issue_date" value="Issue Date" />
                            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', $invoice->issue_date->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Due Date" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', $invoice->due_date->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="tax_rate" value="Tax Rate (%)" />
                            <x-text-input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('tax_rate', $invoice->tax_rate)" />
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Line Items</h3>
                    <div class="space-y-3 mb-4">
                        <template x-for="(item, index) in lineItems" :key="index">
                            <div class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input type="text" x-model="item.description" :name="'line_items['+index+'][description]'" placeholder="Description" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                                </div>
                                <div class="w-24">
                                    <input type="number" x-model="item.quantity" :name="'line_items['+index+'][quantity]'" step="0.01" min="0.01" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                                </div>
                                <div class="w-32">
                                    <input type="number" x-model="item.unit_price" :name="'line_items['+index+'][unit_price]'" step="0.01" min="0" class="block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
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
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="internal_notes" value="Internal Notes" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('internal_notes', $invoice->internal_notes) }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
                        <x-primary-button>Update Invoice</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function invoiceForm() {
            return {
                lineItems: @json($invoice->lineItems->map(fn($i) => ['description' => $i->description, 'quantity' => (float)$i->quantity, 'unit_price' => (float)$i->unit_price])->values()),
                get subtotal() { return this.lineItems.reduce((sum, i) => sum + (i.quantity * i.unit_price), 0); },
                get taxAmount() { return this.subtotal * ((document.getElementById('tax_rate')?.value || 0) / 100); },
                get total() { return this.subtotal + this.taxAmount; },
                addItem() { this.lineItems.push({ description: '', quantity: 1, unit_price: 0 }); },
                removeItem(index) { this.lineItems.splice(index, 1); },
            }
        }
    </script>
</x-app-layout>
