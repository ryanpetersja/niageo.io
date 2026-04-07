<?php

namespace Database\Seeders;

use App\Models\BrandingSetting;
use Illuminate\Database\Seeder;

class BrandingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        BrandingSetting::firstOrCreate([], [
            'company_name' => 'Niageo',
            'email' => 'info@niageo.com',
            'phone' => '876-543-3794',
            'website' => 'https://niageo.com',
            'address' => null,
            'footer_text' => null,
        ]);
    }
}
