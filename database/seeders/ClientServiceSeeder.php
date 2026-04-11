<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientService;
use Illuminate\Database\Seeder;

class ClientServiceSeeder extends Seeder
{
    public function run(): void
    {
        $campusElite = Client::where('company_name', 'Campus Elite')->first();
        $snowJm = Client::where('company_name', 'Snow JM')->first();
        $ltnExpress = Client::where('company_name', 'LTN Express')->first();
        $niageo = Client::where('company_name', 'Niageo Technologies')->first();

        if ($campusElite) {
            ClientService::updateOrCreate(
                ['client_id' => $campusElite->id, 'service_type' => 'hosting'],
                ['display_name' => 'Web Hosting', 'is_active' => true, 'sort_order' => 0]
            );
            ClientService::updateOrCreate(
                ['client_id' => $campusElite->id, 'service_type' => 'email'],
                ['display_name' => 'Email Service', 'is_active' => true, 'sort_order' => 1]
            );
            ClientService::updateOrCreate(
                ['client_id' => $campusElite->id, 'service_type' => 'backups'],
                ['display_name' => 'Daily Backups', 'config' => ['frequency' => 'daily'], 'is_active' => true, 'sort_order' => 2]
            );
        }

        if ($snowJm) {
            ClientService::updateOrCreate(
                ['client_id' => $snowJm->id, 'service_type' => 'hosting'],
                ['display_name' => 'Web Hosting', 'is_active' => true, 'sort_order' => 0]
            );
            ClientService::updateOrCreate(
                ['client_id' => $snowJm->id, 'service_type' => 'backups'],
                ['display_name' => 'Weekly Backups', 'config' => ['frequency' => 'weekly'], 'is_active' => true, 'sort_order' => 1]
            );
        }

        if ($ltnExpress) {
            ClientService::updateOrCreate(
                ['client_id' => $ltnExpress->id, 'service_type' => 'hosting'],
                ['display_name' => 'App Hosting', 'is_active' => true, 'sort_order' => 0]
            );
            ClientService::updateOrCreate(
                ['client_id' => $ltnExpress->id, 'service_type' => 'backups'],
                ['display_name' => 'Daily Backups', 'config' => ['frequency' => 'daily'], 'is_active' => true, 'sort_order' => 1]
            );
            ClientService::updateOrCreate(
                ['client_id' => $ltnExpress->id, 'service_type' => 'custom'],
                ['display_name' => 'SSL Certificate Management', 'is_active' => true, 'sort_order' => 2]
            );
        }

        if ($niageo) {
            ClientService::updateOrCreate(
                ['client_id' => $niageo->id, 'service_type' => 'hosting'],
                ['display_name' => 'Web Hosting', 'is_active' => true, 'sort_order' => 0]
            );
            ClientService::updateOrCreate(
                ['client_id' => $niageo->id, 'service_type' => 'email'],
                ['display_name' => 'Email Service', 'is_active' => true, 'sort_order' => 1]
            );
            ClientService::updateOrCreate(
                ['client_id' => $niageo->id, 'service_type' => 'backups'],
                ['display_name' => 'Monthly Backups', 'config' => ['frequency' => 'monthly'], 'is_active' => true, 'sort_order' => 2]
            );
        }
    }
}
