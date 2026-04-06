<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\InvoiceStatusHistory;
use App\Models\Payment;
use App\Models\PricingPreset;
use App\Models\PricingPresetItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@niageo.io')->first();

        // Staff user
        $staff = User::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@niageo.io',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Clients
        $clients = [];

        $clients[] = $c1 = Client::create([
            'company_name' => 'Acme Corporation',
            'billing_terms' => 'net_30',
            'billing_email' => 'billing@acmecorp.com',
            'notes' => 'Long-term client. Primary contact prefers email communication.',
            'is_active' => true,
        ]);
        ClientContact::create(['client_id' => $c1->id, 'name' => 'John Smith', 'email' => 'john@acmecorp.com', 'phone' => '(555) 123-4567', 'is_primary' => true]);
        ClientContact::create(['client_id' => $c1->id, 'name' => 'Jane Doe', 'email' => 'jane@acmecorp.com', 'phone' => '(555) 123-4568', 'is_primary' => false]);

        $clients[] = $c2 = Client::create([
            'company_name' => 'TechStart Inc.',
            'billing_terms' => 'net_15',
            'billing_email' => 'accounts@techstart.io',
            'notes' => 'Startup client. Fast-paced project cycles.',
            'is_active' => true,
        ]);
        ClientContact::create(['client_id' => $c2->id, 'name' => 'Mike Chen', 'email' => 'mike@techstart.io', 'phone' => '(555) 234-5678', 'is_primary' => true]);

        $clients[] = $c3 = Client::create([
            'company_name' => 'GreenLeaf Solutions',
            'billing_terms' => 'net_30',
            'billing_email' => 'finance@greenleaf.com',
            'notes' => 'Environmental consulting firm. Quarterly maintenance contract.',
            'is_active' => true,
        ]);
        ClientContact::create(['client_id' => $c3->id, 'name' => 'Lisa Park', 'email' => 'lisa@greenleaf.com', 'phone' => '(555) 345-6789', 'is_primary' => true]);
        ClientContact::create(['client_id' => $c3->id, 'name' => 'Tom Williams', 'email' => 'tom@greenleaf.com', 'is_primary' => false]);

        $clients[] = $c4 = Client::create([
            'company_name' => 'Harbor Properties LLC',
            'billing_terms' => 'due_on_receipt',
            'billing_email' => 'ap@harborprop.com',
            'is_active' => true,
        ]);
        ClientContact::create(['client_id' => $c4->id, 'name' => 'David Brown', 'email' => 'david@harborprop.com', 'phone' => '(555) 456-7890', 'is_primary' => true]);

        $clients[] = $c5 = Client::create([
            'company_name' => 'Sunset Media Group',
            'billing_terms' => 'net_60',
            'billing_email' => 'billing@sunsetmedia.co',
            'notes' => 'Inactive — project completed.',
            'is_active' => false,
        ]);
        ClientContact::create(['client_id' => $c5->id, 'name' => 'Amy Rivera', 'email' => 'amy@sunsetmedia.co', 'is_primary' => true]);

        // Pricing Presets
        $preset1 = PricingPreset::create(['client_id' => $c1->id, 'name' => 'Quarterly Maintenance', 'is_active' => true]);
        PricingPresetItem::create(['pricing_preset_id' => $preset1->id, 'description' => 'Web application maintenance & updates', 'quantity' => 1, 'unit_price' => 1500.00, 'sort_order' => 1]);
        PricingPresetItem::create(['pricing_preset_id' => $preset1->id, 'description' => 'Hosting & infrastructure management', 'quantity' => 1, 'unit_price' => 500.00, 'sort_order' => 2]);
        PricingPresetItem::create(['pricing_preset_id' => $preset1->id, 'description' => 'Security monitoring & patching', 'quantity' => 1, 'unit_price' => 300.00, 'sort_order' => 3]);

        $preset2 = PricingPreset::create(['client_id' => $c3->id, 'name' => 'Monthly Retainer', 'is_active' => true]);
        PricingPresetItem::create(['pricing_preset_id' => $preset2->id, 'description' => 'Development hours (up to 20hrs)', 'quantity' => 20, 'unit_price' => 125.00, 'sort_order' => 1]);
        PricingPresetItem::create(['pricing_preset_id' => $preset2->id, 'description' => 'Project management', 'quantity' => 1, 'unit_price' => 400.00, 'sort_order' => 2]);

        // Invoices
        // Invoice 1: Paid invoice for Acme
        $inv1 = Invoice::create([
            'invoice_number' => 'INV-' . now()->subMonths(2)->format('Ym') . '-0001',
            'client_id' => $c1->id,
            'created_by' => $admin->id,
            'status' => 'paid',
            'issue_date' => now()->subMonths(2),
            'due_date' => now()->subMonths(2)->addDays(30),
            'subtotal' => 2300.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 2300.00,
            'amount_paid' => 2300.00,
            'notes' => 'Q3 maintenance package.',
            'pricing_preset_id' => $preset1->id,
        ]);
        InvoiceLineItem::create(['invoice_id' => $inv1->id, 'description' => 'Web application maintenance & updates', 'quantity' => 1, 'unit_price' => 1500.00, 'total' => 1500.00, 'sort_order' => 1]);
        InvoiceLineItem::create(['invoice_id' => $inv1->id, 'description' => 'Hosting & infrastructure management', 'quantity' => 1, 'unit_price' => 500.00, 'total' => 500.00, 'sort_order' => 2]);
        InvoiceLineItem::create(['invoice_id' => $inv1->id, 'description' => 'Security monitoring & patching', 'quantity' => 1, 'unit_price' => 300.00, 'total' => 300.00, 'sort_order' => 3]);
        InvoiceStatusHistory::create(['invoice_id' => $inv1->id, 'from_status' => null, 'to_status' => 'draft', 'changed_by' => $admin->id, 'created_at' => now()->subMonths(2)]);
        InvoiceStatusHistory::create(['invoice_id' => $inv1->id, 'from_status' => 'draft', 'to_status' => 'sent', 'changed_by' => $admin->id, 'created_at' => now()->subMonths(2)->addDay()]);
        InvoiceStatusHistory::create(['invoice_id' => $inv1->id, 'from_status' => 'sent', 'to_status' => 'paid', 'changed_by' => $admin->id, 'created_at' => now()->subMonths(1)->subDays(5)]);
        Payment::create(['invoice_id' => $inv1->id, 'amount' => 2300.00, 'payment_date' => now()->subMonths(1)->subDays(5), 'payment_method' => 'bank_transfer', 'reference' => 'ACH-98712', 'recorded_by' => $admin->id]);

        // Invoice 2: Sent invoice for TechStart (partially paid)
        $inv2 = Invoice::create([
            'invoice_number' => 'INV-' . now()->subMonth()->format('Ym') . '-0001',
            'client_id' => $c2->id,
            'created_by' => $admin->id,
            'status' => 'sent',
            'issue_date' => now()->subMonth(),
            'due_date' => now()->subMonth()->addDays(15),
            'subtotal' => 4500.00,
            'tax_rate' => 8.25,
            'tax_amount' => 371.25,
            'total' => 4871.25,
            'amount_paid' => 2000.00,
            'notes' => 'Custom dashboard development - Phase 1.',
        ]);
        InvoiceLineItem::create(['invoice_id' => $inv2->id, 'description' => 'Dashboard UI design & implementation', 'quantity' => 30, 'unit_price' => 125.00, 'total' => 3750.00, 'sort_order' => 1]);
        InvoiceLineItem::create(['invoice_id' => $inv2->id, 'description' => 'API integration & data pipeline', 'quantity' => 6, 'unit_price' => 125.00, 'total' => 750.00, 'sort_order' => 2]);
        InvoiceStatusHistory::create(['invoice_id' => $inv2->id, 'from_status' => null, 'to_status' => 'draft', 'changed_by' => $admin->id, 'created_at' => now()->subMonth()]);
        InvoiceStatusHistory::create(['invoice_id' => $inv2->id, 'from_status' => 'draft', 'to_status' => 'sent', 'changed_by' => $admin->id, 'created_at' => now()->subMonth()->addDay()]);
        Payment::create(['invoice_id' => $inv2->id, 'amount' => 2000.00, 'payment_date' => now()->subWeeks(2), 'payment_method' => 'credit_card', 'reference' => 'CC-44521', 'recorded_by' => $admin->id]);

        // Invoice 3: Overdue for GreenLeaf
        $inv3 = Invoice::create([
            'invoice_number' => 'INV-' . now()->subMonths(2)->format('Ym') . '-0002',
            'client_id' => $c3->id,
            'created_by' => $staff->id,
            'status' => 'overdue',
            'issue_date' => now()->subMonths(2),
            'due_date' => now()->subMonth(),
            'subtotal' => 2900.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 2900.00,
            'amount_paid' => 0,
            'notes' => 'Monthly retainer - January.',
        ]);
        InvoiceLineItem::create(['invoice_id' => $inv3->id, 'description' => 'Development hours (up to 20hrs)', 'quantity' => 20, 'unit_price' => 125.00, 'total' => 2500.00, 'sort_order' => 1]);
        InvoiceLineItem::create(['invoice_id' => $inv3->id, 'description' => 'Project management', 'quantity' => 1, 'unit_price' => 400.00, 'total' => 400.00, 'sort_order' => 2]);
        InvoiceStatusHistory::create(['invoice_id' => $inv3->id, 'from_status' => null, 'to_status' => 'draft', 'changed_by' => $staff->id, 'created_at' => now()->subMonths(2)]);
        InvoiceStatusHistory::create(['invoice_id' => $inv3->id, 'from_status' => 'draft', 'to_status' => 'sent', 'changed_by' => $staff->id, 'created_at' => now()->subMonths(2)->addDay()]);
        InvoiceStatusHistory::create(['invoice_id' => $inv3->id, 'from_status' => 'sent', 'to_status' => 'overdue', 'notes' => 'Automatically marked overdue by system.', 'created_at' => now()->subMonth()]);

        // Invoice 4: Draft for Harbor Properties
        $inv4 = Invoice::create([
            'invoice_number' => 'INV-' . now()->format('Ym') . '-0001',
            'client_id' => $c4->id,
            'created_by' => $admin->id,
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now(),
            'subtotal' => 8500.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 8500.00,
            'amount_paid' => 0,
            'notes' => 'Website redesign project.',
            'internal_notes' => 'Awaiting final approval from client before sending.',
        ]);
        InvoiceLineItem::create(['invoice_id' => $inv4->id, 'description' => 'Website redesign - UX/UI design', 'quantity' => 1, 'unit_price' => 3500.00, 'total' => 3500.00, 'sort_order' => 1]);
        InvoiceLineItem::create(['invoice_id' => $inv4->id, 'description' => 'Frontend development (responsive)', 'quantity' => 1, 'unit_price' => 3000.00, 'total' => 3000.00, 'sort_order' => 2]);
        InvoiceLineItem::create(['invoice_id' => $inv4->id, 'description' => 'CMS integration & content migration', 'quantity' => 1, 'unit_price' => 2000.00, 'total' => 2000.00, 'sort_order' => 3]);
        InvoiceStatusHistory::create(['invoice_id' => $inv4->id, 'from_status' => null, 'to_status' => 'draft', 'changed_by' => $admin->id, 'created_at' => now()]);

        // Invoice 5: Cancelled for Sunset Media
        $inv5 = Invoice::create([
            'invoice_number' => 'INV-' . now()->subMonths(3)->format('Ym') . '-0001',
            'client_id' => $c5->id,
            'created_by' => $admin->id,
            'status' => 'cancelled',
            'issue_date' => now()->subMonths(3),
            'due_date' => now()->subMonths(3)->addDays(60),
            'subtotal' => 1200.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 1200.00,
            'amount_paid' => 0,
            'notes' => 'Social media campaign assets.',
            'internal_notes' => 'Client cancelled the project.',
        ]);
        InvoiceLineItem::create(['invoice_id' => $inv5->id, 'description' => 'Social media graphics package', 'quantity' => 1, 'unit_price' => 800.00, 'total' => 800.00, 'sort_order' => 1]);
        InvoiceLineItem::create(['invoice_id' => $inv5->id, 'description' => 'Content calendar setup', 'quantity' => 1, 'unit_price' => 400.00, 'total' => 400.00, 'sort_order' => 2]);
        InvoiceStatusHistory::create(['invoice_id' => $inv5->id, 'from_status' => null, 'to_status' => 'draft', 'changed_by' => $admin->id, 'created_at' => now()->subMonths(3)]);
        InvoiceStatusHistory::create(['invoice_id' => $inv5->id, 'from_status' => 'draft', 'to_status' => 'cancelled', 'changed_by' => $admin->id, 'notes' => 'Client cancelled the project.', 'created_at' => now()->subMonths(3)->addDays(5)]);

        // Invoice 6: Recently paid for Acme (current month)
        $inv6 = Invoice::create([
            'invoice_number' => 'INV-' . now()->format('Ym') . '-0002',
            'client_id' => $c1->id,
            'created_by' => $staff->id,
            'status' => 'paid',
            'issue_date' => now()->subWeeks(3),
            'due_date' => now()->addWeek(),
            'subtotal' => 3200.00,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 3200.00,
            'amount_paid' => 3200.00,
            'notes' => 'Q4 maintenance + emergency hotfix.',
        ]);
        InvoiceLineItem::create(['invoice_id' => $inv6->id, 'description' => 'Quarterly maintenance package', 'quantity' => 1, 'unit_price' => 2300.00, 'total' => 2300.00, 'sort_order' => 1]);
        InvoiceLineItem::create(['invoice_id' => $inv6->id, 'description' => 'Emergency production hotfix (4hrs)', 'quantity' => 4, 'unit_price' => 225.00, 'total' => 900.00, 'sort_order' => 2]);
        InvoiceStatusHistory::create(['invoice_id' => $inv6->id, 'from_status' => null, 'to_status' => 'draft', 'changed_by' => $staff->id, 'created_at' => now()->subWeeks(3)]);
        InvoiceStatusHistory::create(['invoice_id' => $inv6->id, 'from_status' => 'draft', 'to_status' => 'sent', 'changed_by' => $staff->id, 'created_at' => now()->subWeeks(3)->addDay()]);
        InvoiceStatusHistory::create(['invoice_id' => $inv6->id, 'from_status' => 'sent', 'to_status' => 'paid', 'changed_by' => $admin->id, 'created_at' => now()->subDays(3)]);
        Payment::create(['invoice_id' => $inv6->id, 'amount' => 3200.00, 'payment_date' => now()->subDays(3), 'payment_method' => 'check', 'reference' => 'CHK-10244', 'recorded_by' => $admin->id]);
    }
}
