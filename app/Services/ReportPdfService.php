<?php

namespace App\Services;

use App\Models\BrandingSetting;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportPdfService
{
    public function generate(Report $report): \Barryvdh\DomPDF\PDF
    {
        $report->load(['client', 'invoice.lineItems', 'invoice.client']);
        $branding = BrandingSetting::getSettings();

        return Pdf::loadView('pdf.report', [
            'report' => $report,
            'branding' => $branding,
        ])->setPaper('a4');
    }

    public function download(Report $report): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generate($report);
        $filename = $report->report_number . '.pdf';

        return $pdf->download($filename);
    }

    public function stream(Report $report): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = $this->generate($report);
        $filename = $report->report_number . '.pdf';

        return $pdf->stream($filename);
    }
}
