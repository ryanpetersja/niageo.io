<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Scopes']]" />
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Scopes</h2>
            <a href="{{ route('scopes.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">New Scope</a>
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
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search scopes..." class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 flex-1 min-w-[200px]">
                        <select name="status" class="rounded-md border-gray-300 shadow-sm">
                            <option value="">All Statuses</option>
                            @foreach(['draft', 'sent', 'approved', 'archived'] as $s)
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Scope #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Items</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($scopes as $scope)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><a href="{{ route('scopes.show', $scope) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $scope->scope_number }}</a></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $scope->client->company_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($scope->title, 40) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $scope->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                            {{ $scope->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $scope->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $scope->status === 'archived' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        ">{{ ucfirst($scope->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $scope->items_count ?? $scope->items->count() }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $scope->currency_symbol }}{{ number_format($scope->total_price, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $scope->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <a href="{{ route('scopes.show', $scope) }}" class="text-indigo-600 hover:text-indigo-800">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No scopes found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $scopes->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
