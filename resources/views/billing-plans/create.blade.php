<x-app-layout voice-page="billing-plans.form">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Billing Plans', 'url' => route('billing-plans.index')], ['label' => 'New Plan']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">New Billing Plan</h2>
        <p class="text-sm text-muted mt-0.5">Define the client's package once; each period's invoice is generated from it.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('billing-plans._form', ['mode' => 'create'])
        </div>
    </div>
</x-app-layout>
