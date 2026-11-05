<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SubscriptionBillResource;
use App\Models\SubscriptionBill;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SubscriptionBillController extends Controller
{
    public const STATUS_RANK = ['overdue' => 0, 'due_soon' => 1, 'upcoming' => 2, 'paid' => 3];

    public function index(Request $request)
    {
        SubscriptionBill::active()->unpaid()->get()->each->refreshStatus();

        $bills = SubscriptionBill::query()
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($bill) => sprintf('%d-%s-%s', self::STATUS_RANK[$bill->status] ?? 9, $bill->next_due_date?->toDateString() ?? '9999', strtolower($bill->service_name)))
            ->values();

        return response()->json([
            'summary' => self::summary(),
            'data' => SubscriptionBillResource::collection($bills)->resolve(),
        ]);
    }

    public function markPaid(SubscriptionBill $subscription)
    {
        $subscription->markAsPaid();

        if ($subscription->auto_renew) {
            $subscription->advanceBillingCycle();
            $subscription->refreshStatus();
        }

        return new SubscriptionBillResource($subscription->fresh());
    }

    /** Counts and totals for the lists and the dashboard; statuses are assumed fresh. */
    public static function summary(): array
    {
        $active = SubscriptionBill::active()->get();

        return [
            'active_count' => $active->count(),
            'total_monthly' => round(self::monthlyEquivalent($active), 2),
            'overdue_count' => $active->where('status', 'overdue')->count(),
            'overdue_total' => round((float) $active->where('status', 'overdue')->sum('amount'), 2),
            'due_soon_count' => $active->where('status', 'due_soon')->count(),
            'due_soon_total' => round((float) $active->where('status', 'due_soon')->sum('amount'), 2),
        ];
    }

    protected static function monthlyEquivalent(Collection $bills): float
    {
        return (float) $bills->sum(fn ($bill) => match ($bill->billing_cycle) {
            'quarterly' => (float) $bill->amount / 3,
            'annual' => (float) $bill->amount / 12,
            default => (float) $bill->amount,
        });
    }
}
