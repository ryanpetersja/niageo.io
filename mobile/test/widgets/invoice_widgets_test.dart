import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:niageo_ops/core/theme.dart';
import 'package:niageo_ops/features/invoices/invoice_editor_screen.dart';
import 'package:niageo_ops/features/invoices/invoice_models.dart';
import 'package:niageo_ops/features/invoices/widgets/invoice_tile.dart';

Invoice invoice({
  InvoiceStatus status = InvoiceStatus.sent,
  bool overdue = false,
  double paid = 0,
}) => Invoice(
  id: 1,
  invoiceNumber: 'INV-202611-0001',
  title: 'October maintenance',
  status: status,
  client: const InvoiceClient(id: 1, companyName: 'Campus Elite'),
  issueDate: DateTime(2026, 10, 1),
  dueDate: DateTime.now().subtract(Duration(days: overdue ? 3 : -10)),
  total: 275,
  amountPaid: paid,
  balanceDue: 275 - paid,
  isOverdue: overdue,
);

Widget wrap(Widget child) => ProviderScope(
  child: MaterialApp(
    theme: buildAppTheme(),
    home: Scaffold(body: child),
  ),
);

void main() {
  group('InvoiceTile', () {
    testWidgets('shows the balance, the client and an overdue warning', (
      tester,
    ) async {
      await tester.pumpWidget(
        wrap(InvoiceTile(invoice: invoice(overdue: true, paid: 100))),
      );

      expect(find.text('Campus Elite'), findsOneWidget);
      expect(
        find.text('INV-202611-0001 · October maintenance'),
        findsOneWidget,
      );
      expect(find.text(r'$175.00'), findsOneWidget);
      expect(find.text('Overdue by 3 days'), findsOneWidget);
      expect(find.text('Overdue'), findsOneWidget);
    });

    testWidgets('a paid invoice shows its total', (tester) async {
      await tester.pumpWidget(
        wrap(
          InvoiceTile(invoice: invoice(status: InvoiceStatus.paid, paid: 275)),
        ),
      );

      expect(find.text(r'$275.00'), findsOneWidget);
      expect(find.text('Paid'), findsOneWidget);
    });
  });

  group('InvoiceEditorScreen', () {
    testWidgets(
      'adds a line item, updates totals and refuses to save without a client',
      (tester) async {
        await tester.pumpWidget(
          const ProviderScope(child: MaterialApp(home: InvoiceEditorScreen())),
        );
        await tester.pumpAndSettle();

        expect(find.text('New invoice'), findsOneWidget);
        expect(find.text('Choose a client'), findsOneWidget);

        await tester.tap(find.widgetWithText(ActionChip, 'Add line'));
        await tester.pumpAndSettle();
        await tester.enterText(
          find.widgetWithText(TextFormField, 'Description'),
          'Website hosting',
        );
        await tester.enterText(
          find.widgetWithText(TextFormField, 'Quantity'),
          '12',
        );
        await tester.enterText(
          find.widgetWithText(TextFormField, 'Unit price'),
          '45',
        );
        await tester.pump();
        expect(
          find.text(r'$540.00'),
          findsOneWidget,
          reason: 'the sheet previews the line total',
        );
        await tester.tap(find.widgetWithText(FilledButton, 'Add item'));
        await tester.pumpAndSettle();

        expect(find.text('Website hosting'), findsOneWidget);
        expect(find.text(r'Create invoice · $540.00'), findsOneWidget);

        await tester.tap(
          find.widgetWithText(FilledButton, r'Create invoice · $540.00'),
        );
        await tester.pump();
        expect(
          find.text('Choose a client.'),
          findsOneWidget,
          reason: 'validation message in a snackbar',
        );
      },
    );
  });
}
