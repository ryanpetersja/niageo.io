import '../../core/json.dart';
import '../invoices/invoice_models.dart';
import '../operations/billing_plan_models.dart';
import '../operations/subscription_models.dart';
import '../operations/uptime_models.dart';

class Metrics {
  const Metrics({
    this.totalRevenue = 0,
    this.totalOutstanding = 0,
    this.totalOverdue = 0,
    this.invoiceCount = 0,
    this.paidCount = 0,
    this.overdueCount = 0,
    this.collectionRate = 0,
  });

  final double totalRevenue;
  final double totalOutstanding;
  final double totalOverdue;
  final int invoiceCount;
  final int paidCount;
  final int overdueCount;
  final double collectionRate;

  factory Metrics.fromJson(Map<String, dynamic> json) => Metrics(
    totalRevenue: asDouble(json['total_revenue']),
    totalOutstanding: asDouble(json['total_outstanding']),
    totalOverdue: asDouble(json['total_overdue']),
    invoiceCount: asInt(json['invoice_count']),
    paidCount: asInt(json['paid_count']),
    overdueCount: asInt(json['overdue_count']),
    collectionRate: asDouble(json['collection_rate']),
  );
}

/// One column of the pipeline: how many invoices sit in a status and what they are worth.
class PipelineStage {
  const PipelineStage({
    required this.status,
    required this.count,
    required this.total,
    required this.balanceDue,
  });

  final InvoiceStatus status;
  final int count;
  final double total;
  final double balanceDue;

  factory PipelineStage.fromJson(Map<String, dynamic> json) => PipelineStage(
    status: InvoiceStatus.parse(json['status']),
    count: asInt(json['count']),
    total: asDouble(json['total']),
    balanceDue: asDouble(json['balance_due']),
  );
}

/// What needs attention operationally: sites down, bills due, plans waiting to be invoiced.
class OperationsSummary {
  const OperationsSummary({
    required this.uptime,
    this.troubled = const [],
    required this.subscriptions,
    this.billAlerts = const [],
    this.activePlans = 0,
    this.plansDueNow = 0,
    this.plansMonthlyValue = 0,
    this.duePlans = const [],
    this.nextPlan,
  });

  final UptimeSummary uptime;
  final List<MonitoredEndpoint> troubled;
  final SubscriptionSummary subscriptions;
  final List<SubscriptionBill> billAlerts;
  final int activePlans;
  final int plansDueNow;
  final double plansMonthlyValue;
  final List<BillingPlan> duePlans;
  final BillingPlan? nextPlan;

  int get alertCount => troubled.length + billAlerts.length + duePlans.length;

  factory OperationsSummary.fromJson(Map<String, dynamic> json) {
    final uptime = asMap(json['uptime']) ?? const {};
    final subscriptions = asMap(json['subscriptions']) ?? const {};
    final plans = asMap(json['billing_plans']) ?? const {};
    final next = asMap(plans['next']);
    return OperationsSummary(
      uptime: UptimeSummary.fromJson(uptime),
      troubled: asList(uptime['troubled'])
          .map(MonitoredEndpoint.fromJson)
          .toList(),
      subscriptions: SubscriptionSummary.fromJson(subscriptions),
      billAlerts: asList(subscriptions['alerts'])
          .map(SubscriptionBill.fromJson)
          .toList(),
      activePlans: asInt(plans['active_count']),
      plansDueNow: asInt(plans['due_now_count']),
      plansMonthlyValue: asDouble(plans['monthly_value']),
      duePlans: asList(plans['due']).map(BillingPlan.fromJson).toList(),
      nextPlan: next == null ? null : BillingPlan.fromJson(next),
    );
  }
}

class Dashboard {
  const Dashboard({
    required this.metrics,
    required this.pipeline,
    required this.collectedThisMonth,
    required this.collectedLastMonth,
    required this.recentInvoices,
    required this.overdueInvoices,
    this.operations,
  });

  final Metrics metrics;
  final List<PipelineStage> pipeline;
  final double collectedThisMonth;
  final double collectedLastMonth;
  final List<Invoice> recentInvoices;
  final List<Invoice> overdueInvoices;
  final OperationsSummary? operations;

  PipelineStage? stage(InvoiceStatus status) =>
      pipeline.where((s) => s.status == status).firstOrNull;

  factory Dashboard.fromJson(Map<String, dynamic> json) => Dashboard(
    metrics: Metrics.fromJson(asMap(json['metrics']) ?? const {}),
    pipeline: asList(json['pipeline']).map(PipelineStage.fromJson).toList(),
    collectedThisMonth: asDouble(json['collected_this_month']),
    collectedLastMonth: asDouble(json['collected_last_month']),
    recentInvoices: asList(json['recent_invoices'])
        .map(Invoice.fromJson)
        .toList(),
    overdueInvoices: asList(json['overdue_invoices'])
        .map(Invoice.fromJson)
        .toList(),
    operations: asMap(json['operations']) == null
        ? null
        : OperationsSummary.fromJson(asMap(json['operations'])!),
  );
}
