<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'recorded_by' => $this->whenLoaded('recorder', fn () => $this->recorder?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
