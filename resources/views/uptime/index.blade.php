<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monitoring</h2>
            <a href="{{ route('uptime.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">Add Endpoint</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-3 h-3 rounded-full bg-green-500 mr-3"></div>
                        <div>
                            <p class="text-sm text-gray-500">Up</p>
                            <p class="text-2xl font-bold text-green-600">{{ $summary['up'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-3 h-3 rounded-full bg-yellow-500 mr-3"></div>
                        <div>
                            <p class="text-sm text-gray-500">Degraded</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $summary['degraded'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-3 h-3 rounded-full bg-red-500 mr-3"></div>
                        <div>
                            <p class="text-sm text-gray-500">Down</p>
                            <p class="text-2xl font-bold text-red-600">{{ $summary['down'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="mb-4">
                <form method="GET" action="{{ route('uptime.index') }}" class="flex items-center gap-3">
                    <select name="client_id" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <!-- Endpoints List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @forelse($endpoints as $endpoint)
                    <div class="flex items-center justify-between px-6 py-4 border-b last:border-b-0 hover:bg-gray-50">
                        <div class="flex items-center min-w-0 flex-1">
                            <div class="flex-shrink-0 w-3 h-3 rounded-full bg-{{ $endpoint->status_color }}-500 mr-4" title="{{ ucfirst($endpoint->current_status) }}"></div>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('uptime.show', $endpoint) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $endpoint->name }}</a>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <span class="text-xs text-gray-500">{{ $endpoint->client->company_name }}</span>
                                    <span class="text-xs text-gray-400 truncate max-w-xs" title="{{ $endpoint->url }}">{{ $endpoint->url }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 ml-4">
                            <div class="text-right">
                                @if($endpoint->last_response_time_ms !== null)
                                    <span class="text-sm font-medium {{ $endpoint->current_status === 'degraded' ? 'text-yellow-600' : 'text-gray-700' }}">{{ $endpoint->last_response_time_ms }}ms</span>
                                @else
                                    <span class="text-sm text-gray-400">--</span>
                                @endif
                                <div class="text-xs text-gray-400">
                                    @if($endpoint->last_checked_at)
                                        {{ $endpoint->last_checked_at->diffForHumans() }}
                                    @else
                                        Never checked
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('uptime.check', $endpoint) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium">Check Now</button>
                                </form>
                                <a href="{{ route('uptime.edit', $endpoint) }}" class="text-xs text-gray-500 hover:text-gray-700">Edit</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <p class="text-lg mb-2">No endpoints monitored yet</p>
                        <p class="text-sm">Add your first endpoint to start monitoring.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
