<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoices</h2>
            <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">New Invoice</a>
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

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Invoiced</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($summary->total_amount ?? 0, 2) }}</div>
                    <div class="mt-1 text-xs text-gray-500">{{ $summary->total_count ?? 0 }} invoice{{ ($summary->total_count ?? 0) != 1 ? 's' : '' }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-green-200 p-4">
                    <div class="text-xs font-medium text-green-600 uppercase tracking-wider">Paid</div>
                    <div class="mt-1 text-2xl font-bold text-green-700">${{ number_format($summary->total_paid ?? 0, 2) }}</div>
                    <div class="mt-1 text-xs text-green-600">{{ $summary->paid_count ?? 0 }} paid</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-amber-200 p-4">
                    <div class="text-xs font-medium text-amber-600 uppercase tracking-wider">Outstanding</div>
                    <div class="mt-1 text-2xl font-bold text-amber-700">${{ number_format(max(0, $summary->total_outstanding ?? 0), 2) }}</div>
                    <div class="mt-1 text-xs text-amber-600">{{ ($summary->draft_count ?? 0) + ($summary->sent_count ?? 0) }} unpaid</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-red-200 p-4">
                    <div class="text-xs font-medium text-red-600 uppercase tracking-wider">Overdue</div>
                    <div class="mt-1 text-2xl font-bold text-red-700">${{ number_format($summary->overdue_amount ?? 0, 2) }}</div>
                    <div class="mt-1 text-xs text-red-600">{{ $summary->overdue_count ?? 0 }} overdue</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    {{-- Filters --}}
                    <form method="GET" class="flex flex-wrap gap-4 mb-6">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoices..." class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 flex-1 min-w-[200px]">
                        <select name="status" class="rounded-md border-gray-300 shadow-sm">
                            <option value="">All Statuses</option>
                            @foreach(['draft', 'sent', 'paid', 'overdue', 'cancelled'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <select name="client_id" class="rounded-md border-gray-300 shadow-sm">
                            <option value="">All Clients</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm hover:bg-gray-700">Filter</button>
                        @if(request()->hasAny(['search', 'status', 'client_id']))
                            <a href="{{ route('invoices.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md text-sm hover:bg-gray-50">Clear</a>
                        @endif
                    </form>

                    {{-- Actions bar --}}
                    @if($invoices->total() > 0)
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                            <div class="text-sm text-gray-500">
                                Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                            </div>
                            <a href="{{ route('invoices.download-all', request()->query()) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-medium hover:bg-gray-900 transition"
                               onclick="this.innerHTML='<svg class=\'animate-spin h-4 w-4 text-white inline mr-2\' xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg>Generating...'; this.style.pointerEvents='none';">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download All PDFs
                            </a>
                        </div>
                    @endif

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($invoices as $inv)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><a href="{{ route('invoices.show', $inv) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $inv->invoice_number }}</a></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $inv->client->company_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $inv->issue_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $inv->due_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $inv->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $inv->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $inv->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                            {{ $inv->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $inv->status === 'cancelled' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        ">{{ ucfirst($inv->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">${{ number_format($inv->total, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-medium {{ $inv->balance_due > 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($inv->balance_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No invoices found.</td></tr>
                            @endforelse
                        </tbody>
                        @if($invoices->count() > 0)
                            <tfoot class="bg-gray-50">
                                <tr class="font-semibold">
                                    <td colspan="5" class="px-4 py-3 text-sm text-gray-700 uppercase tracking-wider">Page Totals</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900">${{ number_format($invoices->sum('total'), 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900">${{ number_format($invoices->sum('balance_due'), 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                    <div class="mt-4">{{ $invoices->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
