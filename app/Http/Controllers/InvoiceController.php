<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PricingPreset;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use ZipArchive;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private InvoicePdfService $pdfService,
    ) {}

    public function index(Request $request)
    {
        $query = Invoice::with('client');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Compute summary totals from the filtered query (before pagination)
        $summary = (clone $query)->selectRaw('
            COUNT(*) as total_count,
            SUM(total) as total_amount,
            SUM(amount_paid) as total_paid,
            SUM(total - amount_paid) as total_outstanding,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_count,
            SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
            SUM(CASE WHEN status = "overdue" THEN total - amount_paid ELSE 0 END) as overdue_amount
        ')->first();

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('invoices.index', compact('invoices', 'clients', 'summary'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $selectedClient = $request->input('client_id') ? Client::find($request->input('client_id')) : null;

        return view('invoices.create', compact('clients', 'selectedClient'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
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

        $invoice = $this->invoiceService->create([
            'client_id' => $validated['client_id'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);

        foreach ($validated['line_items'] as $index => $item) {
            $this->invoiceService->addLineItem($invoice, [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client.contacts', 'lineItems', 'payments.recorder', 'statusHistory.changedBy', 'creator']);
        $validTransitions = $this->invoiceService->getValidTransitions($invoice);

        return view('invoices.show', compact('invoice', 'validTransitions'));
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Only draft invoices can be edited.');
        }

        $invoice->load('lineItems');
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('invoices.edit', compact('invoice', 'clients'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Only draft invoices can be edited.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
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

        $this->invoiceService->update($invoice, [
            'client_id' => $validated['client_id'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);

        // Replace line items
        $invoice->lineItems()->delete();
        foreach ($validated['line_items'] as $index => $item) {
            $this->invoiceService->addLineItem($invoice, [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if (!in_array($invoice->status, ['draft', 'cancelled'])) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Only draft or cancelled invoices can be deleted.');
        }

        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function transition(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->invoiceService->transition($invoice, $validated['status'], $validated['notes'] ?? null);
            return redirect()->route('invoices.show', $invoice)->with('success', "Invoice marked as {$validated['status']}.");
        } catch (\RuntimeException $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', $e->getMessage());
        }
    }

    public function duplicate(Invoice $invoice)
    {
        $newInvoice = $this->invoiceService->duplicate($invoice);
        return redirect()->route('invoices.edit', $newInvoice)->with('success', 'Invoice duplicated as draft.');
    }

    public function applyPreset(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'pricing_preset_id' => 'required|exists:pricing_presets,id',
        ]);

        try {
            $preset = PricingPreset::findOrFail($validated['pricing_preset_id']);
            $this->invoiceService->applyPreset($invoice, $preset);
            return redirect()->route('invoices.edit', $invoice)->with('success', 'Preset applied successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', $e->getMessage());
        }
    }

    public function pdf(Invoice $invoice)
    {
        return $this->pdfService->stream($invoice);
    }

    public function downloadPdf(Invoice $invoice)
    {
        return $this->pdfService->download($invoice);
    }

    public function downloadAll(Request $request)
    {
        $query = Invoice::with('client');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        if ($invoices->isEmpty()) {
            return redirect()->route('invoices.index')->with('error', 'No invoices match the current filters.');
        }

        $zipPath = storage_path('app/temp/invoices-' . now()->format('Y-m-d-His') . '.zip');
        $tempDir = dirname($zipPath);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($invoices as $invoice) {
            $pdf = $this->pdfService->generate($invoice);
            $zip->addFromString($invoice->invoice_number . '.pdf', $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
