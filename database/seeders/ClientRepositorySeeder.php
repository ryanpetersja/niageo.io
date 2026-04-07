<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientRepository;
use Illuminate\Database\Seeder;

class ClientRepositorySeeder extends Seeder
{
    public function run(): void
    {
        $snowJm = Client::where('company_name', 'Snow JM')->first();
        $campusElite = Client::where('company_name', 'Campus Elite')->first();

        ClientRepository::updateOrCreate(
            ['client_id' => $snowJm->id, 'repo_name' => 'SNOW-Application-Portal'],
            [
                'owner' => 'ryanpetersja',
                'default_branch' => 'ryan/email-seperation-2',
                'is_active' => true,
            ]
        );

        ClientRepository::updateOrCreate(
            ['client_id' => $campusElite->id, 'repo_name' => 'campus-elite'],
            [
                'owner' => 'ryanpetersja',
                'default_branch' => 'ryan/comprehensive-logging',
                'is_active' => true,
            ]
        );
    }
}
