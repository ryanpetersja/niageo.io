<?php

namespace App\Http\Controllers;

use App\Models\MonitoredEndpoint;
use App\Models\SubscriptionBill;
use App\Services\DashboardService;
use App\Services\UptimeService;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboardService, UptimeService $uptimeService)
    {
        $metrics = $dashboardService->getMetrics();
        $monthlyRevenue = $dashboardService->getMonthlyRevenue();
        $recentInvoices = $dashboardService->getRecentInvoices();
        $overdueInvoices = $dashboardService->getOverdueInvoices();

        $uptimeSummary = $uptimeService->getSummary();
        $troubledEndpoints = MonitoredEndpoint::with('client')
            ->where('is_active', true)
            ->whereIn('current_status', ['down', 'degraded'])
            ->orderByRaw("FIELD(current_status, 'down', 'degraded')")
            ->get();

        // Refresh subscription statuses and get alerts
        SubscriptionBill::active()->unpaid()->get()->each->refreshStatus();
        $subscriptionAlerts = SubscriptionBill::active()
            ->whereIn('status', ['overdue', 'due_soon'])
            ->orderByRaw("FIELD(status, 'overdue', 'due_soon')")
            ->orderBy('next_due_date')
            ->get();

        return view('dashboard', compact(
            'metrics', 'monthlyRevenue', 'recentInvoices', 'overdueInvoices',
            'uptimeSummary', 'troubledEndpoints', 'subscriptionAlerts'
        ));
    }
}
