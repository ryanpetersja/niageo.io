<?php

namespace App\Http\Resources\Api;

use App\Services\BillingPlanService;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingPlanResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        $plans = app(BillingPlanService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'company_name' => $this->client->company_name,
            ]),
            'billing_cycle' => $this->billing_cycle,
            'cycle_label' => $this->cycle_label,
            'status' => $this->status,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'next_period_start' => $this->next_period_start?->toDateString(),
            'next_issue_date' => $plans->nextIssueDate($this->resource)?->toDateString(),
            'is_due' => $plans->isDue($this->resource),
            'issue_days_before' => (int) $this->issue_days_before,
            'due_days' => (int) $this->due_days,
            'tax_rate' => (float) $this->tax_rate,
            'period_subtotal' => $this->relationLoaded('items') ? round($this->period_subtotal, 2) : null,
            'period_total' => $this->relationLoaded('items') ? $this->period_total : null,
            'monthly_value' => $this->relationLoaded('items') ? $this->monthly_value : null,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => round((float) $item->quantity * (float) $item->unit_price, 2),
            ])->values()->all()),
            // Set by the controller on detail responses.
            'upcoming' => $this->when(isset($this->upcoming), fn () => array_map(fn ($period) => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'issue_date' => $period['issue_date']->toDateString(),
                'due_date' => $period['due_date']->toDateString(),
                'label' => $period['label'],
            ], $this->upcoming)),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
        ];
    }
}
