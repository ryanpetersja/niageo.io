<?php

namespace Tests\Unit;

use App\Models\BillingPlan;
use App\Models\Client;
use App\Services\BillingPlanService;
use Carbon\Carbon;
use Tests\TestCase;

class BillingPlanPeriodsTest extends TestCase
{
    private BillingPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BillingPlanService::class);
    }

    private function plan(array $attributes = []): BillingPlan
    {
        $plan = new BillingPlan(array_merge([
            'name' => 'Hosting package',
            'billing_cycle' => 'monthly',
            'starts_on' => '2026-01-01',
            'next_period_start' => '2026-01-01',
            'issue_days_before' => 0,
            'due_days' => 30,
            'tax_rate' => 0,
            'status' => 'active',
        ], $attributes));
        $plan->setRelation('client', new Client(['company_name' => 'Acme Corp']));

        return $plan;
    }

    public function test_monthly_periods_follow_calendar_months_when_anchored_on_the_first(): void
    {
        $plan = $this->plan();

        $this->assertSame('2026-01-31', $this->service->periodEnd($plan, Carbon::parse('2026-01-01'))->toDateString());
        $this->assertSame('2026-02-01', $this->service->periodStartAfter($plan, Carbon::parse('2026-01-01'))->toDateString());
        $this->assertSame('2026-02-28', $this->service->periodEnd($plan, Carbon::parse('2026-02-01'))->toDateString());
        $this->assertSame('January 2026', $this->service->period($plan, Carbon::parse('2026-01-01'))['label']);
        // A date inside a period resolves to the next grid start, not "one month later".
        $this->assertSame('2026-02-01', $this->service->periodStartAfter($plan, Carbon::parse('2026-01-15'))->toDateString());
    }

    public function test_quarterly_periods_and_labels(): void
    {
        $plan = $this->plan(['billing_cycle' => 'quarterly', 'starts_on' => '2026-10-01', 'next_period_start' => '2026-10-01']);
        $period = $this->service->period($plan, Carbon::parse('2026-10-01'));

        $this->assertSame('2026-12-31', $period['end']->toDateString());
        $this->assertSame('Q4 2026 (Oct – Dec)', $period['label']);
        $this->assertSame('2027-01-01', $this->service->periodStartAfter($plan, $period['start'])->toDateString());

        $offQuarter = $this->plan(['billing_cycle' => 'quarterly', 'starts_on' => '2026-11-01']);
        $this->assertSame('Nov 2026 – Jan 2027', $this->service->period($offQuarter, Carbon::parse('2026-11-01'))['label']);
    }

    public function test_periods_anchored_late_in_the_month_do_not_overflow(): void
    {
        $plan = $this->plan(['starts_on' => '2026-01-31', 'next_period_start' => '2026-01-31']);

        $this->assertSame('2026-02-28', $this->service->periodStartAfter($plan, Carbon::parse('2026-01-31'))->toDateString());
        $this->assertSame('2026-03-31', $this->service->periodStartAfter($plan, Carbon::parse('2026-02-28'))->toDateString());
        $this->assertSame('2026-04-30', $this->service->periodStartAfter($plan, Carbon::parse('2026-03-31'))->toDateString());
        $this->assertSame('31 Jan 2026 – 27 Feb 2026', $this->service->period($plan, Carbon::parse('2026-01-31'))['label']);
    }

    public function test_issue_and_due_dates_follow_the_plan_terms(): void
    {
        $plan = $this->plan(['starts_on' => '2026-10-01', 'issue_days_before' => 7, 'due_days' => 30]);
        $period = $this->service->period($plan, Carbon::parse('2026-10-01'));

        $this->assertSame('2026-09-24', $period['issue_date']->toDateString());
        $this->assertSame('2026-10-24', $period['due_date']->toDateString());

        $onStart = $this->plan(['starts_on' => '2026-10-01']);
        $this->assertSame('2026-10-31', $this->service->period($onStart, Carbon::parse('2026-10-01'))['due_date']->toDateString());
    }

    public function test_is_due_respects_issue_offset_status_and_end_date(): void
    {
        $plan = $this->plan(['starts_on' => '2026-10-01', 'next_period_start' => '2026-10-01']);

        $this->assertFalse($this->service->isDue($plan, Carbon::parse('2026-09-30')));
        $this->assertTrue($this->service->isDue($plan, Carbon::parse('2026-10-01')));
        $this->assertTrue($this->service->isDue($plan, Carbon::parse('2026-10-15')));

        $early = $this->plan(['starts_on' => '2026-10-01', 'next_period_start' => '2026-10-01', 'issue_days_before' => 7]);
        $this->assertTrue($this->service->isDue($early, Carbon::parse('2026-09-24')));
        $this->assertFalse($this->service->isDue($early, Carbon::parse('2026-09-23')));

        $this->assertFalse($this->service->isDue($this->plan(['next_period_start' => '2026-10-01', 'status' => 'paused']), Carbon::parse('2026-10-05')));
        $this->assertFalse($this->service->isDue($this->plan(['next_period_start' => '2026-10-01', 'ends_on' => '2026-09-30']), Carbon::parse('2026-10-05')));
        $this->assertFalse($this->service->isDue($this->plan(['next_period_start' => null]), Carbon::parse('2026-10-05')));
    }

    public function test_upcoming_periods_stop_at_the_end_date(): void
    {
        $plan = $this->plan(['starts_on' => '2026-10-01', 'next_period_start' => '2026-10-01', 'ends_on' => '2026-11-30']);
        $labels = array_map(fn ($p) => $p['label'], $this->service->upcomingPeriods($plan, 5));

        $this->assertSame(['October 2026', 'November 2026'], $labels);
    }

    public function test_title_and_notes_templates(): void
    {
        $plan = $this->plan(['starts_on' => '2026-10-01', 'notes' => 'Thank you for your business.']);
        $period = $this->service->period($plan, Carbon::parse('2026-10-01'));

        $this->assertSame('Hosting package — October 2026', $this->service->buildTitle($plan, $period));
        $this->assertSame("Billing period: 1 Oct 2026 – 31 Oct 2026.\n\nThank you for your business.", $this->service->buildNotes($plan, $period));

        $plan->title_template = '{client} hosting for {month}';
        $this->assertSame('Acme Corp hosting for October 2026', $this->service->buildTitle($plan, $period));
    }
}
