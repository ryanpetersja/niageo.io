<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Subscription — {{ $bill->service_name }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('subscriptions.update', $bill) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Service Name -->
                        <div>
                            <label for="service_name" class="block text-sm font-medium text-gray-700">Service Name</label>
                            <input type="text" name="service_name" id="service_name" value="{{ old('service_name', $bill->service_name) }}" required
                                   list="common_services"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <datalist id="common_services">
                                <option value="DigitalOcean">
                                <option value="SendGrid">
                                <option value="Laravel Forge">
                                <option value="Google Workspace">
                                <option value="AWS">
                                <option value="Cloudflare">
                                <option value="GitHub">
                                <option value="Slack">
                                <option value="Figma">
                                <option value="Postmark">
                                <option value="Sentry">
                                <option value="Laravel Vapor">
                            </datalist>
                            @error('service_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category" id="category" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach(\App\Models\SubscriptionBill::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}" {{ old('category', $bill->category) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Amount -->
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Amount ($)</label>
                            <input type="number" name="amount" id="amount" value="{{ old('amount', $bill->amount) }}" required step="0.01" min="0.01"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Billing Cycle -->
                        <div>
                            <label for="billing_cycle" class="block text-sm font-medium text-gray-700">Billing Cycle</label>
                            <select name="billing_cycle" id="billing_cycle" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach(\App\Models\SubscriptionBill::BILLING_CYCLES as $key => $label)
                                    <option value="{{ $key }}" {{ old('billing_cycle', $bill->billing_cycle) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('billing_cycle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Next Due Date -->
                        <div>
                            <label for="next_due_date" class="block text-sm font-medium text-gray-700">Next Due Date</label>
                            <input type="date" name="next_due_date" id="next_due_date" value="{{ old('next_due_date', $bill->next_due_date->format('Y-m-d')) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('next_due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- URL -->
                        <div>
                            <label for="url" class="block text-sm font-medium text-gray-700">Service URL (optional)</label>
                            <input type="url" name="url" id="url" value="{{ old('url', $bill->url) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description (optional)</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $bill->description) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Notes -->
                    <div class="mt-4">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('notes', $bill->notes) }}</textarea>
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Toggles -->
                    <div class="mt-4 flex items-center gap-6">
                        <label class="inline-flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $bill->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">Active</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="hidden" name="auto_renew" value="0">
                            <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew', $bill->auto_renew) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">Auto-advance due date on payment</span>
                        </label>
                    </div>

                    @if($bill->last_paid_at)
                        <div class="mt-4 text-sm text-gray-500">
                            Last paid: {{ $bill->last_paid_at->format('M j, Y g:i A') }}
                        </div>
                    @endif

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">Cancel</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">Update Subscription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
