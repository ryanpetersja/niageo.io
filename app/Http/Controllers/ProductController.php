<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::ordered()->withCount('planItems')->get();

        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);
        $product = Product::create($validated);

        return redirect()->route('products.index')->with('success', "Product \"{$product->name}\" added.");
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product);
        $product->update($validated);

        return redirect()->route('products.index')->with('success', "Product \"{$product->name}\" updated.");
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', "Product \"{$product->name}\" deleted. Plan line items keep their own description and price.");
    }

    protected function validateProduct(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($product?->id)],
            'description' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'default_unit_price' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['default_unit_price'] = $validated['default_unit_price'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
