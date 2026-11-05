<?php

namespace App\Http\Resources\Api;

use App\Services\InvoiceService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One shape for lists and detail: relations appear only when loaded (lists load the client;
 * detail also loads line items, payments and history).
 */
class InvoiceResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'title' => $this->title,
            'status' => $this->status,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'company_name' => $this->client->company_name,
                'billing_email' => $this->client->billing_email,
                'billing_terms' => $this->client->billing_terms,
                'due_days' => $this->client->due_days,
                'contacts' => $this->client->relationLoaded('contacts')
                    ? ClientContactResource::collection($this->client->contacts)->resolve()
                    : null,
            ]),
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'days_until_due' => $this->due_date ? (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false) : null,
            'subtotal' => (float) $this->subtotal,
            'tax_rate' => (float) $this->tax_rate,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'balance_due' => (float) $this->balance_due,
            'is_overdue' => (bool) $this->is_overdue,
            'is_recurring' => (bool) $this->is_recurring,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'line_items' => InvoiceLineItemResource::collection($this->whenLoaded('lineItems')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'status_history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($entry) => [
                'from_status' => $entry->from_status,
                'to_status' => $entry->to_status,
                'notes' => $entry->notes,
                'changed_by' => $entry->relationLoaded('changedBy') ? $entry->changedBy?->name : null,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])->values()->all()),
            'valid_transitions' => app(InvoiceService::class)->getValidTransitions($this->resource),
            'can_edit' => $this->status === 'draft',
            'can_delete' => in_array($this->status, ['draft', 'cancelled'], true),
            'can_record_payment' => ! in_array($this->status, ['draft', 'cancelled'], true),
            'pdf_url' => route('api.invoices.pdf', $this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
