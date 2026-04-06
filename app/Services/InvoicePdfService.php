<?php

namespace App\Services;

use App\Models\BrandingSetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function generate(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['client', 'lineItems', 'payments']);
        $branding = BrandingSetting::getSettings();

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'branding' => $branding,
        ])->setPaper('a4');
    }

    public function download(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generate($invoice);
        $filename = $invoice->invoice_number . '.pdf';

        return $pdf->download($filename);
    }

    public function stream(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generate($invoice);
        $filename = $invoice->invoice_number . '.pdf';

        return $pdf->stream($filename);
    }
}
