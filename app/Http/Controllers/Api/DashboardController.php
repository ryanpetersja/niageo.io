<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\DashboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The pipeline screen of the mobile app: headline metrics, invoices by status, recent and overdue.
 */
class DashboardController extends Controller
{
    public const PIPELINE_STATUSES = ['draft', 'sent', 'overdue', 'paid', 'cancelled'];

    public function __invoke(DashboardService $dashboard)
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
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    protected function collected(Carbon $from, Carbon $to): float
    {
        return round((float) Payment::whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])->sum('amount'), 2);
    }
}
