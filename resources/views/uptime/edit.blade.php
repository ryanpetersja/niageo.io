<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Monitoring', 'url' => route('uptime.index')], ['label' => $endpoint->name, 'url' => route('uptime.show', $endpoint)], ['label' => 'Edit']]" />
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Endpoint</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('uptime.update', $endpoint) }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select a client...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $endpoint->client_id) == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="name" value="Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $endpoint->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="url" value="URL" />
                            <x-text-input id="url" name="url" type="url" class="mt-1 block w-full" :value="old('url', $endpoint->url)" required />
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="check_interval_minutes" value="Check Interval (minutes)" />
                                <x-text-input id="check_interval_minutes" name="check_interval_minutes" type="number" class="mt-1 block w-full" :value="old('check_interval_minutes', $endpoint->check_interval_minutes)" min="1" max="1440" required />
                                <x-input-error :messages="$errors->get('check_interval_minutes')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="timeout_seconds" value="Timeout (seconds)" />
                                <x-text-input id="timeout_seconds" name="timeout_seconds" type="number" class="mt-1 block w-full" :value="old('timeout_seconds', $endpoint->timeout_seconds)" min="1" max="60" required />
                                <x-input-error :messages="$errors->get('timeout_seconds')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="degraded_threshold_ms" value="Degraded Threshold (ms)" />
                                <x-text-input id="degraded_threshold_ms" name="degraded_threshold_ms" type="number" class="mt-1 block w-full" :value="old('degraded_threshold_ms', $endpoint->degraded_threshold_ms)" min="100" max="30000" required />
                                <x-input-error :messages="$errors->get('degraded_threshold_ms')" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $endpoint->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <x-input-label for="is_active" value="Active" />
                        </div>
                        <div class="flex justify-end gap-4">
                            <a href="{{ route('uptime.show', $endpoint) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
                            <x-primary-button>Update Endpoint</x-primary-button>
                        </div>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <form method="POST" action="{{ route('uptime.destroy', $endpoint) }}" onsubmit="return confirm('Delete this endpoint and all check history?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">Delete Endpoint</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
