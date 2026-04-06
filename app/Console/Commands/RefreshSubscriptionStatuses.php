<?php

namespace App\Console\Commands;

use App\Models\SubscriptionBill;
use Illuminate\Console\Command;

class RefreshSubscriptionStatuses extends Command
{
    protected $signature = 'subscriptions:refresh';

    protected $description = 'Refresh subscription bill statuses (mark overdue and due soon)';

    public function handle(): int
    {
        $bills = SubscriptionBill::active()->unpaid()->get();

        $overdueCount = 0;
        $dueSoonCount = 0;

        foreach ($bills as $bill) {
            $oldStatus = $bill->status;
            $bill->refreshStatus();

            if ($bill->status === 'overdue' && $oldStatus !== 'overdue') {
                $overdueCount++;
            }
            if ($bill->status === 'due_soon' && $oldStatus !== 'due_soon') {
                $dueSoonCount++;
            }
        }

        $this->info("Refreshed {$bills->count()} subscription(s). {$overdueCount} newly overdue, {$dueSoonCount} newly due soon.");

        return Command::SUCCESS;
    }
}
