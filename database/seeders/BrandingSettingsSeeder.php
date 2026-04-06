<?php

namespace Database\Seeders;

use App\Models\BrandingSetting;
use Illuminate\Database\Seeder;

class BrandingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        BrandingSetting::firstOrCreate([], [
            'company_name' => 'NiageoOps',
            'email' => 'info@niageo.io',
            'phone' => '',
            'website' => 'https://niageo.io',
        ]);
    }
}
