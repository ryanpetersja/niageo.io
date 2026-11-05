<?php

namespace Tests\Feature\Api;

use App\Models\BillingPlan;
use App\Models\Client;
use App\Models\MonitoredEndpoint;
use App\Models\SubscriptionBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-05 10:00:00');
        $this->user = User::factory()->create();
        $this->client = Client::create(['company_name' => 'Campus Elite', 'billing_terms' => 'net_30', 'is_active' => true]);
        Sanctum::actingAs($this->user);
    }

    private function endpoint(string $name, string $status, array $extra = []): MonitoredEndpoint
    {
        return MonitoredEndpoint::create(array_merge([
            'client_id' => $this->client->id,
            'name' => $name,
            'url' => 'https://' . strtolower(str_replace(' ', '-', $name)) . '.test/health',
            'check_interval_minutes' => 5,
            'timeout_seconds' => 10,
            'degraded_threshold_ms' => 2000,
            'current_status' => $status,
            'is_active' => true,
        ], $extra));
    }

    private function bill(string $name, string $dueDate, array $extra = []): SubscriptionBill
    {
        return SubscriptionBill::create(array_merge([
            'service_name' => $name,
            'category' => 'hosting',
            'amount' => 40,
            'billing_cycle' => 'monthly',
            'next_due_date' => $dueDate,
            'status' => 'upcoming',
            'is_active' => true,
            'auto_renew' => true,
        ], $extra));
    }

    private function plan(string $name, ?string $nextPeriodStart, array $items = [['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 450]]): BillingPlan
    {
        $plan = BillingPlan::create([
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'name' => $name,
            'billing_cycle' => 'monthly',
            'starts_on' => '2026-01-01',
            'next_period_start' => $nextPeriodStart,
            'issue_days_before' => 0,
            'due_days' => 14,
            'tax_rate' => 0,
            'status' => 'active',
        ]);
        foreach ($items as $index => $item) {
            $plan->items()->create($item + ['sort_order' => $index]);
        }

        return $plan;
    }

    public function test_uptime_list_detail_and_manual_check(): void
    {
        $up = $this->endpoint('Snow Landing', 'up', ['last_response_time_ms' => 180]);
        $down = $this->endpoint('Opportunity Network', 'down', ['last_error_message' => 'HTTP 503']);
        Http::fake(['opportunity-network.test/*' => Http::response('ok', 200)]);

        $this->getJson('/api/v1/uptime')
            ->assertOk()
            ->assertJsonPath('summary.up', 1)
            ->assertJsonPath('summary.down', 1)
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('data.0.name', 'Opportunity Network')
            ->assertJsonPath('data.0.status', 'down')
            ->assertJsonPath('data.0.client.company_name', 'Campus Elite')
            ->assertJsonPath('data.1.last_response_time_ms', 180);

        $this->postJson("/api/v1/uptime/{$down->id}/check")
            ->assertOk()
            ->assertJsonPath('status', 'up')
            ->assertJsonPath('last_status_code', 200)
            ->assertJsonPath('last_error_message', null)
            ->assertJsonCount(1, 'recent_checks')
            ->assertJsonPath('recent_checks.0.status', 'up')
            ->assertJsonPath('stats.checks_24h', 1)
            ->assertJsonPath('stats.uptime_24h', 100);

        $this->getJson("/api/v1/uptime/{$up->id}")
            ->assertOk()
            ->assertJsonPath('name', 'Snow Landing')
            ->assertJsonPath('recent_checks', [])
            ->assertJsonPath('stats.uptime_24h', null);
    }

    public function test_subscriptions_list_refreshes_statuses_and_marks_paid(): void
    {
        $overdue = $this->bill('Cloudflare', '2026-11-01');
        $dueSoon = $this->bill('SendGrid', '2026-11-08', ['amount' => 89.95, 'category' => 'email']);
        $this->bill('Google Workspace', '2026-12-20', ['amount' => 300, 'billing_cycle' => 'quarterly', 'auto_renew' => false]);
        $this->bill('Old thing', '2026-11-01', ['is_active' => false]);

        $response = $this->getJson('/api/v1/subscriptions')
            ->assertOk()
            ->assertJsonPath('summary.active_count', 3)
            ->assertJsonPath('summary.overdue_count', 1)
            ->assertJsonPath('summary.overdue_total', 40)
            ->assertJsonPath('summary.due_soon_count', 1)
            ->assertJsonPath('summary.total_monthly', 229.95)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.service_name', 'Cloudflare')
            ->assertJsonPath('data.0.status', 'overdue')
            ->assertJsonPath('data.0.days_until_due', -4)
            ->assertJsonPath('data.1.status', 'due_soon')
            ->assertJsonPath('data.2.monthly_equivalent', 100);

        $this->assertSame('overdue', $overdue->fresh()->status, 'statuses are refreshed on the way out');

        $this->postJson("/api/v1/subscriptions/{$overdue->id}/mark-paid")
            ->assertOk()
            ->assertJsonPath('status', 'upcoming')
            ->assertJsonPath('next_due_date', '2026-12-01')
            ->assertJsonPath('days_until_due', 26);
        $this->assertNotNull($overdue->fresh()->last_paid_at);

        $this->postJson("/api/v1/subscriptions/{$dueSoon->id}/mark-paid")->assertOk()->assertJsonPath('status', 'upcoming')->assertJsonPath('next_due_date', '2026-12-08');
    }

    public function test_billing_plans_list_detail_generate_and_status(): void
    {
        $due = $this->plan('Monthly maintenance', '2026-11-01');
        $later = $this->plan('Quarterly bundle', '2026-12-01');
        $empty = $this->plan('Empty plan', '2026-11-01', []);

        $this->getJson('/api/v1/billing-plans')
            ->assertOk()
            ->assertJsonPath('summary.active_count', 3)
            ->assertJsonPath('summary.due_now_count', 2)
            ->assertJsonPath('summary.monthly_value', 900)
            ->assertJsonPath('data.0.name', 'Empty plan')
            ->assertJsonPath('data.1.name', 'Monthly maintenance')
            ->assertJsonPath('data.1.is_due', true)
            ->assertJsonPath('data.1.period_total', 450)
            ->assertJsonPath('data.1.next_issue_date', '2026-11-01')
            ->assertJsonPath('data.2.is_due', false);

        $this->getJson("/api/v1/billing-plans/{$due->id}")
            ->assertOk()
            ->assertJsonCount(3, 'upcoming')
            ->assertJsonPath('upcoming.0.label', 'November 2026')
            ->assertJsonPath('upcoming.0.due_date', '2026-11-15')
            ->assertJsonPath('upcoming.1.start', '2026-12-01')
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('invoices', []);

        $invoice = $this->postJson("/api/v1/billing-plans/{$due->id}/generate")
            ->assertCreated()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('is_recurring', true)
            ->assertJsonPath('total', 450)
            ->assertJsonPath('client.company_name', 'Campus Elite')
            ->assertJsonCount(1, 'line_items');
        $this->assertStringContainsString('November 2026', $invoice->json('title'));
        $this->assertSame('2026-12-01', $due->fresh()->next_period_start->toDateString(), 'the schedule moves on');

        $this->getJson("/api/v1/billing-plans/{$due->id}")->assertOk()->assertJsonCount(1, 'invoices')->assertJsonPath('is_due', false);

        $this->postJson("/api/v1/billing-plans/{$empty->id}/generate")
            ->assertStatus(422)
            ->assertJsonPath('message', 'The plan has no line items to invoice.');

        $this->postJson("/api/v1/billing-plans/{$later->id}/status", ['status' => 'paused'])->assertOk()->assertJsonPath('status', 'paused');
        $this->postJson("/api/v1/billing-plans/{$later->id}/status", ['status' => 'bogus'])->assertStatus(422);

        $later->update(['next_period_start' => null]);
        $this->postJson("/api/v1/billing-plans/{$later->id}/status", ['status' => 'active'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Set the next period to invoice before resuming this plan.');
    }

    public function test_dashboard_carries_an_operations_summary(): void
    {
        $this->endpoint('Snow Landing', 'up');
        $this->endpoint('Portal', 'down');
        $this->endpoint('Retired', 'down', ['is_active' => false]);
        $this->bill('Cloudflare', '2026-11-01');
        $this->bill('Forge', '2026-11-30', ['amount' => 39]);
        $this->plan('Monthly maintenance', '2026-11-01');
        $this->plan('Later plan', '2026-12-01');

        $response = $this->getJson('/api/v1/dashboard')->assertOk();
        $ops = $response->json('operations');

        $this->assertSame(1, $ops['uptime']['down']);
        $this->assertSame(1, $ops['uptime']['up']);
        $this->assertCount(1, $ops['uptime']['troubled']);
        $this->assertSame('Portal', $ops['uptime']['troubled'][0]['name']);
        $this->assertSame(1, $ops['subscriptions']['overdue_count']);
        $this->assertSame(40.0, (float) $ops['subscriptions']['overdue_total']);
        $this->assertCount(1, $ops['subscriptions']['alerts']);
        $this->assertSame(2, $ops['billing_plans']['active_count']);
        $this->assertSame(1, $ops['billing_plans']['due_now_count']);
        $this->assertSame('Monthly maintenance', $ops['billing_plans']['due'][0]['name']);
        $this->assertSame('Monthly maintenance', $ops['billing_plans']['next']['name']);
        $this->assertSame('2026-11-01', $ops['billing_plans']['next']['next_issue_date']);
    }
}
