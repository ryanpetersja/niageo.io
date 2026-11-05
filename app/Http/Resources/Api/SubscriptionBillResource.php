<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionBillResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        $monthly = match ($this->billing_cycle) {
            'quarterly' => (float) $this->amount / 3,
            'annual' => (float) $this->amount / 12,
            default => (float) $this->amount,
        };

        return [
            'id' => $this->id,
            'service_name' => $this->service_name,
            'category' => $this->category,
            'category_label' => $this->category_label,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'billing_cycle' => $this->billing_cycle,
            'cycle_label' => $this->cycle_label,
            'monthly_equivalent' => round($monthly, 2),
            'next_due_date' => $this->next_due_date?->toDateString(),
            'days_until_due' => $this->next_due_date
                ? (int) now()->startOfDay()->diffInDays($this->next_due_date->copy()->startOfDay(), false)
                : null,
            'status' => $this->status,
            'last_paid_at' => $this->last_paid_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'auto_renew' => (bool) $this->auto_renew,
            'url' => $this->url,
            'notes' => $this->notes,
        ];
    }
}
