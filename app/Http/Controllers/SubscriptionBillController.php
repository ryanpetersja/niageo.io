<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionBill;
use Illuminate\Http\Request;

class SubscriptionBillController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionBill::query();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Refresh statuses on active unpaid bills
        SubscriptionBill::active()->unpaid()->get()->each->refreshStatus();

        $bills = $query->orderByRaw("FIELD(status, 'overdue', 'due_soon', 'upcoming', 'paid')")
            ->orderBy('next_due_date')
            ->get();

        $summary = [
            'total_monthly' => $this->calculateMonthlyTotal(),
            'due_soon_count' => SubscriptionBill::active()->dueSoon()->count(),
            'overdue_count' => SubscriptionBill::active()->overdue()->count(),
            'overdue_total' => SubscriptionBill::active()->overdue()->sum('amount'),
            'active_count' => SubscriptionBill::active()->count(),
        ];

        return view('subscriptions.index', compact('bills', 'summary'));
    }

    public function create()
    {
        return view('subscriptions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(SubscriptionBill::CATEGORIES)),
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'billing_cycle' => 'required|string|in:' . implode(',', array_keys(SubscriptionBill::BILLING_CYCLES)),
            'next_due_date' => 'required|date',
            'is_active' => 'boolean',
            'auto_renew' => 'boolean',
            'url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['auto_renew'] = $request->boolean('auto_renew', true);

        $bill = SubscriptionBill::create($validated);
        $bill->refreshStatus();

        return redirect()->route('subscriptions.index')->with('success', "Subscription \"{$bill->service_name}\" added.");
    }

    public function edit(SubscriptionBill $subscription)
    {
        return view('subscriptions.edit', ['bill' => $subscription]);
    }

    public function update(Request $request, SubscriptionBill $subscription)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(SubscriptionBill::CATEGORIES)),
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'billing_cycle' => 'required|string|in:' . implode(',', array_keys(SubscriptionBill::BILLING_CYCLES)),
            'next_due_date' => 'required|date',
            'is_active' => 'boolean',
            'auto_renew' => 'boolean',
            'url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['auto_renew'] = $request->boolean('auto_renew', true);

        $subscription->update($validated);
        $subscription->refreshStatus();

        return redirect()->route('subscriptions.index')->with('success', "Subscription \"{$subscription->service_name}\" updated.");
    }

    public function destroy(SubscriptionBill $subscription)
    {
        $name = $subscription->service_name;
        $subscription->delete();

        return redirect()->route('subscriptions.index')->with('success', "Subscription \"{$name}\" deleted.");
    }

    public function markPaid(SubscriptionBill $subscription)
    {
        $subscription->markAsPaid();

        if ($subscription->auto_renew) {
            $subscription->advanceBillingCycle();
            $message = "\"{$subscription->service_name}\" marked as paid. Next due: {$subscription->next_due_date->format('M j, Y')}.";
        } else {
            $message = "\"{$subscription->service_name}\" marked as paid.";
        }

        return redirect()->back()->with('success', $message);
    }

    private function calculateMonthlyTotal(): float
    {
        $total = 0;
        $bills = SubscriptionBill::active()->get();

        foreach ($bills as $bill) {
            $total += match ($bill->billing_cycle) {
                'monthly' => (float) $bill->amount,
                'quarterly' => (float) $bill->amount / 3,
                'annual' => (float) $bill->amount / 12,
                default => (float) $bill->amount,
            };
        }

        return round($total, 2);
    }
}
