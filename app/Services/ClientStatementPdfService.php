<?php

namespace App\Services;

use App\Models\BrandingSetting;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientStatementPdfService
{
    public function generate(Client $client, string $from, string $to): \Barryvdh\DomPDF\PDF
    {
        $invoices = $client->invoices()
            ->with('payments')
            ->whereIn('status', ['sent', 'paid', 'overdue'])
            ->whereBetween('issue_date', [$from, $to])
            ->orderBy('issue_date')
            ->get();

        $totalInvoiced = $invoices->sum('total');
        $totalPaid = $invoices->sum('amount_paid');
        $totalOutstanding = $invoices->sum(fn ($i) => $i->balance_due);

        $branding = BrandingSetting::getSettings();

        return Pdf::loadView('pdf.client-statement', [
            'client' => $client,
            'invoices' => $invoices,
            'from' => $from,
            'to' => $to,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'branding' => $branding,
        ])->setPaper('a4');
    }

    public function stream(Client $client, string $from, string $to): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generate($client, $from, $to);
        return $pdf->stream($this->filename($client, $from, $to));
    }

    public function download(Client $client, string $from, string $to): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generate($client, $from, $to);
        return $pdf->download($this->filename($client, $from, $to));
    }

    private function filename(Client $client, string $from, string $to): string
    {
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $client->company_name));
        return "Statement-{$name}-{$from}-to-{$to}.pdf";
    }
}
