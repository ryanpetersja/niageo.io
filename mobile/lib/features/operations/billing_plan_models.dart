import '../../core/dates.dart';
import '../../core/json.dart';
import '../invoices/invoice_models.dart';

enum PlanStatus {
  active,
  paused,
  ended;

  static PlanStatus parse(Object? value) {
    final name = value?.toString();
    return values.firstWhere(
      (s) => s.name == name,
      orElse: () => PlanStatus.active,
    );
  }

  String get label => switch (this) {
    PlanStatus.active => 'Active',
    PlanStatus.paused => 'Paused',
    PlanStatus.ended => 'Ended',
  };
}

class PlanPeriod {
  const PlanPeriod({
    required this.start,
    required this.end,
    required this.issueDate,
    required this.dueDate,
    required this.label,
  });

  final DateTime start;
  final DateTime end;
  final DateTime issueDate;
  final DateTime dueDate;
  final String label;

  factory PlanPeriod.fromJson(Map<String, dynamic> json) => PlanPeriod(
    start: parseDate(asString(json['start'])) ?? DateTime.now(),
    end: parseDate(asString(json['end'])) ?? DateTime.now(),
    issueDate: parseDate(asString(json['issue_date'])) ?? DateTime.now(),
    dueDate: parseDate(asString(json['due_date'])) ?? DateTime.now(),
    label: asString(json['label']) ?? '',
  );
}

class PlanItem {
  const PlanItem({
    required this.description,
    required this.quantity,
    required this.unitPrice,
    required this.total,
  });

  final String description;
  final double quantity;
  final double unitPrice;
  final double total;

  factory PlanItem.fromJson(Map<String, dynamic> json) => PlanItem(
    description: asString(json['description']) ?? '',
    quantity: asDouble(json['quantity'], 1),
    unitPrice: asDouble(json['unit_price']),
    total: asDouble(json['total']),
  );
}

class BillingPlan {
  const BillingPlan({
    required this.id,
    required this.name,
    this.clientId,
    this.clientName,
    this.billingCycle = 'monthly',
    this.cycleLabel = 'Monthly',
    this.status = PlanStatus.active,
    this.startsOn,
    this.endsOn,
    this.nextPeriodStart,
    this.nextIssueDate,
    this.isDue = false,
    this.issueDaysBefore = 0,
    this.dueDays = 30,
    this.taxRate = 0,
    this.periodSubtotal,
    this.periodTotal,
    this.monthlyValue,
    this.items = const [],
    this.upcoming = const [],
    this.invoices = const [],
    this.notes,
    this.internalNotes,
  });

  final int id;
  final String name;
  final int? clientId;
  final String? clientName;
  final String billingCycle;
  final String cycleLabel;
  final PlanStatus status;
  final DateTime? startsOn;
  final DateTime? endsOn;
  final DateTime? nextPeriodStart;
  final DateTime? nextIssueDate;
  final bool isDue;
  final int issueDaysBefore;
  final int dueDays;
  final double taxRate;
  final double? periodSubtotal;
  final double? periodTotal;
  final double? monthlyValue;
  final List<PlanItem> items;
  final List<PlanPeriod> upcoming;
  final List<Invoice> invoices;
  final String? notes;
  final String? internalNotes;

  bool get canGenerate =>
      status == PlanStatus.active &&
      nextPeriodStart != null &&
      items.isNotEmpty;

  factory BillingPlan.fromJson(Map<String, dynamic> json) {
    final client = asMap(json['client']);
    return BillingPlan(
      id: asInt(json['id']),
      name: asString(json['name']) ?? '',
      clientId: client == null ? null : asInt(client['id']),
      clientName: client == null ? null : asString(client['company_name']),
      billingCycle: asString(json['billing_cycle']) ?? 'monthly',
      cycleLabel: asString(json['cycle_label']) ?? 'Monthly',
      status: PlanStatus.parse(json['status']),
      startsOn: parseDate(asString(json['starts_on'])),
      endsOn: parseDate(asString(json['ends_on'])),
      nextPeriodStart: parseDate(asString(json['next_period_start'])),
      nextIssueDate: parseDate(asString(json['next_issue_date'])),
      isDue: asBool(json['is_due']),
      issueDaysBefore: asInt(json['issue_days_before']),
      dueDays: asInt(json['due_days'], 30),
      taxRate: asDouble(json['tax_rate']),
      periodSubtotal: json['period_subtotal'] == null
          ? null
          : asDouble(json['period_subtotal']),
      periodTotal: json['period_total'] == null
          ? null
          : asDouble(json['period_total']),
      monthlyValue: json['monthly_value'] == null
          ? null
          : asDouble(json['monthly_value']),
      items: asList(json['items']).map(PlanItem.fromJson).toList(),
      upcoming: asList(json['upcoming']).map(PlanPeriod.fromJson).toList(),
      invoices: asList(json['invoices']).map(Invoice.fromJson).toList(),
      notes: asString(json['notes']),
      internalNotes: asString(json['internal_notes']),
    );
  }
}

class PlanSummary {
  const PlanSummary({
    this.activeCount = 0,
    this.pausedCount = 0,
    this.monthlyValue = 0,
    this.dueNowCount = 0,
  });

  final int activeCount;
  final int pausedCount;
  final double monthlyValue;
  final int dueNowCount;

  factory PlanSummary.fromJson(Map<String, dynamic> json) => PlanSummary(
    activeCount: asInt(json['active_count']),
    pausedCount: asInt(json['paused_count']),
    monthlyValue: asDouble(json['monthly_value']),
    dueNowCount: asInt(json['due_now_count']),
  );
}

class PlansOverview {
  const PlansOverview({required this.summary, required this.plans});

  final PlanSummary summary;
  final List<BillingPlan> plans;

  factory PlansOverview.fromJson(Map<String, dynamic> json) => PlansOverview(
    summary: PlanSummary.fromJson(asMap(json['summary']) ?? const {}),
    plans: asList(json['data']).map(BillingPlan.fromJson).toList(),
  );
}
