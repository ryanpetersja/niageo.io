<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            BrandingSettingsSeeder::class,
            ClientSeeder::class,
            PricingPresetSeeder::class,
            InvoiceSeeder::class,
            ClientRepositorySeeder::class,
            ClientServerSeeder::class,
            ReportSeeder::class,
            MonitoredEndpointSeeder::class,
            SubscriptionBillSeeder::class,
        ]);
    }
}
