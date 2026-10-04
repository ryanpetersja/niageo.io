<x-app-layout voice-page="billing-plans.index">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Billing Plans']]" />
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-white leading-tight">Billing Plans</h2>
                <p class="text-sm text-muted mt-0.5">Recurring packages that generate each period's invoice automatically.</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Products</a>
                <form method="POST" action="{{ route('billing-plans.generate-due') }}" data-voice-action="generate-due">
                    @csrf
                    <button type="submit" class="btn btn-secondary" title="Create the invoices whose issue date has arrived (the same thing the daily schedule does)">Generate due invoices</button>
                </form>
                <a href="{{ route('billing-plans.create') }}" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    New Plan
                </a>
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

            @php
                $cards = [
                    ['Active plans', $summary['active'], 'billing on schedule', 'var(--text)'],
                    ['Monthly recurring', '$' . number_format($summary['monthly_value'], 2), 'per month before tax', 'var(--good)'],
                    ['Due now', $summary['due_now'], 'invoice' . ($summary['due_now'] == 1 ? '' : 's') . ' waiting to be generated', $summary['due_now'] > 0 ? 'var(--warn)' : 'var(--text)'],
                    ['Paused', $summary['paused'], 'not generating', 'var(--text-muted)'],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
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
                    <form method="GET" data-voice-form="filters" class="flex flex-wrap gap-3 mb-6">
                        <select name="client_id" class="field" style="width:auto;">
                            <option value="">All Clients</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="field" style="width:auto;">
                            <option value="">All Statuses</option>
                            @foreach(\App\Models\BillingPlan::STATUSES as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                        @if(request()->hasAny(['client_id', 'status']))
                            <a href="{{ route('billing-plans.index') }}" class="btn btn-ghost">Clear</a>
                        @endif
                    </form>

                    <div class="overflow-x-auto">
                    <table class="table-cards min-w-full">
                        <thead>
                            <tr class="border-b" style="border-color: var(--border);">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Plan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Cycle</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-muted uppercase">Per period</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Next period</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Next invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-hair">
                            @php $statusBadge = ['active' => 'badge-good', 'paused' => 'badge-warn', 'ended' => 'badge-gray']; @endphp
                            @forelse($billingPlans as $plan)
                                <tr class="row-item" data-voice-plan="{{ $plan->name }}" data-voice-url="{{ route('billing-plans.show', $plan) }}">
                                    <td data-label="Plan" class="px-4 py-3">
                                        <a href="{{ route('billing-plans.show', $plan) }}" class="accent-ink hover:underline font-medium">{{ $plan->name }}</a>
                                        <div class="text-xs text-faint">{{ $plan->items->count() }} item{{ $plan->items->count() == 1 ? '' : 's' }}</div>
                                    </td>
                                    <td data-label="Client" class="px-4 py-3 text-sm text-muted">{{ $plan->client->company_name }}</td>
                                    <td data-label="Cycle" class="px-4 py-3 text-sm text-muted">{{ $plan->cycle_label }}</td>
                                    <td data-label="Per period" class="px-4 py-3 text-sm text-right text-slate-200">${{ number_format($plan->period_total, 2) }}</td>
                                    <td data-label="Next period" class="px-4 py-3 text-sm text-muted">
                                        {{ $plan->next_period_start ? $plan->next_period_start->format('M d, Y') : '—' }}
                                    </td>
                                    <td data-label="Next invoice" class="px-4 py-3 text-sm text-muted">
                                        @if($plan->next_period_start && $plan->status === 'active')
                                            {{ $plan->next_period_start->copy()->subDays($plan->issue_days_before)->format('M d, Y') }}
                                            @if($dueMap[$plan->id] ?? false)
                                                <span class="badge badge-warn ml-1">Due now</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="Status" class="px-4 py-3"><span class="badge {{ $statusBadge[$plan->status] ?? 'badge-gray' }}">{{ ucfirst($plan->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-muted">No billing plans yet. Create one to start generating invoices automatically.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <p class="mt-4 text-xs text-faint">Invoices are generated as drafts every morning for plans whose issue date has arrived, and can be generated early from a plan's page.</p>
        </div>
    </div>
</x-app-layout>
