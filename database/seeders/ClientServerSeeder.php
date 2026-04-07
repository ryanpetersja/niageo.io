<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientServer;
use Illuminate\Database\Seeder;

class ClientServerSeeder extends Seeder
{
    public function run(): void
    {
        $snowJm = Client::where('company_name', 'Snow JM')->first();

        ClientServer::updateOrCreate(
            ['client_id' => $snowJm->id, 'label' => 'Main Server'],
            [
                'host' => '159.223.142.9',
                'port' => 22,
                'username' => 'forge',
                'auth_type' => 'key',
                'private_key_path' => null, // Set manually after seeding — machine-specific path
                'is_active' => true,
            ]
        );
    }
}
