<x-app-layout voice-page="invoices.index">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Invoices']]" />
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-white leading-tight">Invoices</h2>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                New Invoice
            </a>
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

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @php
                    $cards = [
                        ['Total Invoiced', '$'.number_format($summary->total_amount ?? 0, 2), ($summary->total_count ?? 0).' invoice'.(($summary->total_count ?? 0) != 1 ? 's' : ''), 'var(--text)'],
                        ['Paid', '$'.number_format($summary->total_paid ?? 0, 2), ($summary->paid_count ?? 0).' paid', 'var(--good)'],
                        ['Outstanding', '$'.number_format(max(0, $summary->total_outstanding ?? 0), 2), (($summary->draft_count ?? 0) + ($summary->sent_count ?? 0)).' unpaid', 'var(--warn)'],
                        ['Overdue', '$'.number_format($summary->overdue_amount ?? 0, 2), ($summary->overdue_count ?? 0).' overdue', 'var(--danger)'],
                    ];
                @endphp
                @foreach($cards as $c)
                    <div class="card p-4">
                        <div class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $c[0] }}</div>
                        <div class="mt-1 text-lg sm:text-2xl font-bold tabular-nums whitespace-nowrap" style="color: {{ $c[3] }};">{{ $c[1] }}</div>
                        <div class="mt-1 text-xs text-faint">{{ $c[2] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="p-6">
                    {{-- Filters --}}
                    <form method="GET" data-voice-form="filters" class="flex flex-wrap gap-3 mb-6">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoices..." class="field flex-1 min-w-[200px]">
                        <select name="status" class="field" style="width:auto;">
                            <option value="">All Statuses</option>
                            @foreach(['draft', 'sent', 'paid', 'overdue', 'cancelled'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <select name="client_id" class="field" style="width:auto;">
                            <option value="">All Clients</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        <select name="source" class="field" style="width:auto;">
                            <option value="">All Sources</option>
                            <option value="recurring" {{ request('source') === 'recurring' ? 'selected' : '' }}>Recurring</option>
                            <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                        @if(request()->hasAny(['search', 'status', 'client_id', 'source']))
                            <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Clear</a>
                        @endif
                    </form>

                    {{-- Actions bar --}}
                    @if($invoices->total() > 0)
                        <div class="flex items-center justify-between mb-4 pb-4 border-b" style="border-color: var(--border);">
                            <div class="text-sm text-muted">
                                Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                            </div>
                            <a href="{{ route('invoices.download-all', request()->query()) }}"
                               data-voice-action="download-all"
                               class="btn btn-secondary"
                               onclick="this.style.pointerEvents='none'; this.style.opacity=0.6;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download All PDFs
                            </a>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                    <table class="table-cards min-w-full">
                        <thead>
                            <tr class="border-b" style="border-color: var(--border);">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Invoice #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Due</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-muted uppercase">Total</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-muted uppercase">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-hair">
                            @php $map = ['paid' => 'badge-good', 'sent' => 'badge-info', 'draft' => 'badge-gray', 'overdue' => 'badge-danger', 'cancelled' => 'badge-warn']; @endphp
                            @forelse($invoices as $inv)
                                <tr class="row-item" data-voice-invoice="{{ $inv->invoice_number }}" data-voice-url="{{ route('invoices.show', $inv) }}">
                                    <td data-label="Invoice #" class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('invoices.show', $inv) }}" class="accent-ink hover:underline font-medium">{{ $inv->invoice_number }}</a>
                                        @if($inv->billing_plan_id)
                                            <span class="badge badge-accent ml-1" style="font-size:.6rem;" title="Generated from a billing plan">Recurring</span>
                                        @endif
                                        @if($inv->title)
                                            <div class="text-xs text-faint">{{ $inv->title }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Client" class="px-4 py-3 text-sm text-muted">{{ $inv->client->company_name }}</td>
                                    <td data-label="Date" class="px-4 py-3 whitespace-nowrap text-sm text-muted">{{ $inv->issue_date->format('M d, Y') }}</td>
                                    <td data-label="Due" class="px-4 py-3 whitespace-nowrap text-sm text-muted">{{ $inv->due_date->format('M d, Y') }}</td>
                                    <td data-label="Status" class="px-4 py-3">
                                        <span class="badge {{ $map[$inv->status] ?? 'badge-gray' }}">{{ ucfirst($inv->status) }}</span>
                                    </td>
                                    <td data-label="Total" class="px-4 py-3 text-sm text-right text-slate-200">${{ number_format($inv->total, 2) }}</td>
                                    <td data-label="Balance" class="px-4 py-3 text-sm text-right font-medium" style="color: {{ $inv->balance_due > 0 ? 'var(--danger)' : 'var(--good)' }};">${{ number_format($inv->balance_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-muted">No invoices found.</td></tr>
                            @endforelse
                        </tbody>
                        @if($invoices->count() > 0)
                            <tfoot>
                                <tr class="font-semibold border-t" style="border-color: var(--border-strong);">
                                    <td colspan="5" class="px-4 py-3 text-sm text-muted uppercase tracking-wider">Page Totals</td>
                                    <td data-label="Total" class="px-4 py-3 text-sm text-right text-white">${{ number_format($invoices->sum('total'), 2) }}</td>
                                    <td data-label="Balance" class="px-4 py-3 text-sm text-right text-white">${{ number_format($invoices->sum('balance_due'), 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                    </div>
                    <div class="mt-4">{{ $invoices->links() }}</div>
                </div>
            </div>
        </div>
    </div>
    @php
        $voiceContext = [
            'invoices' => $invoices->getCollection()->map(fn ($inv) => [
                'number' => $inv->invoice_number,
                'title' => $inv->title,
                'client' => $inv->client->company_name,
                'client_id' => $inv->client_id,
                'status' => $inv->status,
                'total' => (float) $inv->total,
                'balance_due' => (float) $inv->balance_due,
                'issue_date' => $inv->issue_date->format('Y-m-d'),
                'due_date' => $inv->due_date->format('Y-m-d'),
            ])->values(),
            'pagination' => [
                'from' => $invoices->firstItem() ?? 0,
                'to' => $invoices->lastItem() ?? 0,
                'total' => $invoices->total(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
            ],
            'urls' => ['create' => route('invoices.create')],
        ];
    @endphp
    <script type="application/json" id="voice-context">@json($voiceContext)</script>
</x-app-layout>
