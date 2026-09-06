<?php

namespace App\Http\Controllers;

use App\Models\BillingPlan;
use App\Models\Client;
use App\Models\Product;
use App\Services\BillingPlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BillingPlanController extends Controller
{
    public function __construct(private BillingPlanService $plans) {}

    public function index(Request $request)
    {
        $query = BillingPlan::with(['client', 'items']);

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $billingPlans = $query->orderByRaw('next_period_start IS NULL')->orderBy('next_period_start')->orderBy('name')->get();
        $active = BillingPlan::with('items')->where('status', 'active')->get();

        $summary = [
            'active' => $active->count(),
            'monthly_value' => $active->sum('monthly_value'),
            'due_now' => $active->filter(fn ($plan) => $this->plans->isDue($plan))->count(),
            'paused' => BillingPlan::where('status', 'paused')->count(),
        ];

        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $dueMap = $billingPlans->mapWithKeys(fn ($plan) => [$plan->id => $this->plans->isDue($plan)]);

        return view('billing-plans.index', compact('billingPlans', 'clients', 'summary', 'dueMap'));
    }

    public function create(Request $request)
    {
        $billingPlan = new BillingPlan([
            'billing_cycle' => 'monthly',
            'starts_on' => now()->addMonthNoOverflow()->startOfMonth(),
            'issue_days_before' => 0,
            'due_days' => 30,
            'tax_rate' => 0,
            'status' => 'active',
        ]);
        if ($request->input('client_id')) {
            $billingPlan->client_id = (int) $request->input('client_id');
        }

        return view('billing-plans.create', $this->formData($billingPlan));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        $billingPlan = DB::transaction(function () use ($validated) {
            $plan = BillingPlan::create([
                ...$this->planAttributes($validated),
                'starts_on' => $validated['starts_on'],
                'next_period_start' => $validated['starts_on'],
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
            $this->syncItems($plan, $validated['items']);

            return $plan;
        });

        return redirect()->route('billing-plans.show', $billingPlan)->with('success', 'Billing plan created. Invoices will be generated automatically from ' . $billingPlan->starts_on->format('j M Y') . '.');
    }

    public function show(BillingPlan $billingPlan)
    {
        $billingPlan->load(['client', 'items.product', 'invoices', 'creator']);
        $upcoming = $this->plans->upcomingPeriods($billingPlan, 3);

        return view('billing-plans.show', [
            'billingPlan' => $billingPlan,
            'upcoming' => $upcoming,
            'isDue' => $this->plans->isDue($billingPlan),
            'nextIssueDate' => $this->plans->nextIssueDate($billingPlan),
            'nextTitle' => $upcoming !== [] ? $this->plans->buildTitle($billingPlan, $upcoming[0]) : null,
            'invoiceLabels' => $billingPlan->invoices->mapWithKeys(fn ($invoice) => [
                $invoice->id => $invoice->period_start && $invoice->period_end
                    ? $this->plans->periodLabel($invoice->period_start, $invoice->period_end)
                    : null,
            ]),
        ]);
    }

    public function edit(BillingPlan $billingPlan)
    {
        $billingPlan->load('items');

        return view('billing-plans.edit', $this->formData($billingPlan));
    }

    public function update(Request $request, BillingPlan $billingPlan)
    {
        $validated = $this->validatePlan($request, editing: true);

        DB::transaction(function () use ($billingPlan, $validated) {
            $attributes = $this->planAttributes($validated);
            $attributes['next_period_start'] = $validated['next_period_start'] ?? null;
            $attributes['status'] = $validated['status'];
            if ($attributes['status'] === 'active' && ! $attributes['next_period_start']) {
                $attributes['status'] = 'ended';
            }
            $billingPlan->update($attributes);
            $this->syncItems($billingPlan, $validated['items']);
        });

        return redirect()->route('billing-plans.show', $billingPlan)->with('success', 'Billing plan updated.');
    }

    public function destroy(BillingPlan $billingPlan)
    {
        $billingPlan->delete();

        return redirect()->route('billing-plans.index')->with('success', 'Billing plan deleted. Invoices already generated were kept.');
    }

    /** Generate the invoice for the plan's next period (or a specific period start). */
    public function generate(Request $request, BillingPlan $billingPlan)
    {
        $validated = $request->validate(['period_start' => 'nullable|date']);

        try {
            $invoice = $this->plans->generateNext(
                $billingPlan,
                ! empty($validated['period_start']) ? Carbon::parse($validated['period_start']) : null
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('billing-plans.show', $billingPlan)->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', "Draft invoice {$invoice->invoice_number} generated for {$invoice->title}.");
    }

    public function setStatus(Request $request, BillingPlan $billingPlan)
    {
        $validated = $request->validate(['status' => ['required', Rule::in(BillingPlan::STATUSES)]]);

        if ($validated['status'] === 'active' && ! $billingPlan->next_period_start) {
            return redirect()->route('billing-plans.show', $billingPlan)->with('error', 'Set the next period to invoice before resuming this plan.');
        }

        $billingPlan->update(['status' => $validated['status']]);
        $label = ['active' => 'resumed', 'paused' => 'paused', 'ended' => 'ended'][$validated['status']];

        return redirect()->route('billing-plans.show', $billingPlan)->with('success', "Billing plan {$label}.");
    }

    /** Run the generator now for every plan that is due (same as the daily schedule). */
    public function generateDue()
    {
        $invoices = $this->plans->generateDue();

        if ($invoices === []) {
            return redirect()->route('billing-plans.index')->with('success', 'Nothing to generate: every active plan is up to date.');
        }

        $numbers = collect($invoices)->pluck('invoice_number')->implode(', ');

        return redirect()->route('invoices.index')->with('success', 'Generated ' . count($invoices) . ' draft invoice(s): ' . $numbers . '.');
    }

    /* ------------------------------------------------------------- helpers */

    protected function formData(BillingPlan $billingPlan): array
    {
        $clients = Client::with('pricingPresets.items')->where('is_active', true)->orderBy('company_name')->get();

        return [
            'billingPlan' => $billingPlan,
            'clients' => $clients,
            'products' => Product::active()->ordered()->get(),
            'presetsByClient' => $clients->mapWithKeys(fn ($client) => [
                $client->id => $client->pricingPresets->map(fn ($preset) => [
                    'id' => $preset->id,
                    'name' => $preset->name,
                    'items' => $preset->items->map(fn ($item) => [
                        'description' => $item->description,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                    ])->values(),
                ])->values(),
            ]),
        ];
    }

    protected function validatePlan(Request $request, bool $editing = false): array
    {
        $rules = [
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'billing_cycle' => ['required', Rule::in(array_keys(BillingPlan::CYCLES))],
            'ends_on' => 'nullable|date',
            'issue_days_before' => 'required|integer|min:0|max:120',
            'due_days' => 'required|integer|min:0|max:365',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'title_template' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];

        if ($editing) {
            $rules['next_period_start'] = 'nullable|date';
            $rules['status'] = ['required', Rule::in(BillingPlan::STATUSES)];
        } else {
            $rules['starts_on'] = 'required|date';
        }

        return $request->validate($rules, [
            'items.required' => 'Add at least one line item to the plan.',
            'items.*.description.required' => 'Every line item needs a description.',
        ]);
    }

    protected function planAttributes(array $validated): array
    {
        return [
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'billing_cycle' => $validated['billing_cycle'],
            'ends_on' => $validated['ends_on'] ?? null,
            'issue_days_before' => $validated['issue_days_before'],
            'due_days' => $validated['due_days'],
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'title_template' => $validated['title_template'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ];
    }

    protected function syncItems(BillingPlan $plan, array $items): void
    {
        $plan->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $plan->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $index,
            ]);
        }
    }
}
