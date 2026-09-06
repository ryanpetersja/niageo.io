<?php

namespace Tests\Unit\Voice;

use App\Services\Voice\GlobalTools;
use App\Services\Voice\Pages\InvoiceFormPage;
use App\Services\Voice\Pages\InvoiceIndexPage;
use App\Services\Voice\Pages\InvoiceShowPage;
use App\Services\Voice\VoiceCommandService;
use App\Services\Voice\VoicePageRegistry;
use Tests\TestCase;

class InvoicePagesTest extends TestCase
{
    public function test_registry_resolves_known_pages_and_falls_back_for_others(): void
    {
        $registry = new VoicePageRegistry();

        $this->assertInstanceOf(InvoiceIndexPage::class, $registry->resolve('invoices.index'));
        $this->assertInstanceOf(InvoiceFormPage::class, $registry->resolve('invoices.form'));
        $this->assertInstanceOf(InvoiceShowPage::class, $registry->resolve('invoices.show'));
        $this->assertSame('clients.index', $registry->resolve('clients.index')->name());
        $this->assertSame([], $registry->resolve('clients.index')->tools([]));
    }

    public function test_every_tool_has_a_valid_schema_and_unique_name(): void
    {
        $registry = new VoicePageRegistry();
        foreach (['invoices.index', 'invoices.form', 'invoices.show'] as $page) {
            $tools = array_merge($registry->resolve($page)->tools([]), GlobalTools::tools());
            $names = array_column($tools, 'name');
            $this->assertSame($names, array_unique($names), "duplicate tool names on {$page}");

            foreach ($tools as $tool) {
                $this->assertMatchesRegularExpression('/^[a-z_]+$/', $tool['name']);
                $this->assertNotEmpty($tool['description']);
                $schema = $tool['input_schema'];
                $this->assertSame('object', $schema['type']);
                $this->assertFalse($schema['additionalProperties']);
                // Empty property maps must encode as {} not [].
                $this->assertStringContainsString('"properties":{', json_encode($schema));
                $properties = is_array($schema['properties']) ? $schema['properties'] : [];
                foreach ($schema['required'] as $required) {
                    $this->assertArrayHasKey($required, $properties, "{$tool['name']} requires unknown property {$required}");
                }
            }
        }
    }

    public function test_form_screen_lists_live_fields_line_items_and_totals(): void
    {
        $screen = (new InvoiceFormPage())->screen([
            'mode' => 'edit',
            'invoice_number' => 'INV-PREV-1006',
            'fields' => ['client_id' => '4', 'client_name' => 'Initech', 'title' => 'March maintenance', 'issue_date' => '2026-09-06', 'due_date' => '2026-10-06', 'tax_rate' => '15', 'notes' => '', 'internal_notes' => ''],
            'clients' => [['id' => 4, 'name' => 'Initech']],
            'presets' => [['id' => 7, 'name' => 'Quarterly bundle', 'total' => 1200]],
            'line_items' => [['line' => 1, 'description' => 'Website hosting', 'quantity' => 12, 'unit_price' => 45, 'total' => 540]],
            'totals' => ['subtotal' => 540, 'tax' => 81, 'total' => 621],
        ]);

        $this->assertStringContainsString('editing draft invoice INV-PREV-1006', $screen);
        $this->assertStringContainsString('client=Initech (id 4)', $screen);
        $this->assertStringContainsString('title="March maintenance"', $screen);
        $this->assertStringContainsString('1. "Website hosting" × 12 @ $45.00 = $540.00', $screen);
        $this->assertStringContainsString('Totals: subtotal $540.00, tax $81.00, total $621.00.', $screen);
        $this->assertStringContainsString('7: Quarterly bundle — $1,200.00', $screen);
    }

    public function test_show_page_confirmations_cover_money_and_deletions_only(): void
    {
        $page = new InvoiceShowPage();
        $context = ['invoice' => ['number' => 'INV-1', 'balance_due' => 250.5], 'payments' => [['number' => 1, 'amount' => 100]]];

        $this->assertNull($page->confirmation('change_status', ['status' => 'sent'], $context));
        $this->assertSame('Mark invoice INV-1 as paid in full? Say yes to confirm.', $page->confirmation('change_status', ['status' => 'paid'], $context));
        $this->assertSame('Cancel invoice INV-1? Say yes to confirm.', $page->confirmation('change_status', ['status' => 'cancelled'], $context));
        $this->assertSame('Delete invoice INV-1? This cannot be undone. Say yes to confirm.', $page->confirmation('delete_invoice', [], $context));
        $this->assertSame('Record a payment of $250.50 dated today on INV-1? Say yes to confirm.', $page->confirmation('record_payment', [], $context));
        $this->assertSame('Delete payment 1 of $100.00? Say yes to confirm.', $page->confirmation('delete_payment', ['payment_number' => 1], $context));
        $this->assertNull($page->confirmation('open_pdf', ['mode' => 'download'], $context));
    }

    public function test_action_validation_coerces_types_and_drops_invalid_calls(): void
    {
        $service = app(VoiceCommandService::class);
        $tools = array_merge((new InvoiceFormPage())->tools([]), GlobalTools::tools());

        $valid = $service->validateActions([
            ['name' => 'update_line_item', 'input' => ['line' => '2', 'unit_price' => '250', 'bogus' => 'x']],
            ['name' => 'set_field', 'input' => ['field' => 'Title', 'value' => 'Hosting']],
            ['name' => 'set_field', 'input' => ['field' => 'colour', 'value' => 'red']],
            ['name' => 'remove_line_item', 'input' => []],
            ['name' => 'navigate', 'input' => ['to' => 'invoices']],
            ['name' => 'launch_rockets', 'input' => []],
        ], $tools);

        $this->assertSame([
            ['name' => 'update_line_item', 'input' => ['line' => 2, 'unit_price' => 250.0]],
            ['name' => 'set_field', 'input' => ['field' => 'title', 'value' => 'Hosting']],
            ['name' => 'navigate', 'input' => ['to' => 'invoices']],
        ], $valid);
    }
}
