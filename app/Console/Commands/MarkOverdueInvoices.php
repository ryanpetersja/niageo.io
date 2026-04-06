<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceStatusHistory;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark sent invoices past their due date as overdue';

    public function handle(): int
    {
        $invoices = Invoice::where('status', 'sent')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            $invoice->status = 'overdue';
            $invoice->saveQuietly();

            InvoiceStatusHistory::create([
                'invoice_id' => $invoice->id,
                'from_status' => 'sent',
                'to_status' => 'overdue',
                'notes' => 'Automatically marked overdue by system.',
            ]);

            $count++;
        }

        $this->info("Marked {$count} invoice(s) as overdue.");

        return Command::SUCCESS;
    }
}
