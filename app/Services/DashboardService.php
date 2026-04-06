<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getMetrics(?string $startDate = null, ?string $endDate = null, ?int $clientId = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : now()->subYears(5)->startOfYear();
        $end = $endDate ? Carbon::parse($endDate) : now()->endOfDay();

        $baseQuery = Invoice::query()
            ->whereBetween('issue_date', [$start, $end]);

        if ($clientId) {
            $baseQuery->where('client_id', $clientId);
        }

        $totalRevenue = (clone $baseQuery)->where('status', 'paid')->sum('total');
        $totalOutstanding = (clone $baseQuery)->whereIn('status', ['sent', 'overdue'])->sum(DB::raw('total - amount_paid'));
        $totalOverdue = (clone $baseQuery)->where('status', 'overdue')->sum(DB::raw('total - amount_paid'));
        $invoiceCount = (clone $baseQuery)->count();
        $paidCount = (clone $baseQuery)->where('status', 'paid')->count();
        $overdueCount = (clone $baseQuery)->where('status', 'overdue')->count();

        return [
            'total_revenue' => (float) $totalRevenue,
            'total_outstanding' => (float) $totalOutstanding,
            'total_overdue' => (float) $totalOverdue,
            'invoice_count' => $invoiceCount,
            'paid_count' => $paidCount,
            'overdue_count' => $overdueCount,
            'collection_rate' => $invoiceCount > 0 ? round(($paidCount / $invoiceCount) * 100, 1) : 0,
        ];
    }

    public function getMonthlyRevenue(?int $year = null, ?int $clientId = null): array
    {
        $year = $year ?? now()->year;

        $query = Payment::query()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->whereYear('payments.payment_date', $year)
            ->select(
                DB::raw('MONTH(payments.payment_date) as month'),
                DB::raw('SUM(payments.amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month');

        if ($clientId) {
            $query->where('invoices.client_id', $clientId);
        }

        $results = $query->get()->pluck('total', 'month')->toArray();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = [
                'month' => Carbon::create($year, $i, 1)->format('M'),
                'total' => (float) ($results[$i] ?? 0),
            ];
        }

        return $months;
    }

    public function getRecentInvoices(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::with('client')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getOverdueInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::with('client')
            ->where('status', 'overdue')
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
