<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class PricingPresetResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        $items = $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->values()->all(), []);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => (bool) $this->is_active,
            'total' => $this->relationLoaded('items')
                ? round((float) $this->items->sum(fn ($item) => $item->quantity * $item->unit_price), 2)
                : 0.0,
            'items' => $items,
        ];
    }
}
