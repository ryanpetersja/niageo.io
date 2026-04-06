<?php

namespace App\Services;

use App\Models\BrandingSetting;
use App\Models\Report;
use Illuminate\Support\Facades\Mail;

class ReportMailService
{
    public function __construct(
        private ReportPdfService $reportPdfService,
        private InvoicePdfService $invoicePdfService,
    ) {}

    public function send(Report $report, string $toEmail): void
    {
        $report->load(['client', 'invoice']);
        $branding = BrandingSetting::getSettings();

        $subject = "Development Report: {$report->title} — {$report->report_number}";

        $body = "Dear {$report->client->company_name},\n\n";
        $body .= "Please find attached your development report for the period ";
        $body .= $report->date_from->format('M d, Y') . " to " . $report->date_to->format('M d, Y') . ".\n\n";

        if ($report->notes) {
            $body .= "{$report->notes}\n\n";
        }

        if ($report->invoice) {
            $body .= "An associated invoice ({$report->invoice->invoice_number}) is also attached for your reference.\n\n";
        }

        $body .= "Best regards,\n";
        $body .= $branding->company_name . "\n";
        if ($branding->phone) {
            $body .= $branding->phone . "\n";
        }
        if ($branding->email) {
            $body .= $branding->email . "\n";
        }
        if ($branding->website) {
            $body .= $branding->website . "\n";
        }

        Mail::raw($body, function ($message) use ($report, $toEmail, $subject, $branding) {
            $message->to($toEmail)
                ->subject($subject);

            if ($branding->email) {
                $message->from($branding->email, $branding->company_name);
            }

            // Attach report PDF
            $reportPdf = $this->reportPdfService->generate($report);
            $message->attachData(
                $reportPdf->output(),
                $report->report_number . '.pdf',
                ['mime' => 'application/pdf']
            );

            // Attach invoice PDF if linked
            if ($report->invoice) {
                $invoicePdf = $this->invoicePdfService->generate($report->invoice);
                $message->attachData(
                    $invoicePdf->output(),
                    $report->invoice->invoice_number . '.pdf',
                    ['mime' => 'application/pdf']
                );
            }
        });
    }
}
