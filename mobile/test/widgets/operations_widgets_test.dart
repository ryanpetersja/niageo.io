import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:niageo_ops/core/theme.dart';
import 'package:niageo_ops/features/dashboard/dashboard_models.dart';
import 'package:niageo_ops/features/dashboard/widgets/attention_card.dart';
import 'package:niageo_ops/features/invoices/invoice_models.dart';
import 'package:niageo_ops/features/operations/billing_plan_models.dart';
import 'package:niageo_ops/features/operations/operations_repository.dart';
import 'package:niageo_ops/features/operations/subscription_models.dart';
import 'package:niageo_ops/features/operations/subscriptions_screen.dart';
import 'package:niageo_ops/features/operations/uptime_models.dart';
import 'package:niageo_ops/features/operations/uptime_screen.dart';

class FakeOperations implements OperationsRepository {
  final markPaidCalls = <int>[];

  @override
  Future<UptimeOverview> uptime() async => UptimeOverview(
    summary: const UptimeSummary(up: 1, down: 1, total: 2),
    endpoints: [
      MonitoredEndpoint(
        id: 1,
        name: 'Portal',
        url: 'https://portal.test',
        clientName: 'Campus Elite',
        status: EndpointStatus.down,
        lastErrorMessage: 'HTTP 503',
        lastCheckedAt: DateTime.now().subtract(const Duration(minutes: 5)),
      ),
      MonitoredEndpoint(
        id: 2,
        name: 'Snow Landing',
        url: 'https://snow.test',
        status: EndpointStatus.up,
        lastResponseTimeMs: 180,
        lastCheckedAt: DateTime.now().subtract(const Duration(minutes: 2)),
      ),
    ],
  );

  @override
  Future<SubscriptionsOverview> subscriptions() async => SubscriptionsOverview(
    summary: const SubscriptionSummary(
      activeCount: 2,
      totalMonthly: 129.95,
      overdueCount: 1,
      overdueTotal: 40,
      dueSoonCount: 0,
    ),
    bills: [
      SubscriptionBill(
        id: 1,
        serviceName: 'Cloudflare',
        amount: 40,
        status: BillStatus.overdue,
        nextDueDate: DateTime.now().subtract(const Duration(days: 4)),
      ),
      SubscriptionBill(
        id: 2,
        serviceName: 'SendGrid',
        amount: 89.95,
        status: BillStatus.upcoming,
        nextDueDate: DateTime.now().add(const Duration(days: 20)),
      ),
    ],
  );

  @override
  Future<SubscriptionBill> markBillPaid(int id) async {
    markPaidCalls.add(id);
    return SubscriptionBill(
      id: id,
      serviceName: 'Cloudflare',
      amount: 40,
      status: BillStatus.upcoming,
      nextDueDate: DateTime(2026, 12, 1),
    );
  }

  @override
  Future<MonitoredEndpoint> fetchEndpoint(int id) => throw UnimplementedError();
  @override
  Future<MonitoredEndpoint> checkEndpoint(int id) => throw UnimplementedError();
  @override
  Future<PlansOverview> billingPlans() => throw UnimplementedError();
  @override
  Future<BillingPlan> fetchPlan(int id) => throw UnimplementedError();
  @override
  Future<Invoice> generatePlanInvoice(int id) => throw UnimplementedError();
  @override
  Future<BillingPlan> setPlanStatus(int id, PlanStatus status) =>
      throw UnimplementedError();
}

Widget wrap(Widget child, FakeOperations fake) => ProviderScope(
  overrides: [operationsRepositoryProvider.overrideWithValue(fake)],
  child: MaterialApp(theme: buildAppTheme(), home: child),
);

void main() {
  testWidgets('uptime screen lists sites with their status', (tester) async {
    await tester.pumpWidget(wrap(const UptimeScreen(), FakeOperations()));
    await tester.pumpAndSettle();

    expect(find.text('Portal'), findsOneWidget);
    expect(find.text('Snow Landing'), findsOneWidget);
    expect(find.text('Down'), findsWidgets);
    expect(find.text('180 ms'), findsOneWidget);
    expect(find.textContaining('Campus Elite'), findsOneWidget);
  });

  testWidgets(
    'subscriptions screen groups bills and marks one paid after confirming',
    (tester) async {
      final fake = FakeOperations();
      await tester.pumpWidget(wrap(const SubscriptionsScreen(), fake));
      await tester.pumpAndSettle();

      expect(find.text('Cloudflare'), findsOneWidget);
      expect(find.text('SendGrid'), findsOneWidget);
      expect(
        find.text('Overdue'),
        findsOneWidget,
        reason: 'the overdue group header',
      );
      expect(
        find.text(r'$40.00'),
        findsNWidgets(2),
      ); // the bill and the needs-paying tile

      await tester.tap(find.widgetWithText(FilledButton, 'Mark paid').first);
      await tester.pumpAndSettle();
      expect(find.byType(AlertDialog), findsOneWidget);
      await tester.tap(
        find.descendant(
          of: find.byType(AlertDialog),
          matching: find.text('Mark paid'),
        ),
      );
      await tester.pumpAndSettle();

      expect(fake.markPaidCalls, [1]);
      expect(
        find.text('Cloudflare paid. Next due Dec 1, 2026.'),
        findsOneWidget,
      );
    },
  );

  testWidgets('attention card lists sites, bills and plans that need action', (
    tester,
  ) async {
    final ops = OperationsSummary(
      uptime: const UptimeSummary(up: 1, down: 1, total: 2),
      troubled: const [
        MonitoredEndpoint(
          id: 1,
          name: 'Portal',
          url: 'https://portal.test',
          status: EndpointStatus.down,
          lastErrorMessage: 'HTTP 503',
        ),
      ],
      subscriptions: const SubscriptionSummary(overdueCount: 1),
      billAlerts: [
        SubscriptionBill(
          id: 1,
          serviceName: 'Cloudflare',
          amount: 40,
          status: BillStatus.overdue,
          nextDueDate: DateTime.now().subtract(const Duration(days: 4)),
        ),
      ],
      duePlans: const [
        BillingPlan(
          id: 7,
          name: 'Monthly maintenance',
          clientName: 'Campus Elite',
          periodTotal: 450,
          isDue: true,
        ),
      ],
    );
    await tester.pumpWidget(
      MaterialApp(
        theme: buildAppTheme(),
        home: Scaffold(body: AttentionCard(ops: ops)),
      ),
    );

    expect(ops.alertCount, 3);
    expect(find.text('Needs attention'), findsOneWidget);
    expect(find.text('Portal is down'), findsOneWidget);
    expect(find.text(r'Cloudflare · $40.00'), findsOneWidget);
    expect(
      find.text('Monthly maintenance invoice is ready to generate'),
      findsOneWidget,
    );
    expect(find.text('Overdue by 4 days'), findsOneWidget);
  });
}
