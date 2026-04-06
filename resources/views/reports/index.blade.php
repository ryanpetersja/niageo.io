<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
            <a href="{{ route('reports.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">New Report</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" class="flex flex-wrap gap-4 mb-6">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reports..." class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 flex-1 min-w-[200px]">
                        <select name="status" class="rounded-md border-gray-300 shadow-sm">
                            <option value="">All Statuses</option>
                            @foreach(['draft', 'generated', 'sent', 'archived'] as $s)
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
                    </form>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Report #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Commits</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($reports as $report)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><a href="{{ route('reports.show', $report) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $report->report_number }}</a></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $report->client->company_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($report->title, 40) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $report->date_from->format('M d') }} - {{ $report->date_to->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $report->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                            {{ $report->status === 'generated' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $report->status === 'sent' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $report->status === 'archived' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        ">{{ ucfirst($report->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $report->commit_count }}</td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <a href="{{ route('reports.show', $report) }}" class="text-indigo-600 hover:text-indigo-800">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No reports found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $reports->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
