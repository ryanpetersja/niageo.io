<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Monitoring', 'url' => route('uptime.index')], ['label' => $endpoint->name]]" />
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-{{ $endpoint->status_color }}-500"></div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $endpoint->name }}</h2>
            </div>
            <div class="flex gap-2 items-center">
                <form method="POST" action="{{ route('uptime.check', $endpoint) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">Check Now</button>
                </form>
                <a href="{{ route('uptime.edit', $endpoint) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <!-- Endpoint Details -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Details</h3>
                    <dl class="space-y-3">
                        <div><dt class="text-sm text-gray-500">URL</dt><dd class="font-medium"><a href="{{ $endpoint->url }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">{{ $endpoint->url }}</a></dd></div>
                        <div><dt class="text-sm text-gray-500">Client</dt><dd class="font-medium"><a href="{{ route('clients.show', $endpoint->client) }}" class="text-indigo-600 hover:text-indigo-800">{{ $endpoint->client->company_name }}</a></dd></div>
                        <div><dt class="text-sm text-gray-500">Status</dt><dd><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $endpoint->status_color }}-100 text-{{ $endpoint->status_color }}-800">{{ ucfirst($endpoint->current_status) }}</span></dd></div>
                        <div><dt class="text-sm text-gray-500">Active</dt><dd><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $endpoint->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $endpoint->is_active ? 'Yes' : 'No' }}</span></dd></div>
                    </dl>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Configuration</h3>
                    <dl class="space-y-3">
                        <div><dt class="text-sm text-gray-500">Check Interval</dt><dd class="font-medium">Every {{ $endpoint->check_interval_minutes }} minute{{ $endpoint->check_interval_minutes !== 1 ? 's' : '' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Timeout</dt><dd class="font-medium">{{ $endpoint->timeout_seconds }} second{{ $endpoint->timeout_seconds !== 1 ? 's' : '' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">Degraded Threshold</dt><dd class="font-medium">{{ number_format($endpoint->degraded_threshold_ms) }}ms</dd></div>
                        <div><dt class="text-sm text-gray-500">Last Checked</dt><dd class="font-medium">{{ $endpoint->last_checked_at ? $endpoint->last_checked_at->format('M d, Y H:i:s') . ' (' . $endpoint->last_checked_at->diffForHumans() . ')' : 'Never' }}</dd></div>
                        @if($endpoint->last_response_time_ms !== null)
                            <div><dt class="text-sm text-gray-500">Last Response Time</dt><dd class="font-medium">{{ $endpoint->last_response_time_ms }}ms</dd></div>
                        @endif
                        @if($endpoint->last_error_message)
                            <div><dt class="text-sm text-gray-500">Last Error</dt><dd class="font-medium text-red-600">{{ $endpoint->last_error_message }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Check History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Check History <span class="text-sm font-normal text-gray-400">(last 100)</span></h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Checked At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($checks as $check)
                                @php
                                    $checkColor = match($check->status) {
                                        'up' => 'green',
                                        'degraded' => 'yellow',
                                        'down' => 'red',
                                        default => 'gray',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $check->checked_at->format('M d, Y H:i:s') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $checkColor }}-100 text-{{ $checkColor }}-800">{{ ucfirst($check->status) }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $check->response_time_ms !== null ? $check->response_time_ms . 'ms' : '--' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $check->status_code ?? '--' }}</td>
                                    <td class="px-6 py-3 text-sm text-red-600 max-w-xs truncate" title="{{ $check->error_message }}">{{ $check->error_message ?? '--' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No checks recorded yet. Click "Check Now" to run the first check.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
