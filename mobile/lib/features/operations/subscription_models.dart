import '../../core/dates.dart';
import '../../core/json.dart';

enum BillStatus {
  overdue,
  dueSoon,
  upcoming,
  paid;

  static BillStatus parse(Object? value) => switch (value?.toString()) {
    'overdue' => BillStatus.overdue,
    'due_soon' => BillStatus.dueSoon,
    'paid' => BillStatus.paid,
    _ => BillStatus.upcoming,
  };

  String get label => switch (this) {
    BillStatus.overdue => 'Overdue',
    BillStatus.dueSoon => 'Due soon',
    BillStatus.upcoming => 'Upcoming',
    BillStatus.paid => 'Paid',
  };
}

class SubscriptionBill {
  const SubscriptionBill({
    required this.id,
    required this.serviceName,
    this.category = 'other',
    this.categoryLabel = 'Other',
    this.description,
    required this.amount,
    this.billingCycle = 'monthly',
    this.cycleLabel = 'Monthly',
    this.monthlyEquivalent = 0,
    this.nextDueDate,
    this.daysUntilDue,
    this.status = BillStatus.upcoming,
    this.lastPaidAt,
    this.isActive = true,
    this.autoRenew = true,
    this.url,
    this.notes,
  });

  final int id;
  final String serviceName;
  final String category;
  final String categoryLabel;
  final String? description;
  final double amount;
  final String billingCycle;
  final String cycleLabel;
  final double monthlyEquivalent;
  final DateTime? nextDueDate;
  final int? daysUntilDue;
  final BillStatus status;
  final DateTime? lastPaidAt;
  final bool isActive;
  final bool autoRenew;
  final String? url;
  final String? notes;

  bool get isUnpaid => isActive && status != BillStatus.paid;

  String get dueText =>
      nextDueDate == null ? 'No due date' : dueLabel(nextDueDate!);

  factory SubscriptionBill.fromJson(Map<String, dynamic> json) =>
      SubscriptionBill(
        id: asInt(json['id']),
        serviceName: asString(json['service_name']) ?? '',
        category: asString(json['category']) ?? 'other',
        categoryLabel: asString(json['category_label']) ?? 'Other',
        description: asString(json['description']),
        amount: asDouble(json['amount']),
        billingCycle: asString(json['billing_cycle']) ?? 'monthly',
        cycleLabel: asString(json['cycle_label']) ?? 'Monthly',
        monthlyEquivalent: asDouble(json['monthly_equivalent']),
        nextDueDate: parseDate(asString(json['next_due_date'])),
        daysUntilDue: json['days_until_due'] == null
            ? null
            : asInt(json['days_until_due']),
        status: BillStatus.parse(json['status']),
        lastPaidAt: parseDate(asString(json['last_paid_at'])),
        isActive: asBool(json['is_active'], true),
        autoRenew: asBool(json['auto_renew'], true),
        url: asString(json['url']),
        notes: asString(json['notes']),
      );
}

class SubscriptionSummary {
  const SubscriptionSummary({
    this.activeCount = 0,
    this.totalMonthly = 0,
    this.overdueCount = 0,
    this.overdueTotal = 0,
    this.dueSoonCount = 0,
    this.dueSoonTotal = 0,
  });

  final int activeCount;
  final double totalMonthly;
  final int overdueCount;
  final double overdueTotal;
  final int dueSoonCount;
  final double dueSoonTotal;

  factory SubscriptionSummary.fromJson(Map<String, dynamic> json) =>
      SubscriptionSummary(
        activeCount: asInt(json['active_count']),
        totalMonthly: asDouble(json['total_monthly']),
        overdueCount: asInt(json['overdue_count']),
        overdueTotal: asDouble(json['overdue_total']),
        dueSoonCount: asInt(json['due_soon_count']),
        dueSoonTotal: asDouble(json['due_soon_total']),
      );
}

class SubscriptionsOverview {
  const SubscriptionsOverview({required this.summary, required this.bills});

  final SubscriptionSummary summary;
  final List<SubscriptionBill> bills;

  List<SubscriptionBill> withStatus(BillStatus status) =>
      bills.where((b) => b.status == status).toList();

  factory SubscriptionsOverview.fromJson(Map<String, dynamic> json) =>
      SubscriptionsOverview(
        summary: SubscriptionSummary.fromJson(
          asMap(json['summary']) ?? const {},
        ),
        bills: asList(json['data']).map(SubscriptionBill.fromJson).toList(),
      );
}
