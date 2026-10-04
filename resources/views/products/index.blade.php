<x-app-layout voice-page="products.index">
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Billing Plans', 'url' => route('billing-plans.index')], ['label' => 'Products']]" />
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-white leading-tight">Products</h2>
                <p class="text-sm text-muted mt-0.5">The catalogue used to build billing plans. Prices here are only defaults; each plan sets the client's own price.</p>
            </div>
            <a href="{{ route('billing-plans.index') }}" class="btn btn-secondary">Back to plans</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--good-soft); color: var(--good);">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--danger-soft); color: var(--danger);">{{ $errors->first() }}</div>
            @endif

            <div class="card p-6">
                <h3 class="text-base font-semibold text-white mb-4">Add a product</h3>
                <form method="POST" action="{{ route('products.store') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end" data-voice-form="product">
                    @csrf
                    <div class="md:col-span-3">
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" placeholder="e.g. Website hosting" required />
                    </div>
                    <div class="md:col-span-4">
                        <x-input-label for="description" value="Default invoice description" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" placeholder="Shown on the invoice line" />
                    </div>
                    <div class="md:col-span-1">
                        <x-input-label for="unit" value="Unit" />
                        <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" :value="old('unit', 'month')" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="default_unit_price" value="Default price" />
                        <x-text-input id="default_unit_price" name="default_unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('default_unit_price', '0')" />
                    </div>
                    <div class="md:col-span-2">
                        <x-primary-button class="w-full justify-center">Add</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="p-6">
                    <div class="overflow-x-auto">
                    <table class="table-cards min-w-full">
                        <thead>
                            <tr class="border-b" style="border-color: var(--border);">
                                <th class="px-3 py-3 text-left text-xs font-semibold text-muted uppercase">Product</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-muted uppercase">Default description</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-muted uppercase">Unit</th>
                                <th class="px-3 py-3 text-right text-xs font-semibold text-muted uppercase">Default price</th>
                                <th class="px-3 py-3 text-right text-xs font-semibold text-muted uppercase">Used in</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-muted uppercase">Status</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-hair">
                            @forelse($products as $product)
                                <tr class="row-item" x-data="{ editing: false }" data-voice-product="{{ $product->name }}">
                                    <td colspan="7" class="px-3 py-3">
                                        <div x-show="!editing" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center text-sm">
                                            <div class="md:col-span-3 font-medium text-slate-100">{{ $product->name }}</div>
                                            <div class="md:col-span-4 text-muted">{{ $product->description ?: '—' }}</div>
                                            <div class="md:col-span-1 text-muted">{{ $product->unit ?: '—' }}</div>
                                            <div class="md:col-span-1 text-right text-slate-200">${{ number_format($product->default_unit_price, 2) }}</div>
                                            <div class="md:col-span-1 text-right text-muted">{{ $product->plan_items_count }} plan{{ $product->plan_items_count == 1 ? '' : 's' }}</div>
                                            <div class="md:col-span-1"><span class="badge {{ $product->is_active ? 'badge-good' : 'badge-gray' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></div>
                                            <div class="md:col-span-1 flex justify-end gap-3">
                                                <button type="button" @click="editing = true" class="text-xs accent-ink hover:underline">Edit</button>
                                                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete {{ addslashes($product->name) }}? Plan line items keep their description and price.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-slate-500 hover:text-rose-400">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                        <form x-show="editing" x-cloak method="POST" action="{{ route('products.update', $product) }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                            @csrf @method('PUT')
                                            <div class="md:col-span-3"><input type="text" name="name" value="{{ $product->name }}" class="field text-sm" required></div>
                                            <div class="md:col-span-4"><input type="text" name="description" value="{{ $product->description }}" class="field text-sm" placeholder="Default description"></div>
                                            <div class="md:col-span-1"><input type="text" name="unit" value="{{ $product->unit }}" class="field text-sm" placeholder="Unit"></div>
                                            <div class="md:col-span-1"><input type="number" name="default_unit_price" value="{{ $product->default_unit_price }}" step="0.01" min="0" class="field text-sm text-right"></div>
                                            <div class="md:col-span-1"><input type="number" name="sort_order" value="{{ $product->sort_order }}" min="0" class="field text-sm text-right" title="Sort order"></div>
                                            <div class="md:col-span-1 text-sm text-muted">
                                                <label class="inline-flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} class="rounded" style="background: var(--surface-2); border-color: var(--border-strong);"> Active</label>
                                            </div>
                                            <div class="md:col-span-1 flex justify-end gap-2">
                                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                                <button type="button" @click="editing = false" class="btn btn-ghost btn-sm">Cancel</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-muted">No products yet. Add the services you bill (hosting, backups, maintenance…).</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
