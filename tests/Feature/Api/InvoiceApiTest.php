<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email' => 'ryan@example.test', 'password' => Hash::make('secret123')]);
        $this->client = Client::create(['company_name' => 'Campus Elite', 'billing_terms' => 'net_30', 'billing_email' => 'ap@campuselite.test', 'is_active' => true]);
    }

    private function invoicePayload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'title' => 'October maintenance',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'tax_rate' => 10,
            'notes' => 'Thank you.',
            'line_items' => [
                ['description' => 'Hosting', 'quantity' => 2, 'unit_price' => 100],
                ['description' => 'Support hours', 'quantity' => 1, 'unit_price' => 50],
            ],
        ], $overrides);
    }

    public function test_login_issues_a_token_and_rejects_bad_credentials(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'ryan@example.test', 'password' => 'nope', 'device_name' => 'Pixel'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $response = $this->postJson('/api/v1/auth/login', ['email' => 'ryan@example.test', 'password' => 'secret123', 'device_name' => 'Pixel'])
            ->assertOk()
            ->assertJsonPath('user.email', 'ryan@example.test');

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('name', $this->user->name);
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertSame(0, $this->user->tokens()->count());
    }

    public function test_endpoints_require_a_token(): void
    {
        $this->getJson('/api/v1/invoices')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_invoices_can_be_created_listed_and_shown(): void
    {
        Sanctum::actingAs($this->user);

        $created = $this->postJson('/api/v1/invoices', $this->invoicePayload())
            ->assertCreated()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('client.company_name', 'Campus Elite')
            ->assertJsonPath('subtotal', 250)
            ->assertJsonPath('tax_amount', 25)
            ->assertJsonPath('total', 275)
            ->assertJsonPath('balance_due', 275)
            ->assertJsonPath('valid_transitions', ['sent', 'cancelled'])
            ->assertJsonPath('can_edit', true)
            ->assertJsonCount(2, 'line_items')
            ->assertJsonPath('line_items.0.total', 200);

        $this->assertMatchesRegularExpression('/^INV-\d{6}-0001$/', $created->json('invoice_number'));
        $this->assertStringContainsString('/api/v1/invoices/' . $created->json('id') . '/pdf', $created->json('pdf_url'));

        $this->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.invoice_number', $created->json('invoice_number'))
            ->assertJsonMissingPath('data.0.line_items');

        $this->getJson('/api/v1/invoices?status=paid')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/invoices?search=campus')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/invoices?search=nothing-like-this')->assertOk()->assertJsonPath('meta.total', 0);

        $this->getJson('/api/v1/invoices/' . $created->json('id'))
            ->assertOk()
            ->assertJsonCount(2, 'line_items')
            ->assertJsonPath('status_history.0.to_status', 'draft')
            ->assertJsonPath('client.contacts', []);

        $this->postJson('/api/v1/invoices', $this->invoicePayload(['due_date' => '2026-10-01']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('due_date');
    }

    public function test_draft_invoices_can_be_edited_and_sent_ones_cannot(): void
    {
        Sanctum::actingAs($this->user);
        $id = $this->postJson('/api/v1/invoices', $this->invoicePayload())->json('id');

        $this->putJson("/api/v1/invoices/{$id}", $this->invoicePayload([
            'title' => 'Renamed',
            'tax_rate' => 0,
            'line_items' => [['description' => 'One thing', 'quantity' => 1, 'unit_price' => 80]],
        ]))
            ->assertOk()
            ->assertJsonPath('title', 'Renamed')
            ->assertJsonPath('total', 80)
            ->assertJsonCount(1, 'line_items');

        $this->postJson("/api/v1/invoices/{$id}/transition", ['status' => 'sent'])->assertOk()->assertJsonPath('status', 'sent');

        $this->putJson("/api/v1/invoices/{$id}", $this->invoicePayload())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only draft invoices can be edited.');

        $this->deleteJson("/api/v1/invoices/{$id}")->assertStatus(422);
        $this->postJson("/api/v1/invoices/{$id}/transition", ['status' => 'cancelled'])->assertOk();
        $this->deleteJson("/api/v1/invoices/{$id}")->assertNoContent();
        $this->assertNull(Invoice::find($id));
    }

    public function test_status_transitions_and_payments(): void
    {
        Sanctum::actingAs($this->user);
        $id = $this->postJson('/api/v1/invoices', $this->invoicePayload())->json('id');

        $this->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 100, 'payment_date' => '2026-11-02'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot record payments on draft or cancelled invoices.');

        $this->postJson("/api/v1/invoices/{$id}/transition", ['status' => 'paid'])
            ->assertStatus(422)
            ->assertJsonPath('message', "Cannot transition from 'draft' to 'paid'.");

        $this->postJson("/api/v1/invoices/{$id}/transition", ['status' => 'sent', 'notes' => 'Emailed to AP'])
            ->assertOk()
            ->assertJsonPath('status', 'sent')
            ->assertJsonPath('can_record_payment', true)
            ->assertJsonPath('status_history.0.notes', 'Emailed to AP');

        $this->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 100, 'payment_date' => '2026-11-02', 'payment_method' => 'Bank transfer'])
            ->assertCreated()
            ->assertJsonPath('status', 'sent')
            ->assertJsonPath('amount_paid', 100)
            ->assertJsonPath('balance_due', 175)
            ->assertJsonPath('payments.0.payment_method', 'Bank transfer');

        $this->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 175, 'payment_date' => '2026-11-03'])
            ->assertCreated()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('balance_due', 0)
            ->assertJsonCount(2, 'payments');

        $copy = $this->postJson("/api/v1/invoices/{$id}/duplicate")->assertCreated();
        $this->assertSame('draft', $copy->json('status'));
        $this->assertNotSame($id, $copy->json('id'));
        $this->assertCount(2, $copy->json('line_items'));
    }

    public function test_dashboard_summarises_the_pipeline(): void
    {
        Sanctum::actingAs($this->user);
        $sent = $this->postJson('/api/v1/invoices', $this->invoicePayload())->json('id');
        $this->postJson("/api/v1/invoices/{$sent}/transition", ['status' => 'sent']);
        $this->postJson('/api/v1/invoices', $this->invoicePayload(['line_items' => [['description' => 'Draft work', 'quantity' => 1, 'unit_price' => 40]]]));

        $response = $this->getJson('/api/v1/dashboard')->assertOk();
        $pipeline = collect($response->json('pipeline'))->keyBy('status');

        $this->assertSame(['draft', 'sent', 'overdue', 'paid', 'cancelled'], array_keys($pipeline->all()));
        $this->assertSame(1, $pipeline['sent']['count']);
        $this->assertSame(275.0, (float) $pipeline['sent']['balance_due']);
        $this->assertSame(1, $pipeline['draft']['count']);
        $this->assertSame(0, $pipeline['paid']['count']);
        $this->assertArrayHasKey('total_outstanding', $response->json('metrics'));
        $this->assertCount(2, $response->json('recent_invoices'));
        $this->assertSame([], $response->json('overdue_invoices'));
        $this->assertSame(0.0, (float) $response->json('collected_this_month'));
    }

    public function test_clients_can_be_listed_and_quick_created(): void
    {
        Sanctum::actingAs($this->user);
        $id = $this->postJson('/api/v1/invoices', $this->invoicePayload())->json('id');
        $this->postJson("/api/v1/invoices/{$id}/transition", ['status' => 'sent']);

        $this->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonPath('data.0.company_name', 'Campus Elite')
            ->assertJsonPath('data.0.outstanding_balance', 275)
            ->assertJsonPath('data.0.due_days', 30);

        $this->getJson('/api/v1/clients?search=zzz')->assertOk()->assertJsonCount(0, 'data');

        $this->postJson('/api/v1/clients', [
            'company_name' => 'New Co',
            'billing_terms' => 'net_15',
            'contact_name' => 'Pat Lee',
            'contact_email' => 'pat@newco.test',
        ])
            ->assertCreated()
            ->assertJsonPath('company_name', 'New Co')
            ->assertJsonPath('due_days', 15)
            ->assertJsonPath('contacts.0.name', 'Pat Lee')
            ->assertJsonPath('contacts.0.is_primary', true);

        $this->getJson('/api/v1/clients/' . $this->client->id)
            ->assertOk()
            ->assertJsonPath('outstanding_balance', 275)
            ->assertJsonCount(1, 'recent_invoices')
            ->assertJsonPath('pricing_presets', []);
    }

    public function test_products_are_listed_and_the_pdf_downloads(): void
    {
        Sanctum::actingAs($this->user);
        Product::create(['name' => 'Hosting', 'unit' => 'month', 'default_unit_price' => 45, 'is_active' => true, 'sort_order' => 0]);
        Product::create(['name' => 'Retired', 'default_unit_price' => 1, 'is_active' => false, 'sort_order' => 1]);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.default_unit_price', 45);

        $id = $this->postJson('/api/v1/invoices', $this->invoicePayload())->json('id');
        $response = $this->get("/api/v1/invoices/{$id}/pdf", ['Accept' => 'application/pdf']);
        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', $response->headers->get('content-type'));
    }
}
