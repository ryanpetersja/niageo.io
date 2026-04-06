<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subscriptions</h2>
            <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">Add Subscription</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Est. Monthly Cost</p>
                    <p class="text-2xl font-bold text-indigo-600">${{ number_format($summary['total_monthly'], 2) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Active</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $summary['active_count'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Due Soon</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $summary['due_soon_count'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Overdue</p>
                    <p class="text-2xl font-bold text-red-600">{{ $summary['overdue_count'] }}</p>
                    @if($summary['overdue_total'] > 0)
                        <p class="text-xs text-red-500 mt-1">${{ number_format($summary['overdue_total'], 2) }} total</p>
                    @endif
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-4 flex items-center gap-3">
                <form method="GET" action="{{ route('subscriptions.index') }}" class="flex items-center gap-3">
                    <select name="category" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Categories</option>
                        @foreach(\App\Models\SubscriptionBill::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="due_soon" {{ request('status') == 'due_soon' ? 'selected' : '' }}>Due Soon</option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </form>
            </div>

            <!-- Bills List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @forelse($bills as $bill)
                    <div class="flex items-center justify-between px-6 py-4 border-b last:border-b-0 hover:bg-gray-50 {{ !$bill->is_active ? 'opacity-50' : '' }}">
                        <div class="flex items-center min-w-0 flex-1">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900">{{ $bill->service_name }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $bill->status_color }}-100 text-{{ $bill->status_color }}-800">
                                        {{ str_replace('_', ' ', ucfirst($bill->status)) }}
                                    </span>
                                    @if(!$bill->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <span class="text-xs text-gray-500">{{ $bill->category_label }}</span>
                                    <span class="text-xs text-gray-400">{{ $bill->cycle_label }}</span>
                                    @if($bill->description)
                                        <span class="text-xs text-gray-400 truncate max-w-xs">{{ $bill->description }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 ml-4">
                            <div class="text-right">
                                <span class="text-sm font-semibold text-gray-800">${{ number_format($bill->amount, 2) }}</span>
                                <div class="text-xs {{ $bill->status === 'overdue' ? 'text-red-500 font-medium' : ($bill->status === 'due_soon' ? 'text-yellow-600' : 'text-gray-400') }}">
                                    @if($bill->status === 'paid')
                                        Paid {{ $bill->last_paid_at?->format('M j') }}
                                    @else
                                        Due {{ $bill->next_due_date->format('M j, Y') }}
                                        @if($bill->next_due_date->isPast())
                                            ({{ $bill->next_due_date->diffForHumans() }})
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($bill->is_active && $bill->status !== 'paid')
                                    <form method="POST" action="{{ route('subscriptions.mark-paid', $bill) }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium">Pay</button>
                                    </form>
                                @endif
                                @if($bill->url)
                                    <a href="{{ $bill->url }}" target="_blank" rel="noopener" class="text-xs text-indigo-600 hover:text-indigo-800">Open</a>
                                @endif
                                <a href="{{ route('subscriptions.edit', $bill) }}" class="text-xs text-gray-500 hover:text-gray-700">Edit</a>
                                <form method="POST" action="{{ route('subscriptions.destroy', $bill) }}" onsubmit="return confirm('Delete this subscription?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <p class="text-lg mb-2">No subscriptions tracked yet</p>
                        <p class="text-sm">Add your first subscription to start tracking recurring bills.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
