<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        // Sunset Media Group (inactive, from original data)
        $sunsetMedia = Client::updateOrCreate(
            ['company_name' => 'Sunset Media Group'],
            [
                'billing_terms' => 'net_60',
                'billing_email' => 'billing@sunsetmedia.co',
                'notes' => "Inactive \u2014 project completed.",
                'is_active' => false,
            ]
        );
        ClientContact::updateOrCreate(
            ['client_id' => $sunsetMedia->id, 'email' => 'amy@sunsetmedia.co'],
            ['name' => 'Amy Rivera', 'is_primary' => true]
        );

        // Campus Elite
        Client::updateOrCreate(
            ['company_name' => 'Campus Elite'],
            [
                'billing_terms' => 'net_60',
                'billing_email' => 'ryanpetersja@gmail.com',
                'notes' => 'Hello',
                'is_active' => true,
            ]
        );

        // Snow JM
        Client::updateOrCreate(
            ['company_name' => 'Snow JM'],
            [
                'billing_terms' => 'due_on_receipt',
                'billing_email' => 'students.now@yahoo.com',
                'notes' => null,
                'is_active' => true,
            ]
        );

        // LTN Express
        Client::updateOrCreate(
            ['company_name' => 'LTN Express'],
            [
                'billing_terms' => 'net_30',
                'billing_email' => 'Lorraine.harris@ltnlogisticscompany.com',
                'notes' => null,
                'is_active' => true,
            ]
        );

        // LTN Logistics
        Client::updateOrCreate(
            ['company_name' => 'LTN Logistics'],
            [
                'billing_terms' => 'net_15',
                'billing_email' => 'lorraine.harris@ltnlogsitics.com',
                'notes' => null,
                'is_active' => true,
            ]
        );

        // Niageo Technologies
        Client::updateOrCreate(
            ['company_name' => 'Niageo Technologies'],
            [
                'billing_terms' => 'net_30',
                'billing_email' => 'ryan.peters@niageo.com',
                'notes' => null,
                'is_active' => true,
            ]
        );
    }
}
