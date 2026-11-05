import '../../core/json.dart';
import '../invoices/invoice_models.dart';

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

class Dashboard {
  const Dashboard({
    required this.metrics,
    required this.pipeline,
    required this.collectedThisMonth,
    required this.collectedLastMonth,
    required this.recentInvoices,
    required this.overdueInvoices,
  });

  final Metrics metrics;
  final List<PipelineStage> pipeline;
  final double collectedThisMonth;
  final double collectedLastMonth;
  final List<Invoice> recentInvoices;
  final List<Invoice> overdueInvoices;

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
  );
}
