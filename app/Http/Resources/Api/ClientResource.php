<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        // Lists are loaded with withSum() aggregates to avoid one query per client; detail uses the accessor.
        $attributes = $this->resource->getAttributes();
        $outstanding = array_key_exists('open_total', $attributes)
            ? max(0.0, (float) $attributes['open_total'] - (float) ($attributes['open_paid'] ?? 0))
            : (float) $this->outstanding_balance;

        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'billing_terms' => $this->billing_terms,
            'billing_terms_label' => $this->billing_terms_label,
            'due_days' => $this->due_days,
            'billing_email' => $this->billing_email,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'outstanding_balance' => round($outstanding, 2),
            'contacts' => ClientContactResource::collection($this->whenLoaded('contacts')),
            'pricing_presets' => PricingPresetResource::collection($this->whenLoaded('pricingPresets')),
            'recent_invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
