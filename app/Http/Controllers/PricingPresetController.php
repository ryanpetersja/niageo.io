<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PricingPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingPresetController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $preset = DB::transaction(function () use ($client, $validated) {
            $preset = $client->pricingPresets()->create([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $preset->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'sort_order' => $index,
                ]);
            }

            return $preset->load('items');
        });

        return response()->json(['success' => true, 'preset' => $preset]);
    }

    public function update(Request $request, Client $client, PricingPreset $preset)
    {
        abort_unless($preset->client_id === $client->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($preset, $validated) {
            $preset->update([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $preset->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                $preset->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'sort_order' => $index,
                ]);
            }
        });

        return response()->json(['success' => true, 'preset' => $preset->load('items')]);
    }

    public function destroy(Client $client, PricingPreset $preset)
    {
        abort_unless($preset->client_id === $client->id, 404);

        $preset->delete();

        return response()->json(['success' => true]);
    }
}
