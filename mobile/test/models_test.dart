import 'package:flutter_test/flutter_test.dart';
import 'package:niageo_ops/features/clients/client_models.dart';
import 'package:niageo_ops/features/dashboard/dashboard_models.dart';
import 'package:niageo_ops/features/invoices/invoice_models.dart';

const invoiceJson = {
  'id': 12,
  'invoice_number': 'INV-202611-0012',
  'title': null,
  'status': 'sent',
  'client': {
    'id': 3,
    'company_name': 'Campus Elite',
    'billing_email': 'ap@ce.test',
    'due_days': 30,
    'contacts': [],
  },
  'issue_date': '2026-10-01',
  'due_date': '2026-10-31',
  'days_until_due': -5,
  'subtotal': '250.00',
  'tax_rate': '10.00',
  'tax_amount': '25.00',
  'total': '275.00',
  'amount_paid': 100,
  'balance_due': 175,
  'is_overdue': true,
  'is_recurring': false,
  'line_items': [
    {
      'id': 1,
      'description': 'Hosting',
      'quantity': 2,
      'unit_price': 100,
      'total': 200,
    },
    {
      'id': 2,
      'description': 'Support',
      'quantity': 1,
      'unit_price': 50,
      'total': 50,
    },
  ],
  'payments': [
    {
      'id': 9,
      'amount': '100.00',
      'payment_date': '2026-10-15',
      'payment_method': 'Bank transfer',
      'recorded_by': 'Admin',
    },
  ],
  'status_history': [
    {
      'from_status': 'draft',
      'to_status': 'sent',
      'notes': null,
      'changed_by': 'Admin',
      'created_at': '2026-10-01T10:00:00+00:00',
    },
    {
      'from_status': null,
      'to_status': 'draft',
      'notes': 'Invoice created',
      'changed_by': 'Admin',
      'created_at': '2026-10-01T09:00:00+00:00',
    },
  ],
  'valid_transitions': ['draft', 'paid', 'overdue', 'cancelled'],
  'can_edit': false,
  'can_delete': false,
  'can_record_payment': true,
  'pdf_url': 'https://example.test/api/v1/invoices/12/pdf',
  'updated_at': '2026-10-15T12:00:00+00:00',
};

void main() {
  group('Invoice', () {
    test('parses the API payload, including decimals sent as strings', () {
      final invoice = Invoice.fromJson(invoiceJson);

      expect(invoice.id, 12);
      expect(invoice.status, InvoiceStatus.sent);
      expect(
        invoice.effectiveStatus,
        InvoiceStatus.overdue,
        reason: 'sent and past due reads as overdue',
      );
      expect(invoice.clientName, 'Campus Elite');
      expect(invoice.displayTitle, 'INV-202611-0012');
      expect(invoice.subtotal, 250);
      expect(invoice.taxRate, 10);
      expect(invoice.total, 275);
      expect(invoice.amountPaid, 100);
      expect(invoice.balanceDue, 175);
      expect(invoice.dueDate, DateTime(2026, 10, 31));
      expect(invoice.lineItems.length, 2);
      expect(invoice.lineItems.first.total, 200);
      expect(invoice.payments.single.paymentMethod, 'Bank transfer');
      expect(invoice.statusHistory.first.fromStatus, InvoiceStatus.draft);
      expect(invoice.statusHistory.last.fromStatus, isNull);
      expect(invoice.validTransitions, [
        InvoiceStatus.draft,
        InvoiceStatus.paid,
        InvoiceStatus.overdue,
        InvoiceStatus.cancelled,
      ]);
      expect(invoice.canRecordPayment, isTrue);
      expect(invoice.canEdit, isFalse);
    });

    test('unknown or missing fields fall back safely', () {
      final invoice = Invoice.fromJson({
        'id': '5',
        'invoice_number': 'X',
        'status': 'bogus',
      });
      expect(invoice.id, 5);
      expect(invoice.status, InvoiceStatus.draft);
      expect(invoice.client, isNull);
      expect(invoice.lineItems, isEmpty);
      expect(invoice.validTransitions, isEmpty);
    });

    test('status labels and actions', () {
      expect(InvoiceStatus.parse('paid').label, 'Paid');
      expect(InvoiceStatus.sent.actionLabel, 'Mark as sent');
      expect(InvoiceStatus.cancelled.actionLabel, 'Cancel invoice');
      expect(InvoiceStatus.overdue.isOpen, isTrue);
      expect(InvoiceStatus.paid.isOpen, isFalse);
    });
  });

  test('Paginated reads Laravel meta', () {
    final page = Paginated.fromJson({
      'data': [invoiceJson, invoiceJson],
      'links': {},
      'meta': {'current_page': 2, 'last_page': 3, 'total': 70},
    }, Invoice.fromJson);

    expect(page.items.length, 2);
    expect(page.currentPage, 2);
    expect(page.hasMore, isTrue);
    expect(page.total, 70);
    expect(Paginated.fromJson({'data': []}, Invoice.fromJson).hasMore, isFalse);
  });

  test('Dashboard pipeline stages', () {
    final dashboard = Dashboard.fromJson({
      'metrics': {
        'total_revenue': 150760.4,
        'total_outstanding': '520312.54',
        'total_overdue': 0,
        'invoice_count': 11,
        'paid_count': 1,
        'overdue_count': 0,
        'collection_rate': 9.1,
      },
      'pipeline': [
        {'status': 'draft', 'count': 2, 'total': 100, 'balance_due': 100},
        {'status': 'sent', 'count': 8, 'total': 900, 'balance_due': 520},
      ],
      'collected_this_month': 1200,
      'collected_last_month': 800,
      'recent_invoices': [invoiceJson],
      'overdue_invoices': [],
    });

    expect(dashboard.metrics.totalOutstanding, 520312.54);
    expect(dashboard.stage(InvoiceStatus.sent)?.balanceDue, 520);
    expect(dashboard.stage(InvoiceStatus.paid), isNull);
    expect(dashboard.recentInvoices.single.id, 12);
    expect(dashboard.collectedLastMonth, 800);
  });

  test('Client with presets and contacts', () {
    final client = Client.fromJson({
      'id': 3,
      'company_name': 'Campus Elite',
      'billing_terms': 'net_15',
      'billing_terms_label': 'Net 15',
      'due_days': 15,
      'is_active': 1,
      'outstanding_balance': '175.00',
      'contacts': [
        {'id': 1, 'name': 'Sam', 'email': 's@ce.test', 'is_primary': false},
        {'id': 2, 'name': 'Pat', 'email': 'p@ce.test', 'is_primary': true},
      ],
      'pricing_presets': [
        {
          'id': 7,
          'name': 'Quarterly bundle',
          'total': 1200,
          'items': [
            {'description': 'Hosting', 'quantity': 3, 'unit_price': 400},
          ],
        },
      ],
    });

    expect(client.dueDays, 15);
    expect(client.isActive, isTrue);
    expect(client.outstandingBalance, 175);
    expect(client.primaryContact?.name, 'Pat');
    expect(client.pricingPresets.single.items.single.unitPrice, 400);
    expect(client.toInvoiceClient().dueDays, 15);
  });
}
