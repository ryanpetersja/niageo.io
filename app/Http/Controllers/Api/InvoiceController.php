<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Invoicing for the mobile app. Mirrors the web controller's rules (draft-only editing,
 * status transitions and payments via InvoiceService) with JSON in and out.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoices,
        private InvoicePdfService $pdf,
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|string|max:80',
            'client_id' => 'nullable|integer|exists:clients,id',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Invoice::query()->with('client');

        if (! empty($validated['status'])) {
            $statuses = array_values(array_filter(array_map('trim', explode(',', $validated['status']))));
            // "Overdue" also means sent invoices past their due date that the scheduler has not flagged yet.
            $query->where(function ($q) use ($statuses) {
                $q->whereIn('status', $statuses);
                if (in_array('overdue', $statuses, true)) {
                    $q->orWhere(fn ($q) => $q->where('status', 'sent')->whereDate('due_date', '<', today()));
                }
            });
        }

        if (! empty($validated['client_id'])) {
            $query->where('client_id', $validated['client_id']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 25))
            ->withQueryString();

        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice)
    {
        return new InvoiceResource($this->loadFull($invoice));
    }

    public function store(Request $request)
    {
        $validated = $this->validateInvoice($request);

        $invoice = DB::transaction(function () use ($validated) {
            $invoice = $this->invoices->create($this->attributes($validated));
            $this->addLineItems($invoice, $validated['line_items']);

            return $invoice;
        });

        return (new InvoiceResource($this->loadFull($invoice->fresh())))->response()->setStatusCode(201);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return $this->error('Only draft invoices can be edited.');
        }

        $validated = $this->validateInvoice($request);

        DB::transaction(function () use ($invoice, $validated) {
            $this->invoices->update($invoice, $this->attributes($validated));
            $invoice->lineItems()->delete();
            $this->addLineItems($invoice, $validated['line_items']);
        });

        return new InvoiceResource($this->loadFull($invoice->fresh()));
    }

    public function destroy(Invoice $invoice)
    {
        if (! in_array($invoice->status, ['draft', 'cancelled'], true)) {
            return $this->error('Only draft or cancelled invoices can be deleted.');
        }

        $invoice->delete();

        return response()->noContent();
    }

    public function transition(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->invoices->transition($invoice, $validated['status'], $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        return new InvoiceResource($this->loadFull($invoice->fresh()));
    }

    public function duplicate(Invoice $invoice)
    {
        $copy = $this->invoices->duplicate($invoice);

        return (new InvoiceResource($this->loadFull($copy)))->response()->setStatusCode(201);
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->invoices->recordPayment($invoice, $validated);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        return (new InvoiceResource($this->loadFull($invoice->fresh())))->response()->setStatusCode(201);
    }

    public function destroyPayment(Payment $payment)
    {
        $invoice = $payment->invoice;

        try {
            $this->invoices->deletePayment($payment);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        return new InvoiceResource($this->loadFull($invoice->fresh()));
    }

    public function pdf(Invoice $invoice)
    {
        return $this->pdf->download($invoice);
    }

    protected function loadFull(Invoice $invoice): Invoice
    {
        return $invoice->load(['client.contacts', 'lineItems', 'payments.recorder', 'statusHistory.changedBy']);
    }

    protected function validateInvoice(Request $request): array
    {
        return $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string|max:255',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit_price' => 'required|numeric|min:0',
        ]);
    }

    protected function attributes(array $validated): array
    {
        return [
            'client_id' => $validated['client_id'],
            'title' => $validated['title'] ?? null,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ];
    }

    protected function addLineItems(Invoice $invoice, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $this->invoices->addLineItem($invoice, [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $index,
            ]);
        }
    }

    protected function error(string $message, int $status = 422)
    {
        return response()->json(['message' => $message], $status);
    }
}
