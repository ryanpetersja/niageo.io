<?php

namespace Database\Seeders;

use App\Models\SubscriptionBill;
use Illuminate\Database\Seeder;

class SubscriptionBillSeeder extends Seeder
{
    public function run(): void
    {
        $bills = [
            [
                'service_name' => 'DigitalOcean',
                'category' => 'hosting',
                'description' => '3 Droplets + Managed MySQL',
                'amount' => 84.00,
                'billing_cycle' => 'monthly',
                'next_due_date' => '2026-03-15',
                'status' => 'upcoming',
                'is_active' => true,
                'auto_renew' => true,
                'url' => 'https://cloud.digitalocean.com/billing',
            ],
            [
                'service_name' => 'SendGrid',
                'category' => 'email',
                'description' => 'Pro 100K plan',
                'amount' => 89.95,
                'billing_cycle' => 'monthly',
                'next_due_date' => '2026-02-23',
                'status' => 'upcoming',
                'is_active' => true,
                'auto_renew' => true,
                'url' => 'https://app.sendgrid.com/settings/billing',
            ],
            [
                'service_name' => 'Laravel Forge',
                'category' => 'devops',
                'description' => 'Business plan',
                'amount' => 39.00,
                'billing_cycle' => 'monthly',
                'next_due_date' => '2026-03-08',
                'status' => 'upcoming',
                'is_active' => true,
                'auto_renew' => true,
                'url' => 'https://forge.laravel.com/billing',
            ],
            [
                'service_name' => 'Google Workspace',
                'category' => 'workspace',
                'description' => 'Business Standard - 5 users',
                'amount' => 72.00,
                'billing_cycle' => 'monthly',
                'next_due_date' => '2026-03-02',
                'status' => 'upcoming',
                'is_active' => true,
                'auto_renew' => true,
                'url' => 'https://admin.google.com/ac/billing',
            ],
            [
                'service_name' => 'GitHub',
                'category' => 'devops',
                'description' => 'Team plan - 4 seats',
                'amount' => 48.00,
                'billing_cycle' => 'annual',
                'next_due_date' => '2026-06-18',
                'status' => 'upcoming',
                'is_active' => true,
                'auto_renew' => true,
                'url' => 'https://github.com/settings/billing',
            ],
            [
                'service_name' => 'Cloudflare',
                'category' => 'hosting',
                'description' => 'Pro plan - 2 domains',
                'amount' => 40.00,
                'billing_cycle' => 'monthly',
                'next_due_date' => '2026-02-17',
                'status' => 'upcoming',
                'is_active' => true,
                'auto_renew' => true,
                'url' => 'https://dash.cloudflare.com',
            ],
        ];

        foreach ($bills as $bill) {
            SubscriptionBill::updateOrCreate(
                ['service_name' => $bill['service_name']],
                $bill
            );
        }
    }
}
