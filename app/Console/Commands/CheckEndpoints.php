<?php

namespace App\Console\Commands;

use App\Services\UptimeService;
use Illuminate\Console\Command;

class CheckEndpoints extends Command
{
    protected $signature = 'uptime:check';

    protected $description = 'Check all monitored endpoints that are due for a check';

    public function handle(UptimeService $uptimeService): int
    {
        $count = $uptimeService->checkAllDue();

        $this->info("Checked {$count} endpoint(s).");

        return self::SUCCESS;
    }
}
