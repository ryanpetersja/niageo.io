<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\MonitoredEndpoint;
use Illuminate\Database\Seeder;

class MonitoredEndpointSeeder extends Seeder
{
    public function run(): void
    {
        $snowJm = Client::where('company_name', 'Snow JM')->first();
        $campusElite = Client::where('company_name', 'Campus Elite')->first();
        $ltnExpress = Client::where('company_name', 'LTN Express')->first();
        $niageo = Client::where('company_name', 'Niageo Technologies')->first();

        $endpoints = [
            [
                'client_id' => $snowJm->id,
                'name' => 'Snow Landing',
                'url' => 'https://snowjm.com/',
                'check_interval_minutes' => 5,
                'timeout_seconds' => 10,
                'degraded_threshold_ms' => 2000,
            ],
            [
                'client_id' => $campusElite->id,
                'name' => 'Opportunity Network',
                'url' => 'https://ceopportunitynetwork.com/',
                'check_interval_minutes' => 5,
                'timeout_seconds' => 10,
                'degraded_threshold_ms' => 2000,
            ],
            [
                'client_id' => $snowJm->id,
                'name' => 'Application Portal',
                'url' => 'https://apply.snowjm.com/login',
                'check_interval_minutes' => 5,
                'timeout_seconds' => 10,
                'degraded_threshold_ms' => 2000,
            ],
            [
                'client_id' => $ltnExpress->id,
                'name' => 'LTN Express',
                'url' => 'https://ltnexpress.com/',
                'check_interval_minutes' => 5,
                'timeout_seconds' => 10,
                'degraded_threshold_ms' => 2000,
            ],
            [
                'client_id' => $ltnExpress->id,
                'name' => 'LTN Express Dashboard',
                'url' => 'https://ltnexpress-dashboard.com/orders',
                'check_interval_minutes' => 5,
                'timeout_seconds' => 10,
                'degraded_threshold_ms' => 2000,
            ],
            [
                'client_id' => $niageo->id,
                'name' => 'Niageo Landing',
                'url' => 'https://niageo.com',
                'check_interval_minutes' => 5,
                'timeout_seconds' => 10,
                'degraded_threshold_ms' => 2000,
            ],
        ];

        foreach ($endpoints as $endpoint) {
            MonitoredEndpoint::updateOrCreate(
                ['url' => $endpoint['url']],
                $endpoint
            );
        }
    }
}
