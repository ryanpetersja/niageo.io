<?php

namespace App\Console\Commands;

use App\Services\BillingPlanService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'billing:generate {--date= : Generate as if today were this date (Y-m-d)}';

    protected $description = 'Create draft invoices for every billing plan whose next period is due';

    public function handle(BillingPlanService $service): int
    {
        $today = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : null;
        $invoices = $service->generateDue($today);

        foreach ($invoices as $invoice) {
            $this->line("  {$invoice->invoice_number} — {$invoice->client->company_name} — {$invoice->title}");
        }

        $this->info('Generated ' . count($invoices) . ' recurring invoice(s).');

        return Command::SUCCESS;
    }
}
