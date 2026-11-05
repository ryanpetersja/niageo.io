import '../../core/dates.dart';
import '../../core/money.dart';
import 'invoice_models.dart';

/// A line item while it is being edited. Totals are derived, never stored.
class LineItemDraft {
  const LineItemDraft({
    this.description = '',
    this.quantity = 1,
    this.unitPrice = 0,
  });

  final String description;
  final double quantity;
  final double unitPrice;

  double get total => round2(quantity * unitPrice);

  bool get isBlank => description.trim().isEmpty && unitPrice == 0;

  LineItemDraft copyWith({
    String? description,
    double? quantity,
    double? unitPrice,
  }) => LineItemDraft(
    description: description ?? this.description,
    quantity: quantity ?? this.quantity,
    unitPrice: unitPrice ?? this.unitPrice,
  );

  factory LineItemDraft.fromLineItem(LineItem item) => LineItemDraft(
    description: item.description,
    quantity: item.quantity,
    unitPrice: item.unitPrice,
  );

  Map<String, dynamic> toJson() => {
    'description': description.trim(),
    'quantity': quantity,
    'unit_price': unitPrice,
  };
}

/// The invoice form's state: pure Dart so totals and validation are testable
/// without widgets, and so the editor screen stays thin.
class InvoiceDraft {
  const InvoiceDraft({
    this.id,
    this.client,
    this.title = '',
    required this.issueDate,
    required this.dueDate,
    this.taxRate = 0,
    this.notes = '',
    this.internalNotes = '',
    this.lineItems = const [],
  });

  final int? id;
  final InvoiceClient? client;
  final String title;
  final DateTime issueDate;
  final DateTime dueDate;
  final double taxRate;
  final String notes;
  final String internalNotes;
  final List<LineItemDraft> lineItems;

  bool get isNew => id == null;

  factory InvoiceDraft.blank({DateTime? today, InvoiceClient? client}) {
    final issue = dateOnly(today ?? DateTime.now());
    return InvoiceDraft(
      client: client,
      issueDate: issue,
      dueDate: issue.add(Duration(days: client?.dueDays ?? 30)),
    );
  }

  factory InvoiceDraft.fromInvoice(Invoice invoice) => InvoiceDraft(
    id: invoice.id,
    client: invoice.client,
    title: invoice.title ?? '',
    issueDate: dateOnly(invoice.issueDate),
    dueDate: dateOnly(invoice.dueDate),
    taxRate: invoice.taxRate,
    notes: invoice.notes ?? '',
    internalNotes: invoice.internalNotes ?? '',
    lineItems: invoice.lineItems.map(LineItemDraft.fromLineItem).toList(),
  );

  double get subtotal =>
      round2(lineItems.fold(0.0, (sum, item) => sum + item.total));
  double get taxAmount => round2(subtotal * taxRate / 100);
  double get total => round2(subtotal + taxAmount);

  /// Problems that stop the invoice being saved, in the order the form shows them.
  List<String> validate() {
    final problems = <String>[];
    if (client == null) problems.add('Choose a client.');
    if (dueDate.isBefore(issueDate)) {
      problems.add('The due date must be on or after the issue date.');
    }
    if (taxRate < 0 || taxRate > 100) {
      problems.add('The tax rate must be between 0 and 100.');
    }
    if (lineItems.isEmpty) {
      problems.add('Add at least one line item.');
    } else {
      for (var i = 0; i < lineItems.length; i++) {
        final item = lineItems[i];
        if (item.description.trim().isEmpty) {
          problems.add('Line ${i + 1} needs a description.');
        }
        if (item.quantity <= 0) {
          problems.add('Line ${i + 1} needs a quantity above zero.');
        }
        if (item.unitPrice < 0) {
          problems.add('Line ${i + 1} cannot have a negative price.');
        }
      }
    }
    return problems;
  }

  bool get isValid => validate().isEmpty;

  InvoiceDraft copyWith({
    InvoiceClient? client,
    String? title,
    DateTime? issueDate,
    DateTime? dueDate,
    double? taxRate,
    String? notes,
    String? internalNotes,
    List<LineItemDraft>? lineItems,
  }) => InvoiceDraft(
    id: id,
    client: client ?? this.client,
    title: title ?? this.title,
    issueDate: issueDate ?? this.issueDate,
    dueDate: dueDate ?? this.dueDate,
    taxRate: taxRate ?? this.taxRate,
    notes: notes ?? this.notes,
    internalNotes: internalNotes ?? this.internalNotes,
    lineItems: lineItems ?? this.lineItems,
  );

  /// Picking a client applies their payment terms to the due date.
  InvoiceDraft withClient(InvoiceClient client) => copyWith(
    client: client,
    dueDate: issueDate.add(Duration(days: client.dueDays)),
  );

  InvoiceDraft addLine(LineItemDraft line) =>
      copyWith(lineItems: [...lineItems, line]);

  InvoiceDraft updateLine(int index, LineItemDraft line) {
    final next = [...lineItems];
    next[index] = line;
    return copyWith(lineItems: next);
  }

  InvoiceDraft removeLine(int index) {
    final next = [...lineItems]..removeAt(index);
    return copyWith(lineItems: next);
  }

  Map<String, dynamic> toJson() => {
    'client_id': client?.id,
    'title': title.trim().isEmpty ? null : title.trim(),
    'issue_date': isoDate(issueDate),
    'due_date': isoDate(dueDate),
    'tax_rate': taxRate,
    'notes': notes.trim().isEmpty ? null : notes.trim(),
    'internal_notes': internalNotes.trim().isEmpty
        ? null
        : internalNotes.trim(),
    'line_items': lineItems.map((l) => l.toJson()).toList(),
  };
}
