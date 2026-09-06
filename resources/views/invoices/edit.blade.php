<x-app-layout voice-page="invoices.form">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Invoices', 'url' => route('invoices.index')], ['label' => $invoice->invoice_number, 'url' => route('invoices.show', $invoice)], ['label' => 'Edit']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Edit Invoice: {{ $invoice->invoice_number }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif

            <!-- Apply Preset -->
            @if($invoice->client->pricingPresets->count() > 0)
                <div class="card p-4 mb-6">
                    <form method="POST" action="{{ route('invoices.apply-preset', $invoice) }}" data-voice-form="preset" class="flex items-center gap-4">
                        @csrf
                        <span class="text-sm font-medium text-muted">Apply Pricing Preset:</span>
                        <select name="pricing_preset_id" class="field text-sm" style="width:auto;">
                            @foreach($invoice->client->pricingPresets as $preset)
                                <option value="{{ $preset->id }}">{{ $preset->name }} (${{ number_format($preset->total, 2) }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
                    </form>
                </div>
            @endif

            <div class="card p-6" x-data="invoiceForm()">
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
                            <div class="flex gap-3 items-center" data-voice-line>
                                <div class="flex-1">
                                    <input type="text" x-model="item.description" :name="'line_items['+index+'][description]'" placeholder="Description" class="field text-sm" required>
                                </div>
                                <div class="w-24">
                                    <input type="number" x-model="item.quantity" :name="'line_items['+index+'][quantity]'" step="0.01" min="0.01" class="field text-sm" required>
                                </div>
                                <div class="w-32">
                                    <input type="number" x-model="item.unit_price" :name="'line_items['+index+'][unit_price]'" step="0.01" min="0" class="field text-sm" required>
                                </div>
                                <div class="w-28 text-right text-sm font-medium text-slate-200" x-text="'$' + (item.quantity * item.unit_price).toFixed(2)"></div>
                                <button type="button" @click="removeItem(index)" class="text-slate-500 hover:text-rose-400 text-lg leading-none" x-show="lineItems.length > 1">&times;</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addItem()" class="text-sm accent-ink hover:underline mb-6">+ Add Line Item</button>

                    <div class="border-t pt-4 mb-6" style="border-color: var(--border);">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-1 text-sm">
                                <div class="flex justify-between"><span class="text-muted">Subtotal:</span><span class="text-slate-200" x-text="'$' + subtotal.toFixed(2)"></span></div>
                                <div class="flex justify-between"><span class="text-muted">Tax:</span><span class="text-slate-200" x-text="'$' + taxAmount.toFixed(2)"></span></div>
                                <div class="flex justify-between font-bold text-base border-t pt-1 text-white" style="border-color: var(--border);"><span>Total:</span><span x-text="'$' + total.toFixed(2)"></span></div>
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

                    <div class="flex justify-end gap-3">
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
