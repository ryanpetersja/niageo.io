<x-app-layout voice-page="invoices.form">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Invoices', 'url' => route('invoices.index')], ['label' => 'New Invoice']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Create Invoice</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ session('error') }}</div>
            @endif

            <div class="card p-4 sm:p-6" x-data="invoiceForm()">
                <form method="POST" action="{{ route('invoices.store') }}" data-voice-form="invoice" @submit="prepareSubmit">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" required class="field mt-1">
                                <option value="">Select a client...</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id', $selectedClient?->id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="title" value="Title (optional)" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" placeholder="e.g. Maintenance Services, March" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
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
                    <h3 class="text-base font-semibold text-white mb-4">Line Items</h3>
                    <div class="space-y-3 mb-4">
                        <template x-for="(item, index) in lineItems" :key="index">
                            {{-- Mobile: a stacked card per line (full-width description, labelled qty/price). sm+: a single row. --}}
                            <div class="rounded-lg border p-3 sm:p-0 sm:border-0 sm:rounded-none flex flex-col gap-3 sm:flex-row sm:items-center" style="border-color: var(--border);" data-voice-line>
                                <div class="flex items-center justify-between sm:hidden">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-muted" x-text="'Item ' + (index + 1)"></span>
                                    <button type="button" @click="removeItem(index)" class="text-sm text-slate-400 hover:text-rose-400 px-2 py-1 -mr-2" x-show="lineItems.length > 1">Remove</button>
                                </div>
                                <div class="sm:flex-1 min-w-0">
                                    <label class="block text-xs text-muted mb-1 sm:sr-only" :for="'line_description_'+index">Description</label>
                                    <input type="text" :id="'line_description_'+index" x-model="item.description" :name="'line_items['+index+'][description]'" placeholder="Description" class="field text-base sm:text-sm" required>
                                </div>
                                <div class="grid grid-cols-2 gap-3 sm:contents">
                                    <div class="sm:w-24 sm:shrink-0">
                                        <label class="block text-xs text-muted mb-1 sm:sr-only" :for="'line_quantity_'+index">Qty</label>
                                        <input type="number" inputmode="decimal" :id="'line_quantity_'+index" x-model="item.quantity" :name="'line_items['+index+'][quantity]'" placeholder="Qty" step="0.01" min="0.01" class="field text-base sm:text-sm" required>
                                    </div>
                                    <div class="sm:w-32 sm:shrink-0">
                                        <label class="block text-xs text-muted mb-1 sm:sr-only" :for="'line_price_'+index">Unit Price</label>
                                        <input type="number" inputmode="decimal" :id="'line_price_'+index" x-model="item.unit_price" :name="'line_items['+index+'][unit_price]'" placeholder="Unit Price" step="0.01" min="0" class="field text-base sm:text-sm" required>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between sm:justify-end sm:w-32 sm:shrink-0 text-sm font-medium text-slate-200">
                                    <span class="text-muted sm:hidden">Amount</span>
                                    <span class="tabular-nums" x-text="'$' + Number(item.quantity * item.unit_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                                </div>
                                <button type="button" @click="removeItem(index)" class="hidden sm:block text-slate-500 hover:text-rose-400 text-lg leading-none" x-show="lineItems.length > 1" aria-label="Remove line item">&times;</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addItem()" class="w-full sm:w-auto rounded-lg border border-dashed sm:border-0 py-3 sm:py-0 text-sm accent-ink hover:underline mb-6" style="border-color: var(--border);">+ Add Line Item</button>

                    <div class="border-t pt-4 mb-6" style="border-color: var(--border);">
                        <div class="flex justify-end">
                            <div class="w-full sm:w-72 space-y-1 text-sm tabular-nums">
                                <div class="flex justify-between"><span class="text-muted">Subtotal:</span><span class="text-slate-200" x-text="'$' + Number(subtotal || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></div>
                                <div class="flex justify-between"><span class="text-muted">Tax:</span><span class="text-slate-200" x-text="'$' + Number(taxAmount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></div>
                                <div class="flex justify-between font-bold text-base border-t pt-1 text-white" style="border-color: var(--border);"><span>Total:</span><span x-text="'$' + Number(total || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="notes" value="Notes (visible to client)" />
                            <textarea id="notes" name="notes" rows="3" class="field mt-1">{{ old('notes') }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="internal_notes" value="Internal Notes" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="field mt-1">{{ old('internal_notes') }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 [&>*]:justify-center">
                        <a href="{{ route('invoices.index') }}" data-voice-action="cancel" class="btn btn-secondary">Cancel</a>
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
    @php
        $voiceContext = ['mode' => 'create', 'presets' => []];
    @endphp
    <script type="application/json" id="voice-context">@json($voiceContext)</script>
</x-app-layout>
