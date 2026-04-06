<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Client</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('clients.store') }}">
                    @csrf
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="company_name" value="Company Name" />
                            <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name')" required />
                            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="billing_terms" value="Billing Terms" />
                            <select id="billing_terms" name="billing_terms" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="net_15" {{ old('billing_terms') === 'net_15' ? 'selected' : '' }}>Net 15</option>
                                <option value="net_30" {{ old('billing_terms', 'net_30') === 'net_30' ? 'selected' : '' }}>Net 30</option>
                                <option value="net_60" {{ old('billing_terms') === 'net_60' ? 'selected' : '' }}>Net 60</option>
                                <option value="due_on_receipt" {{ old('billing_terms') === 'due_on_receipt' ? 'selected' : '' }}>Due on Receipt</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="billing_email" value="Billing Email" />
                            <x-text-input id="billing_email" name="billing_email" type="email" class="mt-1 block w-full" :value="old('billing_email')" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="Notes" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <x-input-label for="is_active" value="Active" />
                        </div>
                        <div class="flex justify-end gap-4">
                            <a href="{{ route('clients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
                            <x-primary-button>Create Client</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
