<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Starter catalogue for billing plans. Prices are set per client on each plan,
 * so the defaults here are only suggestions.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Website hosting', 'description' => 'Managed web hosting, server monitoring and patching', 'unit' => 'month'],
            ['name' => 'Database backups – daily', 'description' => 'Daily automated database backups with off-site storage', 'unit' => 'month'],
            ['name' => 'Database backups – weekly', 'description' => 'Weekly automated database backups with off-site storage', 'unit' => 'month'],
            ['name' => 'Database backups – monthly', 'description' => 'Monthly database backup with off-site storage', 'unit' => 'month'],
            ['name' => 'Maintenance & security updates', 'description' => 'Framework, plugin and dependency updates with security patching', 'unit' => 'month'],
            ['name' => 'Uptime monitoring & support', 'description' => 'Uptime monitoring, incident response and support hours', 'unit' => 'month'],
            ['name' => 'SSL certificate management', 'description' => 'SSL certificate provisioning and renewal', 'unit' => 'month'],
            ['name' => 'Email hosting', 'description' => 'Business email hosting and mailbox management', 'unit' => 'month'],
        ];

        foreach ($products as $index => $product) {
            Product::firstOrCreate(['name' => $product['name']], $product + ['sort_order' => $index]);
        }
    }
}
