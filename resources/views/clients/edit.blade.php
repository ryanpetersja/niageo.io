<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Clients', 'url' => route('clients.index')], ['label' => $client->company_name, 'url' => route('clients.show', $client)], ['label' => 'Edit']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Edit Client: {{ $client->company_name }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('clients.update', $client) }}">
                    @csrf @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="company_name" value="Company Name" />
                            <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $client->company_name)" required />
                            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="billing_terms" value="Billing Terms" />
                            <select id="billing_terms" name="billing_terms" class="field mt-1">
                                @foreach(['net_15' => 'Net 15', 'net_30' => 'Net 30', 'net_60' => 'Net 60', 'due_on_receipt' => 'Due on Receipt'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('billing_terms', $client->billing_terms) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="billing_email" value="Billing Email" />
                            <x-text-input id="billing_email" name="billing_email" type="email" class="mt-1 block w-full" :value="old('billing_email', $client->billing_email)" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="Notes" />
                            <textarea id="notes" name="notes" rows="3" class="field mt-1">{{ old('notes', $client->notes) }}</textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }} class="rounded text-indigo-500 focus:ring-indigo-500" style="background: var(--surface-2); border-color: var(--border-strong);">
                            <x-input-label for="is_active" value="Active" class="!mb-0" />
                        </div>
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
                            <x-primary-button>Update Client</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
