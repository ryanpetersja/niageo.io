<x-app-layout voice-page="invoices.form">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Invoices', 'url' => route('invoices.index')], ['label' => $invoice->invoice_number, 'url' => route('invoices.show', $invoice)], ['label' => 'Edit']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Edit Invoice: {{ $invoice->invoice_number }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif

            <!-- Apply Preset -->
            @if($invoice->client->pricingPresets->count() > 0)
                <div class="card p-4 mb-6">
                    <form method="POST" action="{{ route('invoices.apply-preset', $invoice) }}" data-voice-form="preset" class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                        @csrf
                        <span class="text-sm font-medium text-muted">Apply Pricing Preset:</span>
                        <select name="pricing_preset_id" class="field text-base sm:text-sm sm:w-auto">
                            @foreach($invoice->client->pricingPresets as $preset)
                                <option value="{{ $preset->id }}">{{ $preset->name }} (${{ number_format($preset->total, 2) }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
                    </form>
                </div>
            @endif

            <div class="card p-4 sm:p-6" x-data="invoiceForm()">
                <form method="POST" action="{{ route('invoices.update', $invoice) }}" data-voice-form="invoice">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" required class="field mt-1">
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id', $invoice->client_id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="title" value="Title (optional)" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $invoice->title)" placeholder="e.g. Maintenance Services, March" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
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
                                        <input type="number" inputmode="decimal" :id="'line_quantity_'+index" x-model="item.quantity" :name="'line_items['+index+'][quantity]'" step="0.01" min="0.01" class="field text-base sm:text-sm" required>
                                    </div>
                                    <div class="sm:w-32 sm:shrink-0">
                                        <label class="block text-xs text-muted mb-1 sm:sr-only" :for="'line_price_'+index">Unit Price</label>
                                        <input type="number" inputmode="decimal" :id="'line_price_'+index" x-model="item.unit_price" :name="'line_items['+index+'][unit_price]'" step="0.01" min="0" class="field text-base sm:text-sm" required>
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
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-6 mb-4">
                        <button type="button" @click="addItem()" class="w-full sm:w-auto rounded-lg border border-dashed sm:border-0 py-3 sm:py-0 text-sm accent-ink hover:underline" style="border-color: var(--border);">+ Add Line Item</button>
                        <button type="button" @click="$dispatch('github-import:toggle')" class="w-full sm:w-auto rounded-lg border border-dashed sm:border-0 py-3 sm:py-0 text-sm accent-ink hover:underline inline-flex items-center justify-center gap-1.5" style="border-color: var(--border);">
                            <svg class="w-4 h-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                            Import from GitHub
                        </button>
                    </div>
                    @include('invoices._github-import')

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
                            <textarea id="notes" name="notes" rows="3" class="field mt-1">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="internal_notes" value="Internal Notes" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="field mt-1">{{ old('internal_notes', $invoice->internal_notes) }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 [&>*]:justify-center">
                        <a href="{{ route('invoices.show', $invoice) }}" data-voice-action="cancel" class="btn btn-secondary">Cancel</a>
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
    @php
        $voiceContext = [
            'mode' => 'edit',
            'invoice_number' => $invoice->invoice_number,
            'presets' => $invoice->client->pricingPresets->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'total' => (float) $p->total,
            ])->values(),
        ];
    @endphp
    <script type="application/json" id="voice-context">@json($voiceContext)</script>
</x-app-layout>
