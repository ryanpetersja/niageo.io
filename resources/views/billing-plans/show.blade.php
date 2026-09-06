<x-app-layout voice-page="billing-plans.show">
    @php
        $statusBadge = ['active' => 'badge-good', 'paused' => 'badge-warn', 'ended' => 'badge-gray'];
        $invoiceBadge = ['paid' => 'badge-good', 'sent' => 'badge-info', 'draft' => 'badge-gray', 'overdue' => 'badge-danger', 'cancelled' => 'badge-warn'];
    @endphp
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Billing Plans', 'url' => route('billing-plans.index')], ['label' => $billingPlan->name]]" />
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-white leading-tight flex items-center gap-2">
                    {{ $billingPlan->name }}
                    <span class="badge {{ $statusBadge[$billingPlan->status] ?? 'badge-gray' }}" style="font-size:.7rem;">{{ ucfirst($billingPlan->status) }}</span>
                </h2>
                <p class="text-sm text-muted mt-0.5">
                    <a href="{{ route('clients.show', $billingPlan->client) }}" class="accent-ink hover:underline">{{ $billingPlan->client->company_name }}</a>
                    · {{ $billingPlan->cycle_label }} · ${{ number_format($billingPlan->period_total, 2) }} per period
                </p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('billing-plans.edit', $billingPlan) }}" class="btn btn-secondary">Edit</a>
                @if($billingPlan->status === 'active')
                    <form method="POST" action="{{ route('billing-plans.status', $billingPlan) }}" data-voice-action="status" data-status="paused">@csrf<input type="hidden" name="status" value="paused"><button type="submit" class="btn btn-secondary">Pause</button></form>
                @elseif($billingPlan->status === 'paused')
                    <form method="POST" action="{{ route('billing-plans.status', $billingPlan) }}" data-voice-action="status" data-status="active">@csrf<input type="hidden" name="status" value="active"><button type="submit" class="btn" style="background: var(--good-soft); color: var(--good);">Resume</button></form>
                @endif
                @if($billingPlan->status !== 'ended')
                    <form method="POST" action="{{ route('billing-plans.status', $billingPlan) }}" data-voice-action="status" data-status="ended" onsubmit="return confirm('End this plan? No further invoices will be generated.')">@csrf<input type="hidden" name="status" value="ended"><button type="submit" class="btn btn-secondary">End plan</button></form>
                @endif
                <form method="POST" action="{{ route('billing-plans.destroy', $billingPlan) }}" data-voice-action="delete-plan" onsubmit="return confirm('Delete this billing plan? Invoices already generated are kept.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
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
                <div class="lg:col-span-2 space-y-6">
                    {{-- Schedule summary --}}
                    <div class="card p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-muted">Next period</span>
                                <div class="font-medium text-slate-100">{{ $upcoming[0]['label'] ?? '—' }}</div>
                                @if(!empty($upcoming))
                                    <div class="text-xs text-faint">{{ $upcoming[0]['start']->format('M d') }} – {{ $upcoming[0]['end']->format('M d, Y') }}</div>
                                @endif
                            </div>
                            <div>
                                <span class="text-muted">Next invoice</span>
                                <div class="font-medium text-slate-100">{{ $nextIssueDate ? $nextIssueDate->format('M d, Y') : '—' }}</div>
                                @if($isDue)
                                    <div class="text-xs" style="color: var(--warn);">Due to be generated now</div>
                                @elseif($billingPlan->status !== 'active')
                                    <div class="text-xs text-faint">Plan is {{ $billingPlan->status }}</div>
                                @endif
                            </div>
                            <div>
                                <span class="text-muted">Terms</span>
                                <div class="font-medium text-slate-100">Due {{ $billingPlan->due_days }} days after issue</div>
                                <div class="text-xs text-faint">
                                    @if($billingPlan->issue_days_before > 0)
                                        Issued {{ $billingPlan->issue_days_before }} days before the period
                                    @else
                                        Issued on the first day of the period
                                    @endif
                                </div>
                            </div>
                            <div>
                                <span class="text-muted">Runs</span>
                                <div class="font-medium text-slate-100">
                                    {{ $billingPlan->starts_on->format('M d, Y') }} → {{ $billingPlan->ends_on ? $billingPlan->ends_on->format('M d, Y') : 'ongoing' }}
                                </div>
                                <div class="text-xs text-faint">Tax {{ number_format($billingPlan->tax_rate, 2) }}%</div>
                            </div>
                        </div>
                        @if($nextTitle)
                            <div class="mt-4 pt-4 border-t text-sm" style="border-color: var(--border);">
                                <span class="text-muted">Next invoice title:</span>
                                <span class="text-slate-100">{{ $nextTitle }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Line items --}}
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Package</h3>
                        <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b" style="border-color: var(--border);">
                                    <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Description</th>
                                    <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Product</th>
                                    <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Qty</th>
                                    <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Unit Price</th>
                                    <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billingPlan->items as $item)
                                    <tr class="border-b" style="border-color: var(--border);">
                                        <td class="py-3 text-sm text-slate-200">{{ $item->description }}</td>
                                        <td class="py-3 text-sm text-faint">{{ $item->product?->name ?? '—' }}</td>
                                        <td class="py-3 text-sm text-right text-muted">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="py-3 text-sm text-right text-muted">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="py-3 text-sm text-right font-medium text-slate-100">${{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><td colspan="4" class="py-2 text-right text-sm text-muted">Subtotal per period</td><td class="py-2 text-right text-sm text-slate-200">${{ number_format($billingPlan->period_subtotal, 2) }}</td></tr>
                                @if($billingPlan->tax_rate > 0)
                                    <tr><td colspan="4" class="py-2 text-right text-sm text-muted">Tax ({{ number_format($billingPlan->tax_rate, 2) }}%)</td><td class="py-2 text-right text-sm text-slate-200">${{ number_format($billingPlan->period_total - $billingPlan->period_subtotal, 2) }}</td></tr>
                                @endif
                                <tr class="border-t" style="border-color: var(--border-strong);"><td colspan="4" class="py-2 text-right font-bold text-white">Total per period</td><td class="py-2 text-right font-bold text-white">${{ number_format($billingPlan->period_total, 2) }}</td></tr>
                            </tfoot>
                        </table>
                        </div>
                        @if($billingPlan->notes)
                            <div class="mt-4 p-3 rounded text-sm text-slate-200" style="background: var(--surface-2);"><strong class="text-white">Invoice notes:</strong> {{ $billingPlan->notes }}</div>
                        @endif
                        @if($billingPlan->internal_notes)
                            <div class="mt-2 p-3 rounded text-sm" style="background: var(--warn-soft); color: var(--warn);"><strong>Internal notes:</strong> {{ $billingPlan->internal_notes }}</div>
                        @endif
                    </div>

                    {{-- Generated invoices --}}
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Generated Invoices</h3>
                        @if($billingPlan->invoices->isEmpty())
                            <p class="text-sm text-muted">No invoices generated yet.</p>
                        @else
                            <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b" style="border-color: var(--border);">
                                        <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Period</th>
                                        <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Invoice</th>
                                        <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Issued</th>
                                        <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Due</th>
                                        <th class="py-2 text-left text-xs font-semibold text-muted uppercase">Status</th>
                                        <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Total</th>
                                        <th class="py-2 text-right text-xs font-semibold text-muted uppercase">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($billingPlan->invoices as $invoice)
                                        <tr class="border-b" style="border-color: var(--border);" data-voice-invoice="{{ $invoice->invoice_number }}" data-voice-url="{{ route('invoices.show', $invoice) }}">
                                            <td class="py-3 text-sm text-slate-200">{{ $invoiceLabels[$invoice->id] ?? '—' }}</td>
                                            <td class="py-3 text-sm"><a href="{{ route('invoices.show', $invoice) }}" class="accent-ink hover:underline font-medium">{{ $invoice->invoice_number }}</a></td>
                                            <td class="py-3 text-sm text-muted">{{ $invoice->issue_date->format('M d, Y') }}</td>
                                            <td class="py-3 text-sm text-muted">{{ $invoice->due_date->format('M d, Y') }}</td>
                                            <td class="py-3"><span class="badge {{ $invoiceBadge[$invoice->status] ?? 'badge-gray' }}">{{ ucfirst($invoice->status) }}</span></td>
                                            <td class="py-3 text-sm text-right text-slate-200">${{ number_format($invoice->total, 2) }}</td>
                                            <td class="py-3 text-sm text-right font-medium" style="color: {{ $invoice->balance_due > 0 ? 'var(--danger)' : 'var(--good)' }};">${{ number_format($invoice->balance_due, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Upcoming</h3>
                        @if(empty($upcoming))
                            <p class="text-sm text-muted">
                                @if($billingPlan->status === 'ended') This plan has ended. @else No next period is set. Edit the plan to choose the next period to invoice. @endif
                            </p>
                        @else
                            <div class="divide-hair">
                                @foreach($upcoming as $index => $period)
                                    <div class="py-3 text-sm">
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="text-slate-100 font-medium">{{ $period['label'] }}</span>
                                            <span class="text-slate-200">${{ number_format($billingPlan->period_total, 2) }}</span>
                                        </div>
                                        <div class="text-xs text-muted mt-0.5">Issue {{ $period['issue_date']->format('M d, Y') }} · due {{ $period['due_date']->format('M d, Y') }}</div>
                                        @if($index === 0 && $billingPlan->status !== 'ended')
                                            <form method="POST" action="{{ route('billing-plans.generate', $billingPlan) }}" class="mt-2" data-voice-action="generate-next">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm w-full justify-center">{{ $isDue ? 'Generate this invoice now' : 'Generate early' }}</button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($billingPlan->status !== 'ended')
                            <form method="POST" action="{{ route('billing-plans.generate', $billingPlan) }}" class="mt-4 pt-4 border-t" style="border-color: var(--border);" data-voice-form="generate-period">
                                @csrf
                                <label class="field-label" for="period_start">Generate a specific period</label>
                                <div class="flex gap-2">
                                    <input id="period_start" name="period_start" type="date" class="field text-sm" required>
                                    <button type="submit" class="btn btn-secondary btn-sm">Generate</button>
                                </div>
                                <p class="text-xs text-faint mt-1">Enter the period's first day, e.g. a missed month. The schedule is not changed.</p>
                            </form>
                        @endif
                    </div>

                    <div class="card p-6 text-sm">
                        <h3 class="text-lg font-semibold text-white mb-3">Details</h3>
                        <dl class="space-y-2">
                            <div><dt class="text-muted text-xs uppercase tracking-wider">Title template</dt><dd class="text-slate-200">{{ $billingPlan->title_template ?: \App\Models\BillingPlan::DEFAULT_TITLE_TEMPLATE }}</dd></div>
                            <div><dt class="text-muted text-xs uppercase tracking-wider">Created</dt><dd class="text-slate-200">{{ $billingPlan->created_at->format('M d, Y') }}{{ $billingPlan->creator ? ' by ' . $billingPlan->creator->name : '' }}</dd></div>
                            <div><dt class="text-muted text-xs uppercase tracking-wider">Invoices generated</dt><dd class="text-slate-200">{{ $billingPlan->invoices->count() }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
