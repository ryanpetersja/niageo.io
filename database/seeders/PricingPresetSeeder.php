<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\PricingPreset;
use App\Models\PricingPresetItem;
use Illuminate\Database\Seeder;

class PricingPresetSeeder extends Seeder
{
    public function run(): void
    {
        $snowJm = Client::where('company_name', 'Snow JM')->first();
        $campusElite = Client::where('company_name', 'Campus Elite')->first();
        $ltnExpress = Client::where('company_name', 'LTN Express')->first();

        // Snow JM - Managed Maintenance and Support Budget
        $preset1 = PricingPreset::updateOrCreate(
            ['client_id' => $snowJm->id, 'name' => 'Managed Maintenance and Support Budget'],
            ['is_active' => true]
        );
        $this->seedPresetItems($preset1, [
            ['description' => 'Business hours Only(8am - 5pm | Monday - Friday)', 'quantity' => 1, 'unit_price' => 42187.50, 'sort_order' => 0],
            ['description' => 'App Dependency Management', 'quantity' => 1, 'unit_price' => 25125.00, 'sort_order' => 1],
            ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 17496.00, 'sort_order' => 2],
            ['description' => 'SSL Certificate', 'quantity' => 1, 'unit_price' => 4374.00, 'sort_order' => 3],
            ['description' => 'Mailing', 'quantity' => 1, 'unit_price' => 13851.00, 'sort_order' => 4],
            ['description' => 'Daily Backup', 'quantity' => 1, 'unit_price' => 28062.50, 'sort_order' => 5],
        ]);

        // Campus Elite - Quarterly Maintenance
        $preset2 = PricingPreset::updateOrCreate(
            ['client_id' => $campusElite->id, 'name' => 'Quarterly Maintenance'],
            ['is_active' => true]
        );
        $this->seedPresetItems($preset2, [
            ['description' => 'Business hours Only(8am - 5pm | Monday - Friday)', 'quantity' => 1, 'unit_price' => 84000.00, 'sort_order' => 0],
            ['description' => 'Security updates and patch management for platform dependencies', 'quantity' => 1, 'unit_price' => 35156.00, 'sort_order' => 1],
            ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 34992.00, 'sort_order' => 2],
            ['description' => 'SSL Certificate', 'quantity' => 1, 'unit_price' => 4374.00, 'sort_order' => 3],
            ['description' => 'Mailing', 'quantity' => 1, 'unit_price' => 13851.00, 'sort_order' => 4],
            ['description' => 'Daily Backup', 'quantity' => 1, 'unit_price' => 14062.50, 'sort_order' => 5],
        ]);

        // LTN Express - Monthly Recurring
        $preset3 = PricingPreset::updateOrCreate(
            ['client_id' => $ltnExpress->id, 'name' => 'Monthly Recurring'],
            ['is_active' => true]
        );
        $this->seedPresetItems($preset3, [
            ['description' => 'Hosting - ltnxpress-dashboard.com', 'quantity' => 1, 'unit_price' => 1040.00, 'sort_order' => 0],
            ['description' => 'Hosting-ltnexpress.com', 'quantity' => 1, 'unit_price' => 5440.00, 'sort_order' => 1],
            ['description' => 'backup -(Daily)', 'quantity' => 1, 'unit_price' => 12000.00, 'sort_order' => 2],
        ]);
    }

    private function seedPresetItems(PricingPreset $preset, array $items): void
    {
        // Clear existing items and re-create
        PricingPresetItem::where('pricing_preset_id', $preset->id)->delete();

        foreach ($items as $item) {
            PricingPresetItem::create([
                'pricing_preset_id' => $preset->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $item['sort_order'],
            ]);
        }
    }
}
