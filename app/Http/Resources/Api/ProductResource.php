<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'unit' => $this->unit,
            'default_unit_price' => (float) $this->default_unit_price,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
