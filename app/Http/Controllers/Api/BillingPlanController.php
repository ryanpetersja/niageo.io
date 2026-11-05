<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BillingPlanResource;
use App\Http\Resources\Api\InvoiceResource;
use App\Models\BillingPlan;
use App\Services\BillingPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BillingPlanController extends Controller
{
    public function __construct(private BillingPlanService $plans) {}

    public function index(Request $request)
    {
        $plans = BillingPlan::with(['client', 'items'])
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByRaw('next_period_start IS NULL')
            ->orderBy('next_period_start')
            ->orderBy('name')
            ->get();

        return response()->json([
            'summary' => $this->summary(),
            'data' => BillingPlanResource::collection($plans)->resolve(),
        ]);
    }

    public function show(BillingPlan $billingPlan)
    {
        return new BillingPlanResource($this->detailed($billingPlan));
    }

    /** Create the next period's draft invoice now (or a specific period's). */
    public function generate(Request $request, BillingPlan $billingPlan)
    {
        $validated = $request->validate(['period_start' => 'nullable|date']);

        try {
            $invoice = $this->plans->generateNext(
                $billingPlan,
                ! empty($validated['period_start']) ? Carbon::parse($validated['period_start']) : null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $invoice->load(['client.contacts', 'lineItems', 'payments', 'statusHistory.changedBy']);

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function setStatus(Request $request, BillingPlan $billingPlan)
    {
        $validated = $request->validate(['status' => ['required', Rule::in(BillingPlan::STATUSES)]]);

        if ($validated['status'] === 'active' && ! $billingPlan->next_period_start) {
            return response()->json(['message' => 'Set the next period to invoice before resuming this plan.'], 422);
        }

        $billingPlan->update(['status' => $validated['status']]);

        return new BillingPlanResource($this->detailed($billingPlan->fresh()));
    }

    /** Counts for the list header and the dashboard. */
    public function summary(): array
    {
        $active = BillingPlan::with('items')->where('status', 'active')->get();

        return [
            'active_count' => $active->count(),
            'paused_count' => BillingPlan::where('status', 'paused')->count(),
            'monthly_value' => round((float) $active->sum('monthly_value'), 2),
            'due_now_count' => $active->filter(fn ($plan) => $this->plans->isDue($plan))->count(),
        ];
    }

    protected function detailed(BillingPlan $plan): BillingPlan
    {
        $plan->load(['client', 'items', 'invoices' => fn ($q) => $q->with('client')->limit(12)]);
        $plan->setAttribute('upcoming', $this->plans->upcomingPeriods($plan, 3));

        return $plan;
    }
}
