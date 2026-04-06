<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\InvoiceStatusHistory;
use App\Models\Payment;
use App\Models\PricingPreset;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    private const VALID_TRANSITIONS = [
        'draft' => ['sent', 'cancelled'],
        'sent' => ['draft', 'paid', 'overdue', 'cancelled'],
        'overdue' => ['paid', 'cancelled'],
    ];

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $data['invoice_number'] = $data['invoice_number'] ?? Invoice::generateInvoiceNumber();
            $data['created_by'] = $data['created_by'] ?? auth()->id();
            $data['status'] = 'draft';

            $invoice = Invoice::create($data);

            InvoiceStatusHistory::create([
                'invoice_id' => $invoice->id,
                'from_status' => null,
                'to_status' => 'draft',
                'changed_by' => auth()->id(),
                'notes' => 'Invoice created',
            ]);

            return $invoice;
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \RuntimeException('Only draft invoices can be edited.');
        }

        $invoice->update($data);
        return $invoice->fresh();
    }

    public function transition(Invoice $invoice, string $toStatus, ?string $notes = null): Invoice
    {
        $fromStatus = $invoice->status;
        $allowed = self::VALID_TRANSITIONS[$fromStatus] ?? [];

        if (!in_array($toStatus, $allowed)) {
            throw new \RuntimeException("Cannot transition from '{$fromStatus}' to '{$toStatus}'.");
        }

        return DB::transaction(function () use ($invoice, $fromStatus, $toStatus, $notes) {
            $invoice->update(['status' => $toStatus]);

            InvoiceStatusHistory::create([
                'invoice_id' => $invoice->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => auth()->id(),
                'notes' => $notes,
            ]);

            return $invoice->fresh();
        });
    }

    public function addLineItem(Invoice $invoice, array $data): InvoiceLineItem
    {
        if ($invoice->status !== 'draft') {
            throw new \RuntimeException('Line items can only be added to draft invoices.');
        }

        $data['invoice_id'] = $invoice->id;
        $data['sort_order'] = $data['sort_order'] ?? ($invoice->lineItems()->max('sort_order') + 1);

        return InvoiceLineItem::create($data);
    }

    public function updateLineItem(InvoiceLineItem $item, array $data): InvoiceLineItem
    {
        if ($item->invoice->status !== 'draft') {
            throw new \RuntimeException('Line items can only be edited on draft invoices.');
        }

        $item->update($data);
        return $item->fresh();
    }

    public function removeLineItem(InvoiceLineItem $item): void
    {
        if ($item->invoice->status !== 'draft') {
            throw new \RuntimeException('Line items can only be removed from draft invoices.');
        }

        $item->delete();
    }

    public function applyPreset(Invoice $invoice, PricingPreset $preset): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \RuntimeException('Presets can only be applied to draft invoices.');
        }

        return DB::transaction(function () use ($invoice, $preset) {
            $invoice->lineItems()->delete();

            foreach ($preset->items as $presetItem) {
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $presetItem->description,
                    'quantity' => $presetItem->quantity,
                    'unit_price' => $presetItem->unit_price,
                    'total' => $presetItem->quantity * $presetItem->unit_price,
                    'sort_order' => $presetItem->sort_order,
                ]);
            }

            $invoice->update(['pricing_preset_id' => $preset->id]);
            $invoice->recalculateTotals();

            return $invoice->fresh(['lineItems']);
        });
    }

    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        if (in_array($invoice->status, ['draft', 'cancelled'])) {
            throw new \RuntimeException('Cannot record payments on draft or cancelled invoices.');
        }

        $data['invoice_id'] = $invoice->id;
        $data['recorded_by'] = $data['recorded_by'] ?? auth()->id();

        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create($data);

            $invoice->refresh();
            if ($invoice->is_fully_paid && $invoice->status !== 'paid') {
                $this->transition($invoice, 'paid', 'Automatically marked as paid - full payment received');
            }

            return $payment;
        });
    }

    public function deletePayment(Payment $payment): void
    {
        $invoice = $payment->invoice;

        if ($invoice->status === 'cancelled') {
            throw new \RuntimeException('Cannot modify payments on cancelled invoices.');
        }

        DB::transaction(function () use ($payment, $invoice) {
            $payment->delete();

            $invoice->refresh();
            if ($invoice->status === 'paid' && !$invoice->is_fully_paid) {
                $newStatus = $invoice->is_overdue ? 'overdue' : 'sent';
                $invoice->update(['status' => $newStatus]);

                InvoiceStatusHistory::create([
                    'invoice_id' => $invoice->id,
                    'from_status' => 'paid',
                    'to_status' => $newStatus,
                    'changed_by' => auth()->id(),
                    'notes' => 'Payment deleted - status reverted',
                ]);
            }
        });
    }

    public function duplicate(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $newInvoice = $this->create([
                'client_id' => $invoice->client_id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays($invoice->client->due_days)->toDateString(),
                'tax_rate' => $invoice->tax_rate,
                'notes' => $invoice->notes,
                'internal_notes' => $invoice->internal_notes,
                'pricing_preset_id' => $invoice->pricing_preset_id,
            ]);

            foreach ($invoice->lineItems as $item) {
                InvoiceLineItem::create([
                    'invoice_id' => $newInvoice->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $newInvoice->recalculateTotals();
            return $newInvoice->fresh(['lineItems']);
        });
    }

    public function getValidTransitions(Invoice $invoice): array
    {
        return self::VALID_TRANSITIONS[$invoice->status] ?? [];
    }
}
