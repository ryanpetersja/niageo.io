<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function store(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->invoiceService->recordPayment($invoice, $validated);
            return redirect()->route('invoices.show', $invoice)->with('success', 'Payment recorded successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', $e->getMessage());
        }
    }

    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;

        try {
            $this->invoiceService->deletePayment($payment);
            return redirect()->route('invoices.show', $invoice)->with('success', 'Payment deleted successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', $e->getMessage());
        }
    }
}
