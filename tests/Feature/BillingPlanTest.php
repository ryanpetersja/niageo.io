<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::create(['company_name' => 'Initech', 'billing_terms' => 'net_30', 'is_active' => true]);
    }

    private function makePlan(array $attributes = [], array $items = null): BillingPlan
    {
        $plan = BillingPlan::create(array_merge([
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'name' => 'Hosting package',
            'billing_cycle' => 'monthly',
            'starts_on' => '2026-10-01',
            'next_period_start' => '2026-10-01',
            'issue_days_before' => 0,
            'due_days' => 30,
            'tax_rate' => 15,
            'status' => 'active',
        ], $attributes));

        foreach ($items ?? [
            ['description' => 'Website hosting', 'quantity' => 1, 'unit_price' => 120],
            ['description' => 'Daily database backups', 'quantity' => 1, 'unit_price' => 40],
        ] as $index => $item) {
            $plan->items()->create($item + ['sort_order' => $index]);
        }

        return $plan->fresh(['items']);
    }

    public function test_a_plan_can_be_created_from_the_form_with_products(): void
    {
        $product = Product::create(['name' => 'Website hosting', 'description' => 'Managed hosting', 'default_unit_price' => 100]);

        $response = $this->actingAs($this->user)->post(route('billing-plans.store'), [
            'client_id' => $this->client->id,
            'name' => 'Standard package',
            'billing_cycle' => 'quarterly',
            'starts_on' => '2026-10-01',
            'issue_days_before' => 0,
            'due_days' => 30,
            'tax_rate' => 0,
            'items' => [
                ['product_id' => $product->id, 'description' => 'Managed hosting', 'quantity' => 3, 'unit_price' => 150],
                ['product_id' => '', 'description' => 'Weekly backups', 'quantity' => 3, 'unit_price' => 25],
            ],
        ]);

        $plan = BillingPlan::firstOrFail();
        $response->assertRedirect(route('billing-plans.show', $plan));

        $this->assertSame('2026-10-01', $plan->next_period_start->toDateString());
        $this->assertSame('active', $plan->status);
        $this->assertCount(2, $plan->items);
        $this->assertSame($product->id, $plan->items[0]->product_id);
        $this->assertEquals(525.0, $plan->period_total);
        $this->assertEquals(175.0, $plan->monthly_value);

        $this->actingAs($this->user)->get(route('billing-plans.show', $plan))
            ->assertOk()
            ->assertSee('Standard package')
            ->assertSee('Q4 2026 (Oct – Dec)')
            ->assertSee('Standard package — Q4 2026 (Oct – Dec)');
    }

    public function test_generating_creates_a_draft_invoice_for_the_next_period_and_advances_the_schedule(): void
    {
        $plan = $this->makePlan(['notes' => 'Payable by bank transfer.']);

        $response = $this->actingAs($this->user)->post(route('billing-plans.generate', $plan));

        $invoice = Invoice::with('lineItems')->firstOrFail();
        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertSame('draft', $invoice->status);
        $this->assertSame($this->client->id, $invoice->client_id);
        $this->assertSame($plan->id, $invoice->billing_plan_id);
        $this->assertSame('Hosting package — October 2026', $invoice->title);
        $this->assertSame('2026-10-01', $invoice->issue_date->toDateString());
        $this->assertSame('2026-10-31', $invoice->due_date->toDateString());
        $this->assertSame('2026-10-01', $invoice->period_start->toDateString());
        $this->assertSame('2026-10-31', $invoice->period_end->toDateString());
        $this->assertSame("Billing period: 1 Oct 2026 – 31 Oct 2026.\n\nPayable by bank transfer.", $invoice->notes);
        $this->assertCount(2, $invoice->lineItems);
        $this->assertEquals(160.0, (float) $invoice->subtotal);
        $this->assertEquals(24.0, (float) $invoice->tax_amount);
        $this->assertEquals(184.0, (float) $invoice->total);

        $this->assertSame('2026-11-01', $plan->fresh()->next_period_start->toDateString());

        // The same period cannot be invoiced twice.
        $this->actingAs($this->user)
            ->post(route('billing-plans.generate', $plan), ['period_start' => '2026-10-01'])
            ->assertRedirect(route('billing-plans.show', $plan))
            ->assertSessionHas('error');
        $this->assertSame(1, Invoice::count());

        // An ad-hoc period does not move the schedule.
        $this->actingAs($this->user)->post(route('billing-plans.generate', $plan), ['period_start' => '2026-12-01']);
        $this->assertSame(2, Invoice::count());
        $this->assertSame('2026-11-01', $plan->fresh()->next_period_start->toDateString());
    }

    public function test_the_daily_command_generates_only_invoices_whose_issue_date_has_arrived(): void
    {
        $due = $this->makePlan(['name' => 'Due today', 'starts_on' => '2026-09-01', 'next_period_start' => '2026-09-01']);
        $early = $this->makePlan(['name' => 'Issued early', 'starts_on' => '2026-10-01', 'next_period_start' => '2026-10-01', 'issue_days_before' => 7]);
        $future = $this->makePlan(['name' => 'Not yet', 'starts_on' => '2026-10-01', 'next_period_start' => '2026-10-01']);
        $paused = $this->makePlan(['name' => 'Paused', 'starts_on' => '2026-09-01', 'next_period_start' => '2026-09-01', 'status' => 'paused']);
        $behind = $this->makePlan(['name' => 'Catching up', 'starts_on' => '2026-05-01', 'next_period_start' => '2026-05-01']);

        $this->artisan('billing:generate', ['--date' => '2026-09-25'])
            ->expectsOutputToContain('Generated 5 recurring invoice(s).')
            ->assertSuccessful();

        $this->assertSame(1, $due->invoices()->count());
        $this->assertSame('2026-10-01', $due->fresh()->next_period_start->toDateString());
        $this->assertSame(1, $early->invoices()->count());
        $this->assertSame('2026-09-24', $early->invoices()->first()->issue_date->toDateString());
        $this->assertSame(0, $future->invoices()->count());
        $this->assertSame(0, $paused->invoices()->count());

        // At most three periods are caught up per run, oldest first.
        $this->assertSame(3, $behind->invoices()->count());
        $this->assertSame(['2026-07-01', '2026-06-01', '2026-05-01'], $behind->invoices()->pluck('period_start')->map->toDateString()->all());
        $this->assertSame('2026-08-01', $behind->fresh()->next_period_start->toDateString());

        // Running again on the same day only continues the catch-up.
        $this->artisan('billing:generate', ['--date' => '2026-09-25'])->expectsOutputToContain('Generated 2 recurring invoice(s).');
        $this->assertSame(5, $behind->invoices()->count());
        $this->assertSame('2026-10-01', $behind->fresh()->next_period_start->toDateString());
    }

    public function test_plans_end_after_their_last_period(): void
    {
        $plan = $this->makePlan(['billing_cycle' => 'quarterly', 'ends_on' => '2026-12-31']);

        $this->actingAs($this->user)->post(route('billing-plans.generate', $plan));

        $plan->refresh();
        $this->assertSame('ended', $plan->status);
        $this->assertNull($plan->next_period_start);
        $this->assertSame('Q4 2026 (Oct – Dec)', app(\App\Services\BillingPlanService::class)->periodLabel(
            $plan->invoices()->first()->period_start,
            $plan->invoices()->first()->period_end,
        ));

        $this->actingAs($this->user)
            ->post(route('billing-plans.status', $plan), ['status' => 'active'])
            ->assertSessionHas('error');
    }

    public function test_pausing_and_editing_the_schedule(): void
    {
        $plan = $this->makePlan();

        $this->actingAs($this->user)->post(route('billing-plans.status', $plan), ['status' => 'paused'])->assertSessionHas('success');
        $this->assertSame('paused', $plan->fresh()->status);

        $this->actingAs($this->user)->put(route('billing-plans.update', $plan), [
            'client_id' => $this->client->id,
            'name' => 'Hosting package v2',
            'billing_cycle' => 'monthly',
            'next_period_start' => '2027-01-01',
            'status' => 'active',
            'issue_days_before' => 3,
            'due_days' => 14,
            'tax_rate' => 0,
            'items' => [['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 200]],
        ])->assertRedirect(route('billing-plans.show', $plan));

        $plan->refresh();
        $this->assertSame('Hosting package v2', $plan->name);
        $this->assertSame('active', $plan->status);
        $this->assertSame('2027-01-01', $plan->next_period_start->toDateString());
        $this->assertCount(1, $plan->items);

        $this->actingAs($this->user)->delete(route('billing-plans.destroy', $plan))->assertRedirect(route('billing-plans.index'));
        $this->assertDatabaseMissing('billing_plans', ['id' => $plan->id]);
    }

    public function test_invoice_list_can_be_filtered_by_source(): void
    {
        $plan = $this->makePlan();
        $this->actingAs($this->user)->post(route('billing-plans.generate', $plan));
        Invoice::create([
            'invoice_number' => 'INV-MANUAL-1', 'client_id' => $this->client->id, 'created_by' => $this->user->id,
            'status' => 'draft', 'issue_date' => '2026-10-01', 'due_date' => '2026-10-31',
        ]);

        $this->actingAs($this->user)->get(route('invoices.index', ['source' => 'recurring']))
            ->assertOk()->assertSee('Hosting package — October 2026')->assertDontSee('INV-MANUAL-1');
        $this->actingAs($this->user)->get(route('invoices.index', ['source' => 'manual']))
            ->assertOk()->assertSee('INV-MANUAL-1')->assertDontSee('Hosting package — October 2026');
    }

    public function test_products_can_be_managed(): void
    {
        $this->actingAs($this->user)->post(route('products.store'), ['name' => 'Email hosting', 'description' => 'Mailboxes', 'unit' => 'month', 'default_unit_price' => 12.5])
            ->assertRedirect(route('products.index'));
        $product = Product::firstOrFail();
        $this->assertTrue($product->is_active);

        $this->actingAs($this->user)->put(route('products.update', $product), ['name' => 'Email hosting', 'default_unit_price' => 15, 'is_active' => 0])
            ->assertRedirect(route('products.index'));
        $this->assertFalse($product->fresh()->is_active);
        $this->assertEquals(15.0, (float) $product->fresh()->default_unit_price);

        $this->actingAs($this->user)->get(route('products.index'))->assertOk()->assertSee('Email hosting');

        $this->actingAs($this->user)->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));
        $this->assertSame(0, Product::count());
    }
}
