{{-- Shared billing plan form. Expects $billingPlan, $clients, $products, $presetsByClient and $mode ('create'|'edit'). --}}
@php
    $itemsForJs = old('items')
        ? collect(old('items'))->values()->map(fn ($i) => [
            'product_id' => $i['product_id'] ?? '',
            'description' => $i['description'] ?? '',
            'quantity' => (float) ($i['quantity'] ?? 1),
            'unit_price' => (float) ($i['unit_price'] ?? 0),
        ])
        : $billingPlan->items->map(fn ($i) => [
            'product_id' => $i->product_id ?? '',
            'description' => $i->description,
            'quantity' => (float) $i->quantity,
            'unit_price' => (float) $i->unit_price,
        ])->values();
    if ($itemsForJs->isEmpty()) {
        $itemsForJs = collect([['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]]);
    }
    $productsForJs = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'description' => $p->description, 'default_unit_price' => (float) $p->default_unit_price, 'unit' => $p->unit])->values();
@endphp

<div class="card p-6" x-data="billingPlanForm()">
    <form method="POST" action="{{ $mode === 'edit' ? route('billing-plans.update', $billingPlan) : route('billing-plans.store') }}" data-voice-form="billing-plan">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <x-input-label for="client_id" value="Client" />
                <select id="client_id" name="client_id" required class="field mt-1" x-model="clientId">
                    <option value="">Select a client...</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ (string) old('client_id', $billingPlan->client_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="name" value="Plan name" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $billingPlan->name)" placeholder="e.g. Hosting & maintenance package" required />
            </div>
            <div>
                <x-input-label for="billing_cycle" value="Billing cycle" />
                <select id="billing_cycle" name="billing_cycle" class="field mt-1" x-model="cycle">
                    <option value="monthly" {{ old('billing_cycle', $billingPlan->billing_cycle) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ old('billing_cycle', $billingPlan->billing_cycle) === 'quarterly' ? 'selected' : '' }}>Quarterly (every 3 months)</option>
                </select>
            </div>
            @if($mode === 'create')
                <div>
                    <x-input-label for="starts_on" value="First billing period starts on" />
                    <x-text-input id="starts_on" name="starts_on" type="date" class="mt-1 block w-full" :value="old('starts_on', $billingPlan->starts_on?->format('Y-m-d'))" required />
                    <p class="text-xs text-faint mt-1">Every period is measured from this date. An invoice is generated for each period from here on, so pick the first period you still need to bill.</p>
                </div>
            @else
                <div>
                    <x-input-label for="next_period_start" value="Next period to invoice" />
                    <x-text-input id="next_period_start" name="next_period_start" type="date" class="mt-1 block w-full" :value="old('next_period_start', $billingPlan->next_period_start?->format('Y-m-d'))" />
                    <p class="text-xs text-faint mt-1">Periods are anchored to {{ $billingPlan->starts_on->format('j M Y') }}. Move this forward to skip periods, or back to re-bill one. Leave empty to stop generating.</p>
                </div>
            @endif
            <div>
                <x-input-label for="ends_on" value="Ends on (optional)" />
                <x-text-input id="ends_on" name="ends_on" type="date" class="mt-1 block w-full" :value="old('ends_on', $billingPlan->ends_on?->format('Y-m-d'))" />
                <p class="text-xs text-faint mt-1">No invoices are generated for periods starting after this date.</p>
            </div>
            @if($mode === 'edit')
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="field mt-1">
                        @foreach(\App\Models\BillingPlan::STATUSES as $s)
                            <option value="{{ $s }}" {{ old('status', $billingPlan->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <x-input-label for="issue_days_before" value="Issue invoice (days before the period starts)" />
                <x-text-input id="issue_days_before" name="issue_days_before" type="number" min="0" max="120" class="mt-1 block w-full" :value="old('issue_days_before', $billingPlan->issue_days_before ?? 0)" required />
                <p class="text-xs text-faint mt-1">0 issues the invoice on the first day of the period.</p>
            </div>
            <div>
                <x-input-label for="due_days" value="Due (days after issue)" />
                <x-text-input id="due_days" name="due_days" type="number" min="0" max="365" class="mt-1 block w-full" :value="old('due_days', $billingPlan->due_days ?? 30)" required />
                <p class="text-xs text-faint mt-1">30 means the invoice is due 30 days into the period.</p>
            </div>
            <div>
                <x-input-label for="tax_rate" value="Tax rate (%)" />
                <x-text-input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('tax_rate', $billingPlan->tax_rate ?? 0)" x-model="taxRate" />
            </div>
            <div>
                <x-input-label for="title_template" value="Invoice title template" />
                <x-text-input id="title_template" name="title_template" type="text" class="mt-1 block w-full" :value="old('title_template', $billingPlan->title_template)" placeholder="{{ \App\Models\BillingPlan::DEFAULT_TITLE_TEMPLATE }}" />
                <p class="text-xs text-faint mt-1">Placeholders: <code>{plan}</code>, <code>{client}</code>, <code>{period}</code> (e.g. September 2026), <code>{month}</code>, <code>{year}</code>.</p>
            </div>
        </div>

        <div class="flex justify-between items-center flex-wrap gap-3 mb-4">
            <h3 class="text-base font-semibold text-white">Package line items <span class="text-xs font-normal text-faint">billed every period</span></h3>
            <div class="flex items-center gap-2" x-show="clientPresets.length > 0" x-cloak>
                <span class="text-xs text-muted">Copy items from preset:</span>
                <select class="field text-sm" style="width:auto;" x-model="presetId">
                    <option value="">Choose…</option>
                    <template x-for="preset in clientPresets" :key="preset.id">
                        <option :value="preset.id" x-text="preset.name"></option>
                    </template>
                </select>
                <button type="button" class="btn btn-secondary btn-sm" @click="loadPreset()" :disabled="!presetId">Load</button>
            </div>
        </div>

        <div class="space-y-3 mb-4">
            <div class="hidden md:grid md:grid-cols-12 gap-3 text-xs font-semibold text-muted uppercase px-1">
                <div class="md:col-span-3">Product</div>
                <div class="md:col-span-4">Description on invoice</div>
                <div class="md:col-span-1 text-right">Qty</div>
                <div class="md:col-span-2 text-right">Unit price</div>
                <div class="md:col-span-2 text-right">Total</div>
            </div>
            <template x-for="(item, index) in items" :key="index">
                <div class="grid grid-cols-2 md:grid-cols-12 gap-3 items-center rounded-lg border p-3 md:p-0 md:border-0 md:rounded-none" style="border-color: var(--border);" data-voice-line>
                    <div class="col-span-2 md:col-span-3">
                        <select x-model="item.product_id" :name="'items['+index+'][product_id]'" class="field text-base md:text-sm" @change="productChanged(index)">
                            <option value="">Custom item</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-span-2 md:col-span-4">
                        <input type="text" x-model="item.description" :name="'items['+index+'][description]'" placeholder="Description" class="field text-base md:text-sm" required>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs text-muted mb-1 md:hidden">Qty</label>
                        <input type="number" inputmode="decimal" x-model="item.quantity" :name="'items['+index+'][quantity]'" step="0.01" min="0.01" class="field text-base md:text-sm text-right" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-muted mb-1 md:hidden">Unit price</label>
                        <input type="number" inputmode="decimal" x-model="item.unit_price" :name="'items['+index+'][unit_price]'" step="0.01" min="0" class="field text-base md:text-sm text-right" required>
                    </div>
                    <div class="col-span-2 md:col-span-2 flex items-center justify-end gap-3">
                        <span class="text-sm text-muted mr-auto md:hidden">Amount</span>
                        <span class="text-sm font-medium text-slate-200" x-text="'$' + lineTotal(item).toFixed(2)"></span>
                        <button type="button" @click="removeItem(index)" class="text-slate-500 hover:text-rose-400 text-lg leading-none" x-show="items.length > 1" title="Remove line">&times;</button>
                    </div>
                </div>
            </template>
        </div>
        <button type="button" @click="addItem()" class="text-sm accent-ink hover:underline mb-6">+ Add line item</button>

        <div class="border-t pt-4 mb-6" style="border-color: var(--border);">
            <div class="flex justify-end">
                <div class="w-full sm:w-72 space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Subtotal per period:</span><span class="text-slate-200" x-text="'$' + subtotal.toFixed(2)"></span></div>
                    <div class="flex justify-between"><span class="text-muted">Tax:</span><span class="text-slate-200" x-text="'$' + taxAmount.toFixed(2)"></span></div>
                    <div class="flex justify-between font-bold text-base border-t pt-1 text-white" style="border-color: var(--border);"><span>Total per period:</span><span x-text="'$' + total.toFixed(2)"></span></div>
                    <div class="flex justify-between text-xs text-faint pt-1"><span>Per month equivalent:</span><span x-text="'$' + (subtotal / (cycle === 'quarterly' ? 3 : 1)).toFixed(2)"></span></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <x-input-label for="notes" value="Invoice notes (visible to client)" />
                <textarea id="notes" name="notes" rows="3" class="field mt-1" placeholder="Added under the billing period line on every invoice">{{ old('notes', $billingPlan->notes) }}</textarea>
            </div>
            <div>
                <x-input-label for="internal_notes" value="Internal notes" />
                <textarea id="internal_notes" name="internal_notes" rows="3" class="field mt-1">{{ old('internal_notes', $billingPlan->internal_notes) }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ $mode === 'edit' ? route('billing-plans.show', $billingPlan) : route('billing-plans.index') }}" data-voice-action="cancel" class="btn btn-secondary">Cancel</a>
            <x-primary-button>{{ $mode === 'edit' ? 'Save Plan' : 'Create Plan' }}</x-primary-button>
        </div>
    </form>
</div>

<script>
    function billingPlanForm() {
        return {
            items: @json($itemsForJs),
            products: @json($productsForJs),
            presetsByClient: @json($presetsByClient),
            clientId: @json((string) old('client_id', $billingPlan->client_id ?? '')),
            cycle: @json(old('billing_cycle', $billingPlan->billing_cycle ?? 'monthly')),
            taxRate: @json((string) old('tax_rate', $billingPlan->tax_rate ?? 0)),
            presetId: '',
            get clientPresets() { return this.presetsByClient[this.clientId] || []; },
            get subtotal() { return this.items.reduce((sum, i) => sum + this.lineTotal(i), 0); },
            get taxAmount() { return this.subtotal * ((parseFloat(this.taxRate) || 0) / 100); },
            get total() { return this.subtotal + this.taxAmount; },
            lineTotal(item) { return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0); },
            addItem() { this.items.push({ product_id: '', description: '', quantity: 1, unit_price: 0 }); },
            removeItem(index) { this.items.splice(index, 1); },
            productChanged(index) {
                const item = this.items[index];
                const product = this.products.find(p => String(p.id) === String(item.product_id));
                if (!product) return;
                if (!item.description) item.description = product.description || product.name;
                if (!(parseFloat(item.unit_price) > 0)) item.unit_price = product.default_unit_price || 0;
            },
            loadPreset() {
                const preset = this.clientPresets.find(p => String(p.id) === String(this.presetId));
                if (!preset) return;
                const hasContent = this.items.some(i => i.description || parseFloat(i.unit_price) > 0);
                if (hasContent && !confirm('Replace the current line items with the preset items?')) return;
                this.items = preset.items.map(i => ({ product_id: '', description: i.description, quantity: i.quantity, unit_price: i.unit_price }));
            },
        }
    }
</script>
