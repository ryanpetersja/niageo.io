<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BillingPlanResource;
use App\Http\Resources\Api\InvoiceResource;
use App\Http\Resources\Api\MonitoredEndpointResource;
use App\Http\Resources\Api\SubscriptionBillResource;
use App\Models\BillingPlan;
use App\Models\Invoice;
use App\Models\MonitoredEndpoint;
use App\Models\Payment;
use App\Models\SubscriptionBill;
use App\Services\BillingPlanService;
use App\Services\DashboardService;
use App\Services\UptimeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The pipeline screen of the mobile app: headline metrics, invoices by status, recent and
 * overdue invoices, plus what needs attention operationally (sites down, bills due, plans due).
 */
class DashboardController extends Controller
{
    public const PIPELINE_STATUSES = ['draft', 'sent', 'overdue', 'paid', 'cancelled'];

    public function __invoke(DashboardService $dashboard, UptimeService $uptime, BillingPlanService $plans)
    {
        $rows = Invoice::query()
            ->select('status', DB::raw('COUNT(*) as invoices'), DB::raw('SUM(total) as total'), DB::raw('SUM(total - amount_paid) as balance_due'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pipeline = collect(self::PIPELINE_STATUSES)->map(fn (string $status) => [
            'status' => $status,
            'count' => (int) ($rows[$status]->invoices ?? 0),
            'total' => round((float) ($rows[$status]->total ?? 0), 2),
            'balance_due' => in_array($status, ['draft', 'sent', 'overdue'], true)
                ? round(max(0.0, (float) ($rows[$status]->balance_due ?? 0)), 2)
                : 0.0,
        ])->values();

        $month = now()->startOfMonth();

        return response()->json([
            'metrics' => $dashboard->getMetrics(),
            'pipeline' => $pipeline,
            'collected_this_month' => $this->collected($month, $month->copy()->endOfMonth()),
            'collected_last_month' => $this->collected($month->copy()->subMonthNoOverflow(), $month->copy()->subMonthNoOverflow()->endOfMonth()),
            'recent_invoices' => InvoiceResource::collection(Invoice::with('client')->latest('updated_at')->limit(8)->get())->resolve(),
            'overdue_invoices' => InvoiceResource::collection(
                Invoice::with('client')
                    ->where(fn ($q) => $q->where('status', 'overdue')->orWhere(fn ($q) => $q->where('status', 'sent')->whereDate('due_date', '<', today())))
                    ->orderBy('due_date')
                    ->limit(10)
                    ->get()
            )->resolve(),
            'operations' => $this->operations($uptime, $plans),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    protected function operations(UptimeService $uptime, BillingPlanService $plans): array
    {
        $troubled = MonitoredEndpoint::with('client')
            ->where('is_active', true)
            ->whereIn('current_status', ['down', 'degraded'])
            ->get()
            ->sortBy(fn ($e) => sprintf('%d-%s', UptimeController::STATUS_RANK[$e->current_status] ?? 3, strtolower($e->name)))
            ->values();

        SubscriptionBill::active()->unpaid()->get()->each->refreshStatus();
        $alerts = SubscriptionBill::active()
            ->whereIn('status', ['overdue', 'due_soon'])
            ->get()
            ->sortBy(fn ($b) => sprintf('%d-%s', SubscriptionBillController::STATUS_RANK[$b->status] ?? 9, $b->next_due_date?->toDateString() ?? '9999'))
            ->values();

        $activePlans = BillingPlan::with(['client', 'items'])->where('status', 'active')->whereNotNull('next_period_start')->get();
        $duePlans = $activePlans->filter(fn ($plan) => $plans->isDue($plan))->values();
        $nextPlan = $activePlans->sortBy(fn ($plan) => $plans->nextIssueDate($plan)?->toDateString() ?? '9999')->first();

        return [
            'uptime' => $uptime->getSummary() + [
                'troubled' => MonitoredEndpointResource::collection($troubled)->resolve(),
            ],
            'subscriptions' => SubscriptionBillController::summary() + [
                'alerts' => SubscriptionBillResource::collection($alerts)->resolve(),
            ],
            'billing_plans' => [
                'active_count' => $activePlans->count(),
                'due_now_count' => $duePlans->count(),
                'monthly_value' => round((float) $activePlans->sum('monthly_value'), 2),
                'due' => BillingPlanResource::collection($duePlans)->resolve(),
                'next' => $nextPlan ? (new BillingPlanResource($nextPlan))->resolve() : null,
            ],
        ];
    }

    protected function collected(Carbon $from, Carbon $to): float
    {
        return round((float) Payment::whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])->sum('amount'), 2);
    }
}
