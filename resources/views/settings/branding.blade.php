<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Settings'], ['label' => 'Branding']]" />
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Branding Settings</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf @method('PUT')

                    <div>
                        <x-input-label for="company_name" value="Company Name" />
                        <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $branding->company_name)" required />
                        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="logo" value="Logo" />
                        @if($branding->logo_url)
                            <div class="mt-2 mb-3">
                                <img src="{{ $branding->logo_url }}" alt="Current logo" class="h-16 object-contain">
                                <span class="text-xs text-gray-500 block mt-1">Current logo</span>
                            </div>
                        @endif
                        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/svg+xml" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        <p class="text-xs text-gray-500 mt-1">PNG, JPG, or SVG. Max 2MB.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="phone" value="Phone" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $branding->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $branding->email)" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="website" value="Website" />
                        <x-text-input id="website" name="website" type="url" class="mt-1 block w-full" :value="old('website', $branding->website)" placeholder="https://" />
                        <x-input-error :messages="$errors->get('website')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Address" />
                        <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('address', $branding->address) }}</textarea>
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="footer_text" value="Invoice Footer Text" />
                        <textarea id="footer_text" name="footer_text" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('footer_text', $branding->footer_text) }}</textarea>
                        <x-input-error :messages="$errors->get('footer_text')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Save Settings</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
