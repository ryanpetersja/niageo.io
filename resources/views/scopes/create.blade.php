<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Scopes', 'url' => route('scopes.index')], ['label' => 'New Scope']]" />
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Scope</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('scopes.store') }}" x-data="{ generateAi: true }">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a client...</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id', $selectedClientId) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="title" value="Scope Title" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required placeholder="e.g., Website Redesign Project" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mb-6">
                        <x-input-label for="description" value="Project Description" />
                        <textarea id="description" name="description" rows="5"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="Describe the project in detail. The AI will use this to generate scope sections and line items...">{{ old('description') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Provide a detailed project description for the best AI-generated results.</p>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="currency" value="Currency" />
                            <select id="currency" name="currency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="USD" {{ old('currency', 'USD') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                <option value="JMD" {{ old('currency') === 'JMD' ? 'selected' : '' }}>JMD (J$)</option>
                                <option value="CAD" {{ old('currency') === 'CAD' ? 'selected' : '' }}>CAD (CA$)</option>
                                <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP (&pound;)</option>
                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR (&euro;)</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="notes" value="Notes (optional)" />
                            <textarea id="notes" name="notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('scopes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
                        <button type="submit" name="generate_ai" value="0"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Create Blank
                        </button>
                        <button type="submit" name="generate_ai" value="1"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Create & Generate with AI
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
