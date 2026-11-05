import '../../core/dates.dart';
import '../../core/json.dart';
import '../clients/client_models.dart';

enum InvoiceStatus {
  draft,
  sent,
  overdue,
  paid,
  cancelled;

  static InvoiceStatus parse(Object? value) {
    final name = value?.toString();
    return values.firstWhere(
      (s) => s.name == name,
      orElse: () => InvoiceStatus.draft,
    );
  }

  String get label => switch (this) {
    InvoiceStatus.draft => 'Draft',
    InvoiceStatus.sent => 'Sent',
    InvoiceStatus.overdue => 'Overdue',
    InvoiceStatus.paid => 'Paid',
    InvoiceStatus.cancelled => 'Cancelled',
  };

  /// What marking an invoice with this status is called on a button.
  String get actionLabel => switch (this) {
    InvoiceStatus.draft => 'Back to draft',
    InvoiceStatus.sent => 'Mark as sent',
    InvoiceStatus.overdue => 'Mark as overdue',
    InvoiceStatus.paid => 'Mark as paid',
    InvoiceStatus.cancelled => 'Cancel invoice',
  };

  bool get isOpen =>
      this == InvoiceStatus.sent || this == InvoiceStatus.overdue;
}

/// The client as embedded in an invoice payload.
class InvoiceClient {
  const InvoiceClient({
    required this.id,
    required this.companyName,
    this.billingEmail,
    this.billingTerms,
    this.dueDays = 30,
    this.contacts = const [],
  });

  final int id;
  final String companyName;
  final String? billingEmail;
  final String? billingTerms;
  final int dueDays;
  final List<Contact> contacts;

  factory InvoiceClient.fromJson(Map<String, dynamic> json) => InvoiceClient(
    id: asInt(json['id']),
    companyName: asString(json['company_name']) ?? '',
    billingEmail: asString(json['billing_email']),
    billingTerms: asString(json['billing_terms']),
    dueDays: asInt(json['due_days'], 30),
    contacts: asList(json['contacts']).map(Contact.fromJson).toList(),
  );
}

class LineItem {
  const LineItem({
    this.id,
    required this.description,
    required this.quantity,
    required this.unitPrice,
    required this.total,
  });

  final int? id;
  final String description;
  final double quantity;
  final double unitPrice;
  final double total;

  factory LineItem.fromJson(Map<String, dynamic> json) => LineItem(
    id: json['id'] == null ? null : asInt(json['id']),
    description: asString(json['description']) ?? '',
    quantity: asDouble(json['quantity'], 1),
    unitPrice: asDouble(json['unit_price']),
    total: asDouble(json['total']),
  );
}

class Payment {
  const Payment({
    required this.id,
    required this.amount,
    required this.paymentDate,
    this.paymentMethod,
    this.reference,
    this.notes,
    this.recordedBy,
  });

  final int id;
  final double amount;
  final DateTime paymentDate;
  final String? paymentMethod;
  final String? reference;
  final String? notes;
  final String? recordedBy;

  factory Payment.fromJson(Map<String, dynamic> json) => Payment(
    id: asInt(json['id']),
    amount: asDouble(json['amount']),
    paymentDate: parseDate(asString(json['payment_date'])) ?? DateTime.now(),
    paymentMethod: asString(json['payment_method']),
    reference: asString(json['reference']),
    notes: asString(json['notes']),
    recordedBy: asString(json['recorded_by']),
  );
}

class StatusEntry {
  const StatusEntry({
    this.fromStatus,
    required this.toStatus,
    this.notes,
    this.changedBy,
    this.createdAt,
  });

  final InvoiceStatus? fromStatus;
  final InvoiceStatus toStatus;
  final String? notes;
  final String? changedBy;
  final DateTime? createdAt;

  factory StatusEntry.fromJson(Map<String, dynamic> json) => StatusEntry(
    fromStatus: json['from_status'] == null
        ? null
        : InvoiceStatus.parse(json['from_status']),
    toStatus: InvoiceStatus.parse(json['to_status']),
    notes: asString(json['notes']),
    changedBy: asString(json['changed_by']),
    createdAt: parseDate(asString(json['created_at'])),
  );
}

class Invoice {
  const Invoice({
    required this.id,
    required this.invoiceNumber,
    this.title,
    required this.status,
    this.client,
    required this.issueDate,
    required this.dueDate,
    this.subtotal = 0,
    this.taxRate = 0,
    this.taxAmount = 0,
    this.total = 0,
    this.amountPaid = 0,
    this.balanceDue = 0,
    this.isOverdue = false,
    this.isRecurring = false,
    this.notes,
    this.internalNotes,
    this.lineItems = const [],
    this.payments = const [],
    this.statusHistory = const [],
    this.validTransitions = const [],
    this.canEdit = false,
    this.canDelete = false,
    this.canRecordPayment = false,
    this.pdfUrl,
    this.updatedAt,
  });

  final int id;
  final String invoiceNumber;
  final String? title;
  final InvoiceStatus status;
  final InvoiceClient? client;
  final DateTime issueDate;
  final DateTime dueDate;
  final double subtotal;
  final double taxRate;
  final double taxAmount;
  final double total;
  final double amountPaid;
  final double balanceDue;
  final bool isOverdue;
  final bool isRecurring;
  final String? notes;
  final String? internalNotes;
  final List<LineItem> lineItems;
  final List<Payment> payments;
  final List<StatusEntry> statusHistory;
  final List<InvoiceStatus> validTransitions;
  final bool canEdit;
  final bool canDelete;
  final bool canRecordPayment;
  final String? pdfUrl;
  final DateTime? updatedAt;

  String get clientName => client?.companyName ?? '';

  String get displayTitle =>
      (title ?? '').trim().isEmpty ? invoiceNumber : title!.trim();

  /// A sent invoice past its due date shows as overdue even before the server flags it.
  InvoiceStatus get effectiveStatus => status == InvoiceStatus.sent && isOverdue
      ? InvoiceStatus.overdue
      : status;

  factory Invoice.fromJson(Map<String, dynamic> json) {
    final client = asMap(json['client']);
    return Invoice(
      id: asInt(json['id']),
      invoiceNumber: asString(json['invoice_number']) ?? '',
      title: asString(json['title']),
      status: InvoiceStatus.parse(json['status']),
      client: client == null ? null : InvoiceClient.fromJson(client),
      issueDate: parseDate(asString(json['issue_date'])) ?? DateTime.now(),
      dueDate: parseDate(asString(json['due_date'])) ?? DateTime.now(),
      subtotal: asDouble(json['subtotal']),
      taxRate: asDouble(json['tax_rate']),
      taxAmount: asDouble(json['tax_amount']),
      total: asDouble(json['total']),
      amountPaid: asDouble(json['amount_paid']),
      balanceDue: asDouble(json['balance_due']),
      isOverdue: asBool(json['is_overdue']),
      isRecurring: asBool(json['is_recurring']),
      notes: asString(json['notes']),
      internalNotes: asString(json['internal_notes']),
      lineItems: asList(json['line_items']).map(LineItem.fromJson).toList(),
      payments: asList(json['payments']).map(Payment.fromJson).toList(),
      statusHistory: asList(json['status_history'])
          .map(StatusEntry.fromJson)
          .toList(),
      validTransitions: (json['valid_transitions'] is List)
          ? (json['valid_transitions'] as List)
                .map(InvoiceStatus.parse)
                .toList()
          : const [],
      canEdit: asBool(json['can_edit']),
      canDelete: asBool(json['can_delete']),
      canRecordPayment: asBool(json['can_record_payment']),
      pdfUrl: asString(json['pdf_url']),
      updatedAt: parseDate(asString(json['updated_at'])),
    );
  }
}

/// One page of a paginated list, as Laravel's resource collections send it.
class Paginated<T> {
  const Paginated({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  static Paginated<T> fromJson<T>(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) parse,
  ) {
    final meta = asMap(json['meta']) ?? const {};
    return Paginated<T>(
      items: asList(json['data']).map(parse).toList(),
      currentPage: asInt(meta['current_page'], 1),
      lastPage: asInt(meta['last_page'], 1),
      total: asInt(meta['total']),
    );
  }
}
