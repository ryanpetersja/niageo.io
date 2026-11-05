import 'package:flutter_test/flutter_test.dart';
import 'package:niageo_ops/features/invoices/invoice_draft.dart';
import 'package:niageo_ops/features/invoices/invoice_models.dart';

void main() {
  final today = DateTime(2026, 11, 5);

  group('InvoiceDraft', () {
    test('totals come from the lines and the tax rate, rounded to cents', () {
      final draft = InvoiceDraft.blank(today: today).copyWith(
        taxRate: 10,
        lineItems: const [
          LineItemDraft(description: 'Hosting', quantity: 2, unitPrice: 100),
          LineItemDraft(description: 'Support', quantity: 3, unitPrice: 33.33),
        ],
      );

      expect(draft.lineItems[1].total, 99.99);
      expect(draft.subtotal, 299.99);
      expect(draft.taxAmount, 30.0);
      expect(draft.total, 329.99);
    });

    test(
      'a blank draft is due 30 days out; picking a client applies its terms',
      () {
        final draft = InvoiceDraft.blank(today: today);
        expect(draft.issueDate, DateTime(2026, 11, 5));
        expect(draft.dueDate, DateTime(2026, 12, 5));

        const client = InvoiceClient(
          id: 1,
          companyName: 'Campus Elite',
          dueDays: 15,
        );
        final withClient = draft.withClient(client);
        expect(withClient.client?.id, 1);
        expect(withClient.dueDate, DateTime(2026, 11, 20));
      },
    );

    test('validation lists every problem in form order', () {
      final draft = InvoiceDraft.blank(today: today)
          .copyWith(dueDate: DateTime(2026, 11, 1));
      expect(draft.validate(), [
        'Choose a client.',
        'The due date must be on or after the issue date.',
        'Add at least one line item.',
      ]);

      final bad = draft.copyWith(
        client: const InvoiceClient(id: 1, companyName: 'X'),
        dueDate: DateTime(2026, 11, 30),
        lineItems: const [
          LineItemDraft(description: '  ', quantity: 0, unitPrice: -1),
        ],
      );
      expect(bad.validate(), [
        'Line 1 needs a description.',
        'Line 1 needs a quantity above zero.',
        'Line 1 cannot have a negative price.',
      ]);
      expect(bad.isValid, isFalse);
    });

    test('line edits are immutable and keep order', () {
      final base = InvoiceDraft.blank(today: today);
      final one = base.addLine(
        const LineItemDraft(description: 'A', unitPrice: 1),
      );
      final two = one.addLine(
        const LineItemDraft(description: 'B', unitPrice: 2),
      );
      final edited = two.updateLine(
        0,
        const LineItemDraft(description: 'A2', unitPrice: 5),
      );
      final removed = edited.removeLine(1);

      expect(base.lineItems, isEmpty);
      expect(one.lineItems.length, 1);
      expect(two.lineItems.map((l) => l.description), ['A', 'B']);
      expect(edited.lineItems.map((l) => l.description), ['A2', 'B']);
      expect(removed.lineItems.map((l) => l.description), ['A2']);
    });

    test('toJson is what the API expects', () {
      final draft = InvoiceDraft.blank(today: today)
          .withClient(
            const InvoiceClient(id: 4, companyName: 'Initech', dueDays: 30),
          )
          .copyWith(
            title: '  ',
            notes: 'Thanks ',
            taxRate: 15,
            lineItems: const [
              LineItemDraft(
                description: ' Hosting ',
                quantity: 2,
                unitPrice: 100,
              ),
            ],
          );

      expect(draft.toJson(), {
        'client_id': 4,
        'title': null,
        'issue_date': '2026-11-05',
        'due_date': '2026-12-05',
        'tax_rate': 15.0,
        'notes': 'Thanks',
        'internal_notes': null,
        'line_items': [
          {'description': 'Hosting', 'quantity': 2.0, 'unit_price': 100.0},
        ],
      });
    });

    test('fromInvoice carries over everything editable', () {
      final invoice = Invoice.fromJson({
        'id': 7,
        'invoice_number': 'INV-202611-0007',
        'title': 'October',
        'status': 'draft',
        'client': {'id': 2, 'company_name': 'Acme', 'due_days': 60},
        'issue_date': '2026-11-01',
        'due_date': '2026-12-31',
        'tax_rate': '15.00',
        'notes': 'n',
        'internal_notes': 'i',
        'line_items': [
          {
            'id': 1,
            'description': 'Hosting',
            'quantity': '2.00',
            'unit_price': '45.00',
            'total': '90.00',
          },
        ],
        'can_edit': true,
      });

      final draft = InvoiceDraft.fromInvoice(invoice);
      expect(draft.id, 7);
      expect(draft.isNew, isFalse);
      expect(draft.client?.companyName, 'Acme');
      expect(draft.title, 'October');
      expect(draft.taxRate, 15);
      expect(draft.dueDate, DateTime(2026, 12, 31));
      expect(draft.lineItems.single.unitPrice, 45);
      expect(draft.total, 103.5);
    });
  });
}
