<?php

namespace App\Services;

use App\Models\BillingPlan;
use App\Models\Invoice;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns billing plans into invoices.
 *
 * Periods are anchored to the plan's start date and stepped by the billing cycle
 * (1 or 3 months, no day-of-month overflow). Each period gets one invoice, issued
 * `issue_days_before` days ahead of the period start and due `due_days` after issue.
 */
class BillingPlanService
{
    public function __construct(private InvoiceService $invoices) {}

    /* ---------------------------------------------------------------- periods */

    public function monthsPerPeriod(BillingPlan $plan): int
    {
        return BillingPlan::CYCLES[$plan->billing_cycle] ?? 1;
    }

    /** First period start (on the plan's anchored grid) strictly after the given date. */
    public function periodStartAfter(BillingPlan $plan, CarbonInterface $date): Carbon
    {
        $anchor = Carbon::instance($plan->starts_on)->startOfDay();
        $date = Carbon::instance($date)->startOfDay();
        $step = $this->monthsPerPeriod($plan);

        if ($anchor->gt($date)) {
            return $anchor;
        }

        $k = intdiv($anchor->diffInMonths($date), $step);
        $candidate = $anchor->copy()->addMonthsNoOverflow($k * $step);
        while ($candidate->lte($date)) {
            $k++;
            $candidate = $anchor->copy()->addMonthsNoOverflow($k * $step);
        }

        return $candidate;
    }

    /** Last day of the period that starts on the given date. */
    public function periodEnd(BillingPlan $plan, CarbonInterface $start): Carbon
    {
        return $this->periodStartAfter($plan, $start)->subDay();
    }

    /**
     * @return array{start: Carbon, end: Carbon, issue_date: Carbon, due_date: Carbon, label: string}
     */
    public function period(BillingPlan $plan, CarbonInterface $start): array
    {
        $start = Carbon::instance($start)->startOfDay();
        $end = $this->periodEnd($plan, $start);
        $issue = $start->copy()->subDays((int) $plan->issue_days_before);
        $due = $issue->copy()->addDays((int) $plan->due_days);

        return [
            'start' => $start,
            'end' => $end,
            'issue_date' => $issue,
            'due_date' => $due,
            'label' => $this->periodLabel($start, $end),
        ];
    }

    /** The next periods the plan will bill, starting from its next period. */
    public function upcomingPeriods(BillingPlan $plan, int $count = 3): array
    {
        $periods = [];
        $start = $plan->next_period_start ? Carbon::instance($plan->next_period_start) : null;

        while ($start && count($periods) < $count) {
            if ($plan->ends_on && $start->gt($plan->ends_on)) {
                break;
            }
            $periods[] = $this->period($plan, $start);
            $start = $this->periodStartAfter($plan, $start);
        }

        return $periods;
    }

    /** "September 2026", "Q4 2026 (Oct – Dec)", "Nov 2026 – Jan 2027" or "6 Sep 2026 – 5 Oct 2026". */
    public function periodLabel(CarbonInterface $start, CarbonInterface $end): string
    {
        $start = Carbon::instance($start)->startOfDay();
        $end = Carbon::instance($end)->startOfDay();
        $calendarAligned = $start->day === 1 && $end->isSameDay($end->copy()->endOfMonth());

        if (! $calendarAligned) {
            return $start->format('j M Y') . ' – ' . $end->format('j M Y');
        }

        $months = (int) $start->diffInMonths($end->copy()->addDay());
        if ($months === 1) {
            return $start->format('F Y');
        }
        if ($months === 3 && in_array($start->month, [1, 4, 7, 10], true)) {
            return 'Q' . $start->quarter . ' ' . $start->year . ' (' . $start->format('M') . ' – ' . $end->format('M') . ')';
        }
        if ($start->year === $end->year) {
            return $start->format('M') . ' – ' . $end->format('M Y');
        }

        return $start->format('M Y') . ' – ' . $end->format('M Y');
    }

    /* ------------------------------------------------------------ scheduling */

    /** Whether the plan's next invoice should be generated on the given day. */
    public function isDue(BillingPlan $plan, ?CarbonInterface $today = null): bool
    {
        if ($plan->status !== 'active' || ! $plan->next_period_start) {
            return false;
        }
        if ($plan->ends_on && Carbon::instance($plan->next_period_start)->gt($plan->ends_on)) {
            return false;
        }

        $today = ($today ? Carbon::instance($today) : now())->startOfDay();
        $issue = Carbon::instance($plan->next_period_start)->subDays((int) $plan->issue_days_before);

        return $issue->lte($today);
    }

    public function nextIssueDate(BillingPlan $plan): ?Carbon
    {
        if (! $plan->next_period_start) {
            return null;
        }

        return Carbon::instance($plan->next_period_start)->subDays((int) $plan->issue_days_before);
    }

    /** Move the plan on to the period after its current next period; ends the plan past ends_on. */
    public function advance(BillingPlan $plan): void
    {
        if (! $plan->next_period_start) {
            return;
        }

        $next = $this->periodStartAfter($plan, $plan->next_period_start);
        $attributes = ['next_period_start' => $next->toDateString()];

        if ($plan->ends_on && $next->gt($plan->ends_on)) {
            $attributes = ['next_period_start' => null, 'status' => 'ended'];
        }

        $plan->update($attributes);
    }

    /* ------------------------------------------------------------ generation */

    /**
     * Create the draft invoice for one period (the plan's next period by default).
     *
     * @throws \RuntimeException when the period cannot be invoiced
     */
    public function generateNext(BillingPlan $plan, ?CarbonInterface $periodStart = null): Invoice
    {
        $plan->loadMissing(['items', 'client']);

        if ($plan->items->isEmpty()) {
            throw new \RuntimeException('The plan has no line items to invoice.');
        }

        $start = $periodStart
            ? Carbon::instance($periodStart)->startOfDay()
            : ($plan->next_period_start ? Carbon::instance($plan->next_period_start) : null);

        if (! $start) {
            throw new \RuntimeException('The plan has no next period to invoice.');
        }
        if ($plan->ends_on && $start->gt($plan->ends_on)) {
            throw new \RuntimeException('That period starts after the plan ends on ' . $plan->ends_on->format('j M Y') . '.');
        }
        if ($plan->invoices()->whereDate('period_start', $start->toDateString())->exists()) {
            throw new \RuntimeException('An invoice already exists for the period starting ' . $start->format('j M Y') . '.');
        }

        $period = $this->period($plan, $start);

        return DB::transaction(function () use ($plan, $period, $start) {
            $invoice = $this->invoices->create([
                'client_id' => $plan->client_id,
                'created_by' => auth()->id() ?? $plan->created_by,
                'title' => $this->buildTitle($plan, $period),
                'issue_date' => $period['issue_date']->toDateString(),
                'due_date' => $period['due_date']->toDateString(),
                'tax_rate' => $plan->tax_rate,
                'notes' => $this->buildNotes($plan, $period),
                'internal_notes' => $plan->internal_notes,
                'billing_plan_id' => $plan->id,
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
            ]);

            foreach ($plan->items as $index => $item) {
                $this->invoices->addLineItem($invoice, [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'sort_order' => $index,
                ]);
            }

            // Generating the scheduled period moves the schedule on; ad-hoc periods leave it alone.
            if ($plan->next_period_start && $start->isSameDay($plan->next_period_start)) {
                $this->advance($plan);
            }

            return $invoice->fresh(['lineItems']);
        });
    }

    /**
     * Generate every invoice whose issue date has arrived, across all active plans.
     *
     * @return Invoice[]
     */
    public function generateDue(?CarbonInterface $today = null, int $maxPerPlan = 3): array
    {
        $today = ($today ? Carbon::instance($today) : now())->startOfDay();
        $created = [];

        $plans = BillingPlan::with(['items', 'client'])
            ->where('status', 'active')
            ->whereNotNull('next_period_start')
            ->orderBy('next_period_start')
            ->get();

        foreach ($plans as $plan) {
            for ($i = 0; $i < $maxPerPlan && $this->isDue($plan, $today); $i++) {
                // A period invoiced by hand already: skip it rather than stall the schedule.
                if ($plan->invoices()->whereDate('period_start', $plan->next_period_start->toDateString())->exists()) {
                    $this->advance($plan);
                    continue;
                }

                try {
                    $created[] = $this->generateNext($plan);
                } catch (\RuntimeException $e) {
                    Log::warning('Recurring invoice skipped', ['plan' => $plan->id, 'reason' => $e->getMessage()]);
                    break;
                }
            }
        }

        return $created;
    }

    /* --------------------------------------------------------------- content */

    public function buildTitle(BillingPlan $plan, array $period): string
    {
        $template = trim((string) $plan->title_template) ?: BillingPlan::DEFAULT_TITLE_TEMPLATE;

        return trim(strtr($template, [
            '{plan}' => $plan->name,
            '{client}' => $plan->client?->company_name ?? '',
            '{period}' => $period['label'],
            '{month}' => $period['start']->format('F Y'),
            '{year}' => $period['start']->format('Y'),
        ]));
    }

    public function buildNotes(BillingPlan $plan, array $period): string
    {
        $line = 'Billing period: ' . $period['start']->format('j M Y') . ' – ' . $period['end']->format('j M Y') . '.';
        $notes = trim((string) $plan->notes);

        return $notes === '' ? $line : $line . "\n\n" . $notes;
    }
}
