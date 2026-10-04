<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Clients']]" />
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-white leading-tight">Clients</h2>
            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                Add Client
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="p-6">
                    <form method="GET" class="flex gap-3 mb-6">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search clients..." class="field flex-1">
                        <select name="status" onchange="this.form.submit()" class="field" style="width:auto;">
                            <option value="">Active</option>
                            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Search</button>
                    </form>

                    <div class="divide-hair">
                        @forelse($clients as $client)
                            <div class="flex justify-between items-center py-4">
                                <div>
                                    <a href="{{ route('clients.show', $client) }}" class="text-lg font-medium accent-ink hover:underline">{{ $client->company_name }}</a>
                                    <div class="text-sm text-muted">
                                        {{ $client->billing_terms_label }} &middot; {{ $client->contacts_count }} contact(s) &middot; {{ $client->invoices_count }} invoice(s)
                                        @if(!$client->is_active)
                                            <span class="badge badge-danger ml-2">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <a href="{{ route('clients.edit', $client) }}" class="text-sm text-muted hover:text-white">Edit</a>
                                    <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="text-sm accent-ink hover:underline">New Invoice</a>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted py-6 text-center">No clients found.</p>
                        @endforelse
                    </div>

                    <div class="mt-4">{{ $clients->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
