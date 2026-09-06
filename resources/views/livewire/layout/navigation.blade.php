<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b backdrop-blur" style="background-color: rgba(10,14,22,.80); border-color: var(--border);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex min-w-0 flex-1">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold text-white">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 4px 14px rgba(99,102,241,.4);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
                        </span>
                        Niageo<span class="text-indigo-400">Ops</span>
                    </a>
                </div>

                <div class="hidden sm:-my-px sm:ms-10 sm:flex nav-links">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        Dashboard
                    </x-nav-link>
                    <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" wire:navigate>
                        Clients
                    </x-nav-link>
                    <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" wire:navigate>
                        Invoices
                    </x-nav-link>
                    <x-nav-link :href="route('billing-plans.index')" :active="request()->routeIs('billing-plans.*') || request()->routeIs('products.*')" wire:navigate>
                        Recurring
                    </x-nav-link>
                    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>
                        Reports
                    </x-nav-link>
                    <x-nav-link :href="route('scopes.index')" :active="request()->routeIs('scopes.*')" wire:navigate>
                        Scopes
                    </x-nav-link>
                    <x-nav-link :href="route('uptime.index')" :active="request()->routeIs('uptime.*')" wire:navigate>
                        Monitoring
                    </x-nav-link>
                    <x-nav-link :href="route('subscriptions.index')" :active="request()->routeIs('subscriptions.*')" wire:navigate>
                        Subscriptions
                    </x-nav-link>
                    @can('manage-users')
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>
                        Users
                    </x-nav-link>
                    @endcan
                    @can('manage-settings')
                    @php $settingsActive = request()->routeIs('settings.*') || request()->routeIs('logs.*'); @endphp
                    <div class="inline-flex items-center">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center gap-1 px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none h-16 {{ $settingsActive ? 'border-indigo-400 text-white' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-600' }}">
                                    Settings
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('settings.branding')" wire:navigate>Branding</x-dropdown-link>
                                <x-dropdown-link :href="route('settings.report-preferences')" wire:navigate>Report Preferences</x-dropdown-link>
                                <x-dropdown-link :href="route('settings.ai-usage')" wire:navigate>AI Usage &amp; Budget</x-dropdown-link>
                                <x-dropdown-link :href="route('logs.index')" wire:navigate>Error Logs</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @endcan
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-2 text-sm leading-4 font-medium rounded-lg text-slate-300 hover:text-white hover:bg-slate-700/50 focus:outline-none transition ease-in-out duration-150">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>Profile</x-dropdown-link>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>Log Out</x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-700/50 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')" wire:navigate>Clients</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" wire:navigate>Invoices</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('billing-plans.index')" :active="request()->routeIs('billing-plans.*') || request()->routeIs('products.*')" wire:navigate>Recurring</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>Reports</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('scopes.index')" :active="request()->routeIs('scopes.*')" wire:navigate>Scopes</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('uptime.index')" :active="request()->routeIs('uptime.*')" wire:navigate>Monitoring</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('subscriptions.index')" :active="request()->routeIs('subscriptions.*')" wire:navigate>Subscriptions</x-responsive-nav-link>
            @can('manage-users')
            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>Users</x-responsive-nav-link>
            @endcan
            @can('manage-settings')
            <x-responsive-nav-link :href="route('settings.branding')" :active="request()->routeIs('settings.branding')" wire:navigate>Settings</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('settings.ai-usage')" :active="request()->routeIs('settings.ai-usage')" wire:navigate>AI Usage &amp; Budget</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('logs.index')" :active="request()->routeIs('logs.*')" wire:navigate>Logs</x-responsive-nav-link>
            @endcan
        </div>
        <div class="pt-4 pb-1 border-t" style="border-color: var(--border);">
            <div class="px-4">
                <div class="font-medium text-base text-slate-100" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-slate-400">{{ auth()->user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>Profile</x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>Log Out</x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
