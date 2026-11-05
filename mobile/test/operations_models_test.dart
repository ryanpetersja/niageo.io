import 'package:flutter_test/flutter_test.dart';
import 'package:niageo_ops/core/dates.dart';
import 'package:niageo_ops/features/dashboard/dashboard_models.dart';
import 'package:niageo_ops/features/operations/billing_plan_models.dart';
import 'package:niageo_ops/features/operations/subscription_models.dart';
import 'package:niageo_ops/features/operations/uptime_models.dart';

const endpointJson = {
  'id': 4,
  'name': 'Portal',
  'url': 'https://portal.campuselite.test/health',
  'client': {'id': 1, 'company_name': 'Campus Elite'},
  'status': 'down',
  'is_active': true,
  'check_interval_minutes': 5,
  'degraded_threshold_ms': 2000,
  'last_checked_at': '2026-11-05T09:55:00+00:00',
  'last_response_time_ms': 5012,
  'last_status_code': 503,
  'last_error_message': 'HTTP 503',
  'stats': {'checks_24h': 12, 'uptime_24h': 91.7, 'avg_response_ms_24h': 840},
  'recent_checks': [
    {
      'status': 'down',
      'response_time_ms': 5012,
      'status_code': 503,
      'error_message': 'HTTP 503',
      'checked_at': '2026-11-05T09:55:00+00:00',
    },
    {
      'status': 'up',
      'response_time_ms': 180,
      'status_code': 200,
      'error_message': null,
      'checked_at': '2026-11-05T09:50:00+00:00',
    },
  ],
};

const billJson = {
  'id': 2,
  'service_name': 'Cloudflare',
  'category': 'hosting',
  'category_label': 'Hosting',
  'amount': '40.00',
  'billing_cycle': 'monthly',
  'cycle_label': 'Monthly',
  'monthly_equivalent': 40,
  'next_due_date': '2026-11-01',
  'days_until_due': -4,
  'status': 'overdue',
  'last_paid_at': null,
  'is_active': true,
  'auto_renew': true,
};

const planJson = {
  'id': 7,
  'name': 'Monthly maintenance',
  'client': {'id': 1, 'company_name': 'Campus Elite'},
  'billing_cycle': 'monthly',
  'cycle_label': 'Monthly',
  'status': 'active',
  'starts_on': '2026-01-01',
  'next_period_start': '2026-11-01',
  'next_issue_date': '2026-11-01',
  'is_due': true,
  'issue_days_before': 0,
  'due_days': 14,
  'tax_rate': '0.00',
  'period_subtotal': 450,
  'period_total': 450,
  'monthly_value': 450,
  'items': [
    {
      'id': 1,
      'description': 'Hosting',
      'quantity': 1,
      'unit_price': 450,
      'total': 450,
    },
  ],
  'upcoming': [
    {
      'start': '2026-11-01',
      'end': '2026-11-30',
      'issue_date': '2026-11-01',
      'due_date': '2026-11-15',
      'label': 'November 2026',
    },
  ],
  'invoices': [],
};

void main() {
  test('uptime overview and endpoint detail parse', () {
    final overview = UptimeOverview.fromJson({
      'summary': {'up': 5, 'degraded': 0, 'down': 1, 'total': 6},
      'data': [endpointJson],
    });
    expect(overview.summary.allUp, isFalse);
    expect(overview.summary.total, 6);

    final endpoint = overview.endpoints.single;
    expect(endpoint.status, EndpointStatus.down);
    expect(endpoint.status.isTrouble, isTrue);
    expect(endpoint.clientName, 'Campus Elite');
    expect(endpoint.host, 'portal.campuselite.test');
    expect(endpoint.lastResponseTimeMs, 5012);
    expect(endpoint.stats?.uptime24h, 91.7);
    expect(endpoint.recentChecks.length, 2);
    expect(endpoint.recentChecks.last.status, EndpointStatus.up);
    expect(EndpointStatus.parse('bogus'), EndpointStatus.unknown);
  });

  test('subscriptions overview groups bills by status', () {
    final overview = SubscriptionsOverview.fromJson({
      'summary': {
        'active_count': 3,
        'total_monthly': '229.95',
        'overdue_count': 1,
        'overdue_total': 40,
        'due_soon_count': 1,
        'due_soon_total': 89.95,
      },
      'data': [
        billJson,
        {
          ...billJson,
          'id': 3,
          'service_name': 'SendGrid',
          'status': 'due_soon',
          'next_due_date': '2026-11-08',
          'days_until_due': 3,
        },
        {
          ...billJson,
          'id': 4,
          'service_name': 'Workspace',
          'status': 'paid',
          'auto_renew': false,
          'last_paid_at': '2026-11-02T10:00:00+00:00',
        },
      ],
    });

    expect(overview.summary.totalMonthly, 229.95);
    expect(
      overview.withStatus(BillStatus.overdue).single.serviceName,
      'Cloudflare',
    );
    expect(overview.withStatus(BillStatus.dueSoon).single.daysUntilDue, 3);
    expect(overview.withStatus(BillStatus.paid).single.isUnpaid, isFalse);
    expect(overview.bills.first.isUnpaid, isTrue);
    expect(overview.bills.first.amount, 40);
    expect(BillStatus.parse('due_soon').label, 'Due soon');
  });

  test('billing plan detail parses periods, items and readiness', () {
    final plan = BillingPlan.fromJson(planJson);
    expect(plan.clientName, 'Campus Elite');
    expect(plan.status, PlanStatus.active);
    expect(plan.isDue, isTrue);
    expect(plan.canGenerate, isTrue);
    expect(plan.periodTotal, 450);
    expect(plan.upcoming.single.label, 'November 2026');
    expect(plan.upcoming.single.dueDate, DateTime(2026, 11, 15));
    expect(plan.items.single.total, 450);

    final ended = BillingPlan.fromJson({
      ...planJson,
      'status': 'ended',
      'next_period_start': null,
    });
    expect(ended.canGenerate, isFalse);
    expect(
      PlansOverview.fromJson({
        'summary': {
          'active_count': 2,
          'monthly_value': 900,
          'due_now_count': 1,
        },
        'data': [planJson],
      }).summary.dueNowCount,
      1,
    );
  });

  test('dashboard carries the operations summary and counts alerts', () {
    final dashboard = Dashboard.fromJson({
      'metrics': {},
      'pipeline': [],
      'recent_invoices': [],
      'overdue_invoices': [],
      'operations': {
        'uptime': {
          'up': 5,
          'degraded': 0,
          'down': 1,
          'total': 6,
          'troubled': [endpointJson],
        },
        'subscriptions': {
          'active_count': 3,
          'total_monthly': 229.95,
          'overdue_count': 1,
          'alerts': [billJson],
        },
        'billing_plans': {
          'active_count': 2,
          'due_now_count': 1,
          'monthly_value': 900,
          'due': [planJson],
          'next': planJson,
        },
      },
    });

    final ops = dashboard.operations!;
    expect(ops.alertCount, 3);
    expect(ops.troubled.single.name, 'Portal');
    expect(ops.billAlerts.single.serviceName, 'Cloudflare');
    expect(ops.duePlans.single.name, 'Monthly maintenance');
    expect(ops.nextPlan?.nextIssueDate, DateTime(2026, 11, 1));
    expect(ops.plansMonthlyValue, 900);

    expect(
      Dashboard.fromJson({'metrics': {}, 'pipeline': []}).operations,
      isNull,
      reason: 'older servers send no operations block',
    );
  });

  test('timeAgo', () {
    final now = DateTime(2026, 11, 5, 12, 0);
    expect(
      timeAgo(now.subtract(const Duration(seconds: 20)), now: now),
      'just now',
    );
    expect(
      timeAgo(now.subtract(const Duration(minutes: 5)), now: now),
      '5 min ago',
    );
    expect(
      timeAgo(now.subtract(const Duration(hours: 3)), now: now),
      '3 h ago',
    );
    expect(
      timeAgo(now.subtract(const Duration(days: 1)), now: now),
      'yesterday',
    );
    expect(
      timeAgo(now.subtract(const Duration(days: 6)), now: now),
      '6 days ago',
    );
  });
}
