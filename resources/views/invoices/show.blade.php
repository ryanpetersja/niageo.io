<x-app-layout>
    @php
        $statusBadge = ['paid' => 'badge-good', 'sent' => 'badge-info', 'draft' => 'badge-gray', 'overdue' => 'badge-danger', 'cancelled' => 'badge-warn'];
        $transitionStyle = [
            'draft' => 'background: var(--surface-3); color: var(--text-muted);',
            'sent' => 'background: var(--info-soft); color: var(--info);',
            'paid' => 'background: var(--good-soft); color: var(--good);',
            'overdue' => 'background: var(--danger-soft); color: var(--danger);',
            'cancelled' => 'background: var(--warn-soft); color: var(--warn);',
        ];
    @endphp
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Invoices', 'url' => route('invoices.index')], ['label' => $invoice->invoice_number]]" />
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-white leading-tight">Invoice {{ $invoice->invoice_number }}</h2>
                @if($invoice->title)
                    <p class="text-sm text-muted mt-0.5">{{ $invoice->title }}</p>
                @endif
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-secondary">Preview PDF</a>
                <a href="{{ route('invoices.pdf.download', $invoice) }}" class="btn" style="background: var(--good-soft); color: var(--good);">Download PDF</a>
                @if($invoice->status === 'draft')
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary">Edit</a>
                @endif
                <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Duplicate</button>
                </form>
                @if(in_array($invoice->status, ['draft', 'cancelled']))
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this invoice? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Invoice Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Header Info -->
                    <div class="card p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ $invoice->client->company_name }}</h3>
                                <div class="text-sm text-muted mt-1">Created by {{ $invoice->creator->name }} on {{ $invoice->created_at->format('M d, Y') }}</div>
                            </div>
                            <span class="badge {{ $statusBadge[$invoice->status] ?? 'badge-gray' }}" style="font-size:.75rem; padding:.3rem .7rem;">{{ ucfirst($invoice->status) }}</span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div><span class="text-muted">Issue Date</span><div class="font-medium text-slate-100">{{ $invoice->issue_date->format('M d, Y') }}</div></div>
                            <div><span class="text-muted">Due Date</span><div class="font-medium text-slate-100">{{ $invoice->due_date->format('M d, Y') }}</div></div>
                            <div><span class="text-muted">Total</span><div class="font-medium text-slate-100">${{ number_format($invoice->total, 2) }}</div></div>
                            <div><span class="text-muted">Balance Due</span><div class="font-bold" style="color: {{ $invoice->balance_due > 0 ? 'var(--danger)' : 'var(--good)' }};">${{ number_format($invoice->balance_due, 2) }}</div></div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Line Items</h3>
                        <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b" style="border-color: var(--border);">
                                    <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Description</th>
                                    <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Qty</th>
                                    <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Unit Price</th>
                                    <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->lineItems as $item)
                                    <tr class="border-b" style="border-color: var(--border);">
                                        <td class="py-3 text-sm text-slate-200">{{ $item->description }}</td>
                                        <td class="py-3 text-sm text-right text-muted">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="py-3 text-sm text-right text-muted">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="py-3 text-sm text-right font-medium text-slate-100">${{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><td colspan="3" class="py-2 text-right text-sm text-muted">Subtotal</td><td class="py-2 text-right text-sm text-slate-200">${{ number_format($invoice->subtotal, 2) }}</td></tr>
                                @if($invoice->tax_rate > 0)
                                    <tr><td colspan="3" class="py-2 text-right text-sm text-muted">Tax ({{ $invoice->tax_rate }}%)</td><td class="py-2 text-right text-sm text-slate-200">${{ number_format($invoice->tax_amount, 2) }}</td></tr>
                                @endif
                                <tr class="border-t" style="border-color: var(--border-strong);"><td colspan="3" class="py-2 text-right font-bold text-white">Total</td><td class="py-2 text-right font-bold text-white">${{ number_format($invoice->total, 2) }}</td></tr>
                                <tr><td colspan="3" class="py-2 text-right text-sm text-muted">Amount Paid</td><td class="py-2 text-right text-sm" style="color: var(--good);">${{ number_format($invoice->amount_paid, 2) }}</td></tr>
                                <tr class="border-t" style="border-color: var(--border-strong);"><td colspan="3" class="py-2 text-right font-bold text-white">Balance Due</td><td class="py-2 text-right font-bold" style="color: {{ $invoice->balance_due > 0 ? 'var(--danger)' : 'var(--good)' }};">${{ number_format($invoice->balance_due, 2) }}</td></tr>
                            </tfoot>
                        </table>
                        </div>

                        @if($invoice->notes)
                            <div class="mt-4 p-3 rounded text-sm text-slate-200" style="background: var(--surface-2);"><strong class="text-white">Notes:</strong> {{ $invoice->notes }}</div>
                        @endif
                        @if($invoice->internal_notes)
                            <div class="mt-2 p-3 rounded text-sm" style="background: var(--warn-soft); color: var(--warn);"><strong>Internal Notes:</strong> {{ $invoice->internal_notes }}</div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status Transitions -->
                    @if(count($validTransitions) > 0)
                        <div class="card p-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Actions</h3>
                            <div class="space-y-2">
                                @foreach($validTransitions as $transition)
                                    <form method="POST" action="{{ route('invoices.transition', $invoice) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $transition }}">
                                        <button type="submit" class="w-full text-left px-4 py-2 rounded-lg text-sm font-medium transition hover:brightness-125" style="{{ $transitionStyle[$transition] ?? '' }}">Mark as {{ ucfirst($transition) }}</button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Record Payment -->
                    @if(!in_array($invoice->status, ['draft', 'cancelled', 'paid']))
                        <div class="card p-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Record Payment</h3>
                            <form method="POST" action="{{ route('payments.store', $invoice) }}" class="space-y-3">
                                @csrf
                                <div>
                                    <x-input-label for="amount" value="Amount" />
                                    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" :value="$invoice->balance_due" class="mt-1 block w-full" required />
                                </div>
                                <div>
                                    <x-input-label for="payment_date" value="Date" />
                                    <x-text-input id="payment_date" name="payment_date" type="date" :value="now()->format('Y-m-d')" class="mt-1 block w-full" required />
                                </div>
                                <div>
                                    <x-input-label for="payment_method" value="Method" />
                                    <select id="payment_method" name="payment_method" class="field mt-1 text-sm">
                                        <option value="">Select...</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="check">Check</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="cash">Cash</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="reference" value="Reference" />
                                    <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" placeholder="Check #, transaction ID..." />
                                </div>
                                <x-primary-button class="w-full justify-center">Record Payment</x-primary-button>
                            </form>
                        </div>
                    @endif

                    <!-- Payment History -->
                    @if($invoice->payments->count() > 0)
                        <div class="card p-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Payments</h3>
                            <div class="divide-hair">
                                @foreach($invoice->payments as $payment)
                                    <div class="py-2">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <div class="font-medium text-sm" style="color: var(--good);">${{ number_format($payment->amount, 2) }}</div>
                                                <div class="text-xs text-muted">{{ $payment->payment_date->format('M d, Y') }} {{ $payment->payment_method ? '- ' . str_replace('_', ' ', ucfirst($payment->payment_method)) : '' }}</div>
                                                @if($payment->reference)<div class="text-xs text-faint">Ref: {{ $payment->reference }}</div>@endif
                                            </div>
                                            @if($invoice->status !== 'cancelled')
                                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Delete this payment?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-slate-500 hover:text-rose-400">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Status History -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">History</h3>
                        <div class="divide-hair">
                            @foreach($invoice->statusHistory as $history)
                                <div class="py-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-200">{{ $history->from_status ? ucfirst($history->from_status) . ' → ' : '' }}{{ ucfirst($history->to_status) }}</span>
                                        <span class="text-xs text-faint">{{ $history->created_at->format('M d, H:i') }}</span>
                                    </div>
                                    @if($history->changedBy)<div class="text-xs text-muted">by {{ $history->changedBy->name }}</div>@endif
                                    @if($history->notes)<div class="text-xs text-muted mt-0.5">{{ $history->notes }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
