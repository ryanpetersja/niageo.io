<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Clients']]" />
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clients</h2>
            <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">Add Client</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" class="flex gap-4 mb-6">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search clients..." class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 flex-1">
                        <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm">
                            <option value="">Active</option>
                            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm hover:bg-gray-700">Search</button>
                    </form>

                    @forelse($clients as $client)
                        <div class="flex justify-between items-center py-4 border-b last:border-b-0">
                            <div>
                                <a href="{{ route('clients.show', $client) }}" class="text-lg font-medium text-indigo-600 hover:text-indigo-800">{{ $client->company_name }}</a>
                                <div class="text-sm text-gray-500">
                                    {{ $client->billing_terms_label }} &middot; {{ $client->contacts_count }} contact(s) &middot; {{ $client->invoices_count }} invoice(s)
                                    @if(!$client->is_active)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('clients.edit', $client) }}" class="text-sm text-gray-600 hover:text-gray-800">Edit</a>
                                <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">New Invoice</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">No clients found.</p>
                    @endforelse

                    <div class="mt-4">{{ $clients->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
