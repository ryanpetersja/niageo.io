<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-2xl text-white leading-tight">Dashboard</h2>
                <p class="text-sm text-muted mt-0.5">{{ now()->format('l, F j, Y') }}</p>
            </div>
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

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                @php
                    $stats = [
                        ['label' => 'Total Revenue', 'value' => '$'.number_format($metrics['total_revenue'], 2), 'color' => 'var(--good)', 'soft' => 'var(--good-soft)', 'sub' => 'Collected to date', 'icon' => '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
                        ['label' => 'Outstanding', 'value' => '$'.number_format($metrics['total_outstanding'], 2), 'color' => 'var(--warn)', 'soft' => 'var(--warn-soft)', 'sub' => 'Awaiting payment', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'],
                        ['label' => 'Overdue', 'value' => '$'.number_format($metrics['total_overdue'], 2), 'color' => 'var(--danger)', 'soft' => 'var(--danger-soft)', 'sub' => ($metrics['overdue_count'] ?? 0).' invoice(s)', 'icon' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/>'],
                        ['label' => 'Collection Rate', 'value' => $metrics['collection_rate'].'%', 'color' => 'var(--accent-ink)', 'soft' => 'var(--accent-soft)', 'sub' => ($metrics['paid_count'] ?? 0).'/'.($metrics['invoice_count'] ?? 0).' invoices', 'icon' => '<path d="M22 12A10 10 0 1 1 12 2"/><path d="M22 4 12 14.01l-3-3"/>'],
                    ];
                @endphp
                @foreach($stats as $s)
                    <div class="card card-hover p-4 sm:p-5 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $s['label'] }}</span>
                            <span class="hidden sm:inline-flex items-center justify-center w-9 h-9 rounded-lg shrink-0" style="background: {{ $s['soft'] }}; color: {{ $s['color'] }};">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $s['icon'] !!}</svg>
                            </span>
                        </div>
                        <div class="mt-2 sm:mt-3 text-lg sm:text-2xl font-bold tabular-nums whitespace-nowrap" style="color: {{ $s['color'] }};">{{ $s['value'] }}</div>
                        <div class="mt-1 text-xs text-faint">{{ $s['sub'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Chart + Overdue --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-white mb-4">Monthly Revenue <span class="text-faint font-normal">({{ now()->year }})</span></h3>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>

                <div class="card p-6">
                    <h3 class="text-base font-semibold text-white mb-4">Overdue Invoices</h3>
                    <div class="space-y-1">
                        @forelse($overdueInvoices as $inv)
                            <a href="{{ route('invoices.show', $inv) }}" class="row-item flex items-center justify-between p-3 -mx-1">
                                <div>
                                    <span class="font-medium text-slate-100">{{ $inv->invoice_number }}</span>
                                    <span class="text-sm text-muted ml-2">{{ $inv->client->company_name }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold" style="color: var(--danger);">${{ number_format($inv->balance_due, 2) }}</div>
                                    <div class="text-xs text-faint">Due {{ $inv->due_date->diffForHumans() }}</div>
                                </div>
                            </a>
                        @empty
                            <p class="text-muted text-sm py-6 text-center">No overdue invoices. 🎉</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Uptime --}}
            <div class="card p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-white">Uptime Monitoring</h3>
                    <a href="{{ route('uptime.index') }}" class="text-sm accent-ink hover:underline">View all →</a>
                </div>
                <div class="flex items-center gap-6 mb-4 flex-wrap">
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full" style="background: var(--good);"></span><span class="text-sm text-muted"><span class="font-semibold" style="color: var(--good);">{{ $uptimeSummary['up'] }}</span> Up</span></div>
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full" style="background: var(--warn);"></span><span class="text-sm text-muted"><span class="font-semibold" style="color: var(--warn);">{{ $uptimeSummary['degraded'] }}</span> Degraded</span></div>
                    <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full" style="background: var(--danger);"></span><span class="text-sm text-muted"><span class="font-semibold" style="color: var(--danger);">{{ $uptimeSummary['down'] }}</span> Down</span></div>
                    <span class="text-sm text-faint">{{ $uptimeSummary['total'] }} total</span>
                </div>
                @if($troubledEndpoints->count() > 0)
                    <div class="space-y-1">
                        @foreach($troubledEndpoints as $ep)
                            @php $c = $ep->current_status === 'down' ? 'var(--danger)' : 'var(--warn)'; @endphp
                            <a href="{{ route('uptime.show', $ep) }}" class="row-item flex justify-between items-center p-3 -mx-1">
                                <div class="flex items-center">
                                    <span class="w-2.5 h-2.5 rounded-full mr-3" style="background: {{ $c }};"></span>
                                    <div><span class="font-medium text-slate-100">{{ $ep->name }}</span><span class="text-sm text-muted ml-2">{{ $ep->client->company_name }}</span></div>
                                </div>
                                <div class="text-right">
                                    <span class="badge {{ $ep->current_status === 'down' ? 'badge-danger' : 'badge-warn' }}">{{ ucfirst($ep->current_status) }}</span>
                                    <div class="text-xs text-faint mt-0.5">{{ $ep->last_checked_at ? $ep->last_checked_at->diffForHumans() : 'Never checked' }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    @if($uptimeSummary['total'] > 0)
                        <p class="text-sm font-medium" style="color: var(--good);">All endpoints are healthy.</p>
                    @else
                        <p class="text-muted text-sm">No endpoints monitored yet. <a href="{{ route('uptime.create') }}" class="accent-ink hover:underline">Add one</a></p>
                    @endif
                @endif
            </div>

            {{-- Subscriptions --}}
            <div class="card p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-white">Subscription Alerts</h3>
                    <a href="{{ route('subscriptions.index') }}" class="text-sm accent-ink hover:underline">View all →</a>
                </div>
                @if($subscriptionAlerts->count() > 0)
                    <div class="space-y-1">
                        @foreach($subscriptionAlerts as $sub)
                            @php $c = $sub->status === 'overdue' ? 'var(--danger)' : 'var(--warn)'; @endphp
                            <div class="row-item flex justify-between items-center p-3 -mx-1">
                                <div class="flex items-center">
                                    <span class="w-2.5 h-2.5 rounded-full mr-3" style="background: {{ $c }};"></span>
                                    <div><span class="font-medium text-slate-100">{{ $sub->service_name }}</span><span class="text-xs text-muted ml-2">{{ $sub->category_label }}</span></div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <span class="text-sm font-semibold text-slate-200">${{ number_format($sub->amount, 2) }}</span>
                                        <div class="text-xs" style="color: {{ $c }};">{{ $sub->status === 'overdue' ? 'Overdue' : 'Due' }} {{ $sub->next_due_date->format('M j') }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('subscriptions.mark-paid', $sub) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm" style="background: var(--good-soft); color: var(--good);">Pay</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm font-medium" style="color: var(--good);">All subscriptions are up to date.</p>
                @endif
            </div>

            {{-- Recent invoices --}}
            <div class="card p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-white">Recent Invoices</h3>
                    <a href="{{ route('invoices.index') }}" class="text-sm accent-ink hover:underline">View all →</a>
                </div>
                <div class="divide-hair">
                    @forelse($recentInvoices as $inv)
                        <div class="flex justify-between items-center py-3">
                            <div>
                                <a href="{{ route('invoices.show', $inv) }}" class="accent-ink hover:underline font-medium">{{ $inv->invoice_number }}</a>
                                <span class="text-sm text-muted ml-2">{{ $inv->client->company_name }}</span>
                                @if($inv->title)<span class="text-xs text-faint ml-2">· {{ $inv->title }}</span>@endif
                            </div>
                            <div class="flex items-center gap-4">
                                @php
                                    $map = ['paid' => 'badge-good', 'sent' => 'badge-info', 'draft' => 'badge-gray', 'overdue' => 'badge-danger', 'cancelled' => 'badge-warn'];
                                @endphp
                                <span class="badge {{ $map[$inv->status] ?? 'badge-gray' }}">{{ ucfirst($inv->status) }}</span>
                                <span class="text-sm font-medium text-slate-200">${{ number_format($inv->total, 2) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-sm">No invoices yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart');
            if (ctx && window.Chart) {
                const data = @json($monthlyRevenue);
                const grid = 'rgba(148,163,184,0.10)';
                const tick = '#9aa6b8';
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.month),
                        datasets: [{
                            label: 'Revenue', data: data.map(d => d.total),
                            backgroundColor: 'rgba(99, 102, 241, 0.85)', hoverBackgroundColor: '#818cf8', borderRadius: 6, maxBarThickness: 34
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#18212f', borderColor: 'rgba(148,163,184,0.24)', borderWidth: 1, titleColor: '#e6eaf2', bodyColor: '#9aa6b8', callbacks: { label: c => '$' + c.parsed.y.toLocaleString() } } },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: tick } },
                            y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { color: tick, callback: v => '$' + v.toLocaleString() } }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
