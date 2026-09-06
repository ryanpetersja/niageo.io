<x-app-layout voice-page="billing-plans.form">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Billing Plans', 'url' => route('billing-plans.index')], ['label' => $billingPlan->name, 'url' => route('billing-plans.show', $billingPlan)], ['label' => 'Edit']]" />
        <h2 class="font-semibold text-xl text-white leading-tight">Edit Plan: {{ $billingPlan->name }}</h2>
        <p class="text-sm text-muted mt-0.5">Changes apply to invoices generated from now on; existing invoices are not touched.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ session('error') }}</div>
            @endif
            @include('billing-plans._form', ['mode' => 'edit'])
        </div>
    </div>
</x-app-layout>
