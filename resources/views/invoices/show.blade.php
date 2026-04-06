<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoice {{ $invoice->invoice_number }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-gray-700 transition">Preview PDF</a>
                <a href="{{ route('invoices.pdf.download', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase hover:bg-green-700 transition">Download PDF</a>
                @if($invoice->status === 'draft')
                    <a href="{{ route('invoices.edit', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase text-gray-700 hover:bg-gray-50 transition">Edit</a>
                @endif
                <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold uppercase text-gray-700 hover:bg-gray-50 transition">Duplicate</button>
                </form>
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
                <!-- Main Invoice Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Header Info -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-lg font-semibold">{{ $invoice->client->company_name }}</h3>
                                <div class="text-sm text-gray-500 mt-1">Created by {{ $invoice->creator->name }} on {{ $invoice->created_at->format('M d, Y') }}</div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $invoice->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $invoice->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $invoice->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $invoice->status === 'cancelled' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            ">{{ ucfirst($invoice->status) }}</span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div><span class="text-gray-500">Issue Date</span><div class="font-medium">{{ $invoice->issue_date->format('M d, Y') }}</div></div>
                            <div><span class="text-gray-500">Due Date</span><div class="font-medium">{{ $invoice->due_date->format('M d, Y') }}</div></div>
                            <div><span class="text-gray-500">Total</span><div class="font-medium">${{ number_format($invoice->total, 2) }}</div></div>
                            <div><span class="text-gray-500">Balance Due</span><div class="font-bold {{ $invoice->balance_due > 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($invoice->balance_due, 2) }}</div></div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Line Items</h3>
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                    <th class="py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->lineItems as $item)
                                    <tr class="border-b">
                                        <td class="py-3 text-sm">{{ $item->description }}</td>
                                        <td class="py-3 text-sm text-right">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="py-3 text-sm text-right">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="py-3 text-sm text-right font-medium">${{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><td colspan="3" class="py-2 text-right text-sm text-gray-500">Subtotal</td><td class="py-2 text-right text-sm">${{ number_format($invoice->subtotal, 2) }}</td></tr>
                                @if($invoice->tax_rate > 0)
                                    <tr><td colspan="3" class="py-2 text-right text-sm text-gray-500">Tax ({{ $invoice->tax_rate }}%)</td><td class="py-2 text-right text-sm">${{ number_format($invoice->tax_amount, 2) }}</td></tr>
                                @endif
                                <tr class="border-t"><td colspan="3" class="py-2 text-right font-bold">Total</td><td class="py-2 text-right font-bold">${{ number_format($invoice->total, 2) }}</td></tr>
                                <tr><td colspan="3" class="py-2 text-right text-sm text-gray-500">Amount Paid</td><td class="py-2 text-right text-sm text-green-600">${{ number_format($invoice->amount_paid, 2) }}</td></tr>
                                <tr class="border-t"><td colspan="3" class="py-2 text-right font-bold">Balance Due</td><td class="py-2 text-right font-bold {{ $invoice->balance_due > 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($invoice->balance_due, 2) }}</td></tr>
                            </tfoot>
                        </table>

                        @if($invoice->notes)
                            <div class="mt-4 p-3 bg-gray-50 rounded text-sm"><strong>Notes:</strong> {{ $invoice->notes }}</div>
                        @endif
                        @if($invoice->internal_notes)
                            <div class="mt-2 p-3 bg-yellow-50 rounded text-sm"><strong>Internal Notes:</strong> {{ $invoice->internal_notes }}</div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status Transitions -->
                    @if(count($validTransitions) > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
                            <div class="space-y-2">
                                @foreach($validTransitions as $transition)
                                    <form method="POST" action="{{ route('invoices.transition', $invoice) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $transition }}">
                                        <button type="submit" class="w-full text-left px-4 py-2 rounded-md text-sm font-medium transition
                                            {{ $transition === 'draft' ? 'bg-gray-100 text-gray-800 hover:bg-gray-200' : '' }}
                                            {{ $transition === 'sent' ? 'bg-blue-100 text-blue-800 hover:bg-blue-200' : '' }}
                                            {{ $transition === 'paid' ? 'bg-green-100 text-green-800 hover:bg-green-200' : '' }}
                                            {{ $transition === 'overdue' ? 'bg-red-100 text-red-800 hover:bg-red-200' : '' }}
                                            {{ $transition === 'cancelled' ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : '' }}
                                        ">Mark as {{ ucfirst($transition) }}</button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Record Payment -->
                    @if(!in_array($invoice->status, ['draft', 'cancelled', 'paid']))
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Record Payment</h3>
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
                                    <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
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
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Payments</h3>
                            @foreach($invoice->payments as $payment)
                                <div class="py-2 border-b last:border-b-0">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-medium text-sm text-green-600">${{ number_format($payment->amount, 2) }}</div>
                                            <div class="text-xs text-gray-500">{{ $payment->payment_date->format('M d, Y') }} {{ $payment->payment_method ? '- ' . str_replace('_', ' ', ucfirst($payment->payment_method)) : '' }}</div>
                                            @if($payment->reference)<div class="text-xs text-gray-400">Ref: {{ $payment->reference }}</div>@endif
                                        </div>
                                        @if($invoice->status !== 'cancelled')
                                            <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Delete this payment?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Status History -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">History</h3>
                        @foreach($invoice->statusHistory as $history)
                            <div class="py-2 border-b last:border-b-0 text-sm">
                                <div class="flex justify-between">
                                    <span>{{ $history->from_status ? ucfirst($history->from_status) . ' → ' : '' }}{{ ucfirst($history->to_status) }}</span>
                                    <span class="text-xs text-gray-400">{{ $history->created_at->format('M d, H:i') }}</span>
                                </div>
                                @if($history->changedBy)<div class="text-xs text-gray-500">by {{ $history->changedBy->name }}</div>@endif
                                @if($history->notes)<div class="text-xs text-gray-500 mt-0.5">{{ $history->notes }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
