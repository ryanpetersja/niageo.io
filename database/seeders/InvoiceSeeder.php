<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\InvoiceStatusHistory;
use App\Models\PricingPreset;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@niageo.io')->first();
        $sunsetMedia = Client::where('company_name', 'Sunset Media Group')->first();
        $campusElite = Client::where('company_name', 'Campus Elite')->first();
        $snowJm = Client::where('company_name', 'Snow JM')->first();
        $ltnExpress = Client::where('company_name', 'LTN Express')->first();
        $ltnLogistics = Client::where('company_name', 'LTN Logistics')->first();

        $cePreset = PricingPreset::where('name', 'Quarterly Maintenance')->first();
        $ltnPreset = PricingPreset::where('name', 'Monthly Recurring')->first();

        // INV-202511-0001 - Sunset Media - Cancelled
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202511-0001',
            'client_id' => $sunsetMedia->id,
            'status' => 'cancelled',
            'issue_date' => '2025-11-16',
            'due_date' => '2026-01-15',
            'subtotal' => 1200.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 1200.00,
            'notes' => 'Social media campaign assets.',
            'internal_notes' => 'Client cancelled the project.',
        ], [
            ['description' => 'Social media graphics package', 'quantity' => 1, 'unit_price' => 800.00, 'total' => 800.00, 'sort_order' => 1],
            ['description' => 'Content calendar setup', 'quantity' => 1, 'unit_price' => 400.00, 'total' => 400.00, 'sort_order' => 2],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'created_at' => '2025-11-16 17:49:17'],
            ['from_status' => 'draft', 'to_status' => 'cancelled', 'notes' => 'Client cancelled the project.', 'created_at' => '2025-11-21 17:49:17'],
        ]);

        // INV-202602-0001 - Snow JM - Sent (Oct 2025 - Mar 2026)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0001',
            'client_id' => $snowJm->id,
            'status' => 'sent',
            'issue_date' => '2025-10-01',
            'due_date' => '2026-03-01',
            'subtotal' => 131096.00,
            'tax_rate' => 15.00,
            'tax_amount' => 19664.40,
            'total' => 150760.40,
        ], [
            ['description' => 'Business hours Only(8am - 5pm | Monday - Friday)', 'quantity' => 1, 'unit_price' => 42187.50, 'total' => 42187.50, 'sort_order' => 0],
            ['description' => 'App Dependency Management', 'quantity' => 1, 'unit_price' => 25125.00, 'total' => 25125.00, 'sort_order' => 1],
            ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 17496.00, 'total' => 17496.00, 'sort_order' => 2],
            ['description' => 'SSL Certificate', 'quantity' => 1, 'unit_price' => 4374.00, 'total' => 4374.00, 'sort_order' => 3],
            ['description' => 'Mailing', 'quantity' => 1, 'unit_price' => 13851.00, 'total' => 13851.00, 'sort_order' => 4],
            ['description' => 'Daily Backup', 'quantity' => 1, 'unit_price' => 28062.50, 'total' => 28062.50, 'sort_order' => 5],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 01:37:21'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 01:37:38'],
        ]);

        // INV-202602-0002 - Snow JM - Paid (Nov 2025 - Jan 2026)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0002',
            'client_id' => $snowJm->id,
            'status' => 'paid',
            'issue_date' => '2025-11-01',
            'due_date' => '2026-01-31',
            'subtotal' => 131096.00,
            'tax_rate' => 15.00,
            'tax_amount' => 19664.40,
            'total' => 150760.40,
        ], [
            ['description' => 'Business hours Only(8am - 5pm | Monday - Friday)', 'quantity' => 1, 'unit_price' => 42187.50, 'total' => 42187.50, 'sort_order' => 0],
            ['description' => 'App Dependency Management', 'quantity' => 1, 'unit_price' => 25125.00, 'total' => 25125.00, 'sort_order' => 1],
            ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 17496.00, 'total' => 17496.00, 'sort_order' => 2],
            ['description' => 'SSL Certificate', 'quantity' => 1, 'unit_price' => 4374.00, 'total' => 4374.00, 'sort_order' => 3],
            ['description' => 'Mailing', 'quantity' => 1, 'unit_price' => 13851.00, 'total' => 13851.00, 'sort_order' => 4],
            ['description' => 'Daily Backup', 'quantity' => 1, 'unit_price' => 28062.50, 'total' => 28062.50, 'sort_order' => 5],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 01:47:34'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 01:49:31'],
            ['from_status' => 'sent', 'to_status' => 'paid', 'created_at' => '2026-02-17 11:57:37'],
        ]);

        // INV-202602-0003 - Campus Elite - Sent (Quarterly Maintenance)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0003',
            'client_id' => $campusElite->id,
            'status' => 'sent',
            'issue_date' => '2025-11-01',
            'due_date' => '2026-01-31',
            'subtotal' => 186435.50,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 186435.50,
            'pricing_preset_id' => $cePreset?->id,
        ], [
            ['description' => 'Business hours Only(8am - 5pm | Monday - Friday)', 'quantity' => 1, 'unit_price' => 84000.00, 'total' => 84000.00, 'sort_order' => 0],
            ['description' => 'Security updates and patch management for platform dependencies', 'quantity' => 1, 'unit_price' => 35156.00, 'total' => 35156.00, 'sort_order' => 1],
            ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 34992.00, 'total' => 34992.00, 'sort_order' => 2],
            ['description' => 'SSL Certificate', 'quantity' => 1, 'unit_price' => 4374.00, 'total' => 4374.00, 'sort_order' => 3],
            ['description' => 'Mailing', 'quantity' => 1, 'unit_price' => 13851.00, 'total' => 13851.00, 'sort_order' => 4],
            ['description' => 'Daily Backup', 'quantity' => 1, 'unit_price' => 14062.50, 'total' => 14062.50, 'sort_order' => 5],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 02:02:32'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 02:03:20'],
        ]);

        // INV-202602-0004 - LTN Express - Sent (Sep 2025)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0004',
            'client_id' => $ltnExpress->id,
            'status' => 'sent',
            'issue_date' => '2025-09-01',
            'due_date' => '2026-03-05',
            'subtotal' => 56980.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 56980.00,
            'pricing_preset_id' => $ltnPreset?->id,
        ], [
            ['description' => 'Hosting - ltnxpress-dashboard.com', 'quantity' => 1, 'unit_price' => 1040.00, 'total' => 1040.00, 'sort_order' => 0],
            ['description' => 'Hosting-ltnexpress.com', 'quantity' => 1, 'unit_price' => 5440.00, 'total' => 5440.00, 'sort_order' => 1],
            ['description' => 'backup -(Daily)', 'quantity' => 1, 'unit_price' => 12000.00, 'total' => 12000.00, 'sort_order' => 2],
            ['description' => 'Status Updates and Branch Updates', 'quantity' => 1, 'unit_price' => 27000.00, 'total' => 27000.00, 'sort_order' => 3],
            ['description' => 'Mail Administration', 'quantity' => 1, 'unit_price' => 11500.00, 'total' => 11500.00, 'sort_order' => 4],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 02:19:56'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 02:20:58'],
        ]);

        // INV-202602-0005 - LTN Express - Sent (Oct 2025)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0005',
            'client_id' => $ltnExpress->id,
            'status' => 'sent',
            'issue_date' => '2025-10-01',
            'due_date' => '2026-03-05',
            'subtotal' => 31369.83,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 31369.83,
            'pricing_preset_id' => $ltnPreset?->id,
        ], [
            ['description' => 'Hosting - ltnxpress-dashboard.com', 'quantity' => 1, 'unit_price' => 1040.00, 'total' => 1040.00, 'sort_order' => 0],
            ['description' => 'Hosting-ltnexpress.com', 'quantity' => 1, 'unit_price' => 5440.00, 'total' => 5440.00, 'sort_order' => 1],
            ['description' => 'backup -(Daily)', 'quantity' => 1, 'unit_price' => 12000.00, 'total' => 12000.00, 'sort_order' => 2],
            ['description' => 'Platform Emailing', 'quantity' => 1, 'unit_price' => 3500.00, 'total' => 3500.00, 'sort_order' => 3],
            ['description' => 'Zoho Subscription', 'quantity' => 1, 'unit_price' => 9389.83, 'total' => 9389.83, 'sort_order' => 4],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 02:21:15'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 02:27:27'],
        ]);

        // INV-202602-0006 - LTN Express - Sent (Nov 2025)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0006',
            'client_id' => $ltnExpress->id,
            'status' => 'sent',
            'issue_date' => '2025-11-01',
            'due_date' => '2025-11-05',
            'subtotal' => 21980.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 21980.00,
            'pricing_preset_id' => $ltnPreset?->id,
        ], [
            ['description' => 'Hosting - ltnxpress-dashboard.com', 'quantity' => 1, 'unit_price' => 1040.00, 'total' => 1040.00, 'sort_order' => 0],
            ['description' => 'Hosting-ltnexpress.com', 'quantity' => 1, 'unit_price' => 5440.00, 'total' => 5440.00, 'sort_order' => 1],
            ['description' => 'backup -(Daily)', 'quantity' => 1, 'unit_price' => 12000.00, 'total' => 12000.00, 'sort_order' => 2],
            ['description' => 'Platform Emailing', 'quantity' => 1, 'unit_price' => 3500.00, 'total' => 3500.00, 'sort_order' => 3],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 02:27:54'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 02:28:23'],
        ]);

        // INV-202602-0007 - LTN Express - Sent (Dec 2025)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0007',
            'client_id' => $ltnExpress->id,
            'status' => 'sent',
            'issue_date' => '2025-12-01',
            'due_date' => '2025-12-05',
            'subtotal' => 28826.81,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 28826.81,
            'pricing_preset_id' => $ltnPreset?->id,
        ], [
            ['description' => 'Hosting - ltnxpress-dashboard.com', 'quantity' => 1, 'unit_price' => 1040.00, 'total' => 1040.00, 'sort_order' => 0],
            ['description' => 'Hosting-ltnexpress.com', 'quantity' => 1, 'unit_price' => 5440.00, 'total' => 5440.00, 'sort_order' => 1],
            ['description' => 'backup -(Daily)', 'quantity' => 1, 'unit_price' => 12000.00, 'total' => 12000.00, 'sort_order' => 2],
            ['description' => 'Platform Emailing', 'quantity' => 1, 'unit_price' => 3500.00, 'total' => 3500.00, 'sort_order' => 3],
            ['description' => 'Zoho Mail', 'quantity' => 1, 'unit_price' => 6846.81, 'total' => 6846.81, 'sort_order' => 4],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 02:31:54'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 02:32:47'],
        ]);

        // INV-202602-0008 - LTN Express - Sent (Jan 2026)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0008',
            'client_id' => $ltnExpress->id,
            'status' => 'sent',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-05',
            'subtotal' => 21980.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 21980.00,
            'pricing_preset_id' => $ltnPreset?->id,
        ], [
            ['description' => 'Hosting - ltnxpress-dashboard.com', 'quantity' => 1, 'unit_price' => 1040.00, 'total' => 1040.00, 'sort_order' => 0],
            ['description' => 'Hosting-ltnexpress.com', 'quantity' => 1, 'unit_price' => 5440.00, 'total' => 5440.00, 'sort_order' => 1],
            ['description' => 'backup -(Daily)', 'quantity' => 1, 'unit_price' => 12000.00, 'total' => 12000.00, 'sort_order' => 2],
            ['description' => 'Platform Emailing', 'quantity' => 1, 'unit_price' => 3500.00, 'total' => 3500.00, 'sort_order' => 3],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 02:35:08'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 02:37:10'],
        ]);

        // INV-202602-0009 - LTN Express - Sent (Feb 2026)
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0009',
            'client_id' => $ltnExpress->id,
            'status' => 'sent',
            'issue_date' => '2026-02-01',
            'due_date' => '2026-03-05',
            'subtotal' => 21980.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 21980.00,
            'pricing_preset_id' => $ltnPreset?->id,
        ], [
            ['description' => 'Hosting - ltnxpress-dashboard.com', 'quantity' => 1, 'unit_price' => 1040.00, 'total' => 1040.00, 'sort_order' => 0],
            ['description' => 'Hosting-ltnexpress.com', 'quantity' => 1, 'unit_price' => 5440.00, 'total' => 5440.00, 'sort_order' => 1],
            ['description' => 'backup -(Daily)', 'quantity' => 1, 'unit_price' => 12000.00, 'total' => 12000.00, 'sort_order' => 2],
            ['description' => 'Platform Emailing', 'quantity' => 1, 'unit_price' => 3500.00, 'total' => 3500.00, 'sort_order' => 3],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 02:36:34'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 02:37:28'],
        ]);

        // INV-202602-0010 - LTN Logistics - Cancelled
        $this->createInvoice($admin, [
            'invoice_number' => 'INV-202602-0010',
            'client_id' => $ltnLogistics->id,
            'status' => 'cancelled',
            'issue_date' => '2025-04-03',
            'due_date' => '2026-03-19',
            'subtotal' => 255872.60,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 255872.60,
        ], [
            ['description' => 'Zoho Mail', 'quantity' => 1, 'unit_price' => 255872.60, 'total' => 255872.60, 'sort_order' => 0],
        ], [
            ['from_status' => null, 'to_status' => 'draft', 'notes' => 'Invoice created', 'created_at' => '2026-02-17 07:25:37'],
            ['from_status' => 'draft', 'to_status' => 'sent', 'created_at' => '2026-02-17 07:26:25'],
            ['from_status' => 'sent', 'to_status' => 'draft', 'created_at' => '2026-02-17 07:28:39'],
            ['from_status' => 'draft', 'to_status' => 'cancelled', 'created_at' => '2026-02-17 08:19:37'],
        ]);
    }

    private function createInvoice(User $admin, array $invoiceData, array $lineItems, array $statusHistory): void
    {
        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => $invoiceData['invoice_number']],
            array_merge($invoiceData, ['created_by' => $admin->id])
        );

        // Clear and re-create line items
        InvoiceLineItem::where('invoice_id', $invoice->id)->delete();
        foreach ($lineItems as $item) {
            InvoiceLineItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
        }

        // Clear and re-create status history
        InvoiceStatusHistory::where('invoice_id', $invoice->id)->delete();
        foreach ($statusHistory as $history) {
            InvoiceStatusHistory::create(array_merge($history, [
                'invoice_id' => $invoice->id,
                'changed_by' => $history['changed_by'] ?? $admin->id,
            ]));
        }
    }
}
