<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Total Revenue</div>
                    <div class="mt-1 text-2xl font-bold text-green-600">${{ number_format($metrics['total_revenue'], 2) }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Outstanding</div>
                    <div class="mt-1 text-2xl font-bold text-yellow-600">${{ number_format($metrics['total_outstanding'], 2) }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Overdue</div>
                    <div class="mt-1 text-2xl font-bold text-red-600">${{ number_format($metrics['total_overdue'], 2) }}</div>
                    @if($metrics['overdue_count'] > 0)
                        <div class="text-xs text-red-500 mt-1">{{ $metrics['overdue_count'] }} invoice(s)</div>
                    @endif
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Collection Rate</div>
                    <div class="mt-1 text-2xl font-bold text-indigo-600">{{ $metrics['collection_rate'] }}%</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $metrics['paid_count'] }}/{{ $metrics['invoice_count'] }} invoices</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Revenue ({{ now()->year }})</h3>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Overdue Invoices</h3>
                    @forelse($overdueInvoices as $inv)
                        <a href="{{ route('invoices.show', $inv) }}" class="block p-3 mb-2 bg-red-50 rounded-lg hover:bg-red-100 transition">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $inv->invoice_number }}</span>
                                    <span class="text-sm text-gray-500 ml-2">{{ $inv->client->company_name }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-red-600">${{ number_format($inv->balance_due, 2) }}</div>
                                    <div class="text-xs text-red-500">Due {{ $inv->due_date->diffForHumans() }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-gray-500 text-sm">No overdue invoices.</p>
                    @endforelse
                </div>
            </div>

            <!-- Uptime Monitoring -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Uptime Monitoring</h3>
                    <a href="{{ route('uptime.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All</a>
                </div>

                <!-- Summary row -->
                <div class="flex items-center gap-6 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                        <span class="text-sm text-gray-600"><span class="font-semibold text-green-600">{{ $uptimeSummary['up'] }}</span> Up</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                        <span class="text-sm text-gray-600"><span class="font-semibold text-yellow-600">{{ $uptimeSummary['degraded'] }}</span> Degraded</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                        <span class="text-sm text-gray-600"><span class="font-semibold text-red-600">{{ $uptimeSummary['down'] }}</span> Down</span>
                    </div>
                    <span class="text-sm text-gray-400">{{ $uptimeSummary['total'] }} total</span>
                </div>

                @if($troubledEndpoints->count() > 0)
                    @foreach($troubledEndpoints as $ep)
                        @php
                            $epColor = $ep->current_status === 'down' ? 'red' : 'yellow';
                            $epBg = $ep->current_status === 'down' ? 'bg-red-50' : 'bg-yellow-50';
                        @endphp
                        <a href="{{ route('uptime.show', $ep) }}" class="block p-3 mb-2 {{ $epBg }} rounded-lg hover:opacity-80 transition">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-2.5 h-2.5 rounded-full bg-{{ $epColor }}-500 mr-3"></div>
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $ep->name }}</span>
                                        <span class="text-sm text-gray-500 ml-2">{{ $ep->client->company_name }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $epColor }}-100 text-{{ $epColor }}-800">{{ ucfirst($ep->current_status) }}</span>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $ep->last_checked_at ? $ep->last_checked_at->diffForHumans() : 'Never checked' }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @else
                    @if($uptimeSummary['total'] > 0)
                        <p class="text-green-600 text-sm font-medium">All endpoints are healthy.</p>
                    @else
                        <p class="text-gray-500 text-sm">No endpoints monitored yet. <a href="{{ route('uptime.create') }}" class="text-indigo-600 hover:text-indigo-800">Add one</a></p>
                    @endif
                @endif
            </div>

            <!-- Subscription Alerts -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Subscription Alerts</h3>
                    <a href="{{ route('subscriptions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All</a>
                </div>

                @if($subscriptionAlerts->count() > 0)
                    @foreach($subscriptionAlerts as $sub)
                        @php
                            $subColor = $sub->status === 'overdue' ? 'red' : 'yellow';
                            $subBg = $sub->status === 'overdue' ? 'bg-red-50' : 'bg-yellow-50';
                        @endphp
                        <div class="flex justify-between items-center p-3 mb-2 {{ $subBg }} rounded-lg">
                            <div class="flex items-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-{{ $subColor }}-500 mr-3"></div>
                                <div>
                                    <span class="font-medium text-gray-900">{{ $sub->service_name }}</span>
                                    <span class="text-xs text-gray-500 ml-2">{{ $sub->category_label }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-gray-800">${{ number_format($sub->amount, 2) }}</span>
                                    <div class="text-xs text-{{ $subColor }}-600">
                                        {{ $sub->status === 'overdue' ? 'Overdue' : 'Due' }} {{ $sub->next_due_date->format('M j') }}
                                        @if($sub->next_due_date->isPast())
                                            ({{ $sub->next_due_date->diffForHumans() }})
                                        @endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('subscriptions.mark-paid', $sub) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium px-2 py-1 bg-green-50 rounded hover:bg-green-100 transition">Pay</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-green-600 text-sm font-medium">All subscriptions are up to date.</p>
                @endif
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Invoices</h3>
                    <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">New Invoice</a>
                </div>
                @forelse($recentInvoices as $inv)
                    <div class="flex justify-between items-center py-3 border-b last:border-b-0">
                        <div>
                            <a href="{{ route('invoices.show', $inv) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $inv->invoice_number }}</a>
                            <span class="text-sm text-gray-500 ml-2">{{ $inv->client->company_name }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $inv->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $inv->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $inv->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $inv->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $inv->status === 'cancelled' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            ">{{ ucfirst($inv->status) }}</span>
                            <span class="text-sm font-medium">${{ number_format($inv->total, 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No invoices yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart');
            if (ctx) {
                const data = @json($monthlyRevenue);
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.month),
                        datasets: [{ label: 'Revenue', data: data.map(d => d.total), backgroundColor: 'rgba(79, 70, 229, 0.8)', borderRadius: 4 }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString() } } }
                    }
                });
            }
        });
    </script>
</x-app-layout>
