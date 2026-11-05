import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/refresh.dart';
import '../invoices/invoice_models.dart';
import 'billing_plan_models.dart';
import 'subscription_models.dart';
import 'uptime_models.dart';

final operationsRepositoryProvider = Provider<OperationsRepository>(
  (ref) => OperationsRepository(ref.watch(apiClientProvider)),
);

final uptimeProvider = FutureProvider.autoDispose<UptimeOverview>((ref) {
  ref.watch(refreshTickProvider);
  return ref.watch(operationsRepositoryProvider).uptime();
});

final endpointDetailProvider = FutureProvider.autoDispose
    .family<MonitoredEndpoint, int>((ref, id) {
      ref.watch(refreshTickProvider);
      return ref.watch(operationsRepositoryProvider).fetchEndpoint(id);
    });

final subscriptionsProvider = FutureProvider.autoDispose<SubscriptionsOverview>(
  (ref) {
    ref.watch(refreshTickProvider);
    return ref.watch(operationsRepositoryProvider).subscriptions();
  },
);

final billingPlansProvider = FutureProvider.autoDispose<PlansOverview>((ref) {
  ref.watch(refreshTickProvider);
  return ref.watch(operationsRepositoryProvider).billingPlans();
});

final planDetailProvider = FutureProvider.autoDispose.family<BillingPlan, int>((
  ref,
  id,
) {
  ref.watch(refreshTickProvider);
  return ref.watch(operationsRepositoryProvider).fetchPlan(id);
});

/// Uptime monitoring, subscription bills and recurring billing plans.
class OperationsRepository {
  OperationsRepository(this._api);

  final ApiClient _api;

  Future<UptimeOverview> uptime() async =>
      UptimeOverview.fromJson(await _api.getObject('/uptime'));

  Future<MonitoredEndpoint> fetchEndpoint(int id) async =>
      MonitoredEndpoint.fromJson(await _api.getObject('/uptime/$id'));

  /// Pings the site now; the server answers with the endpoint and its fresh result.
  Future<MonitoredEndpoint> checkEndpoint(int id) async =>
      MonitoredEndpoint.fromJson(await _api.postObject('/uptime/$id/check'));

  Future<SubscriptionsOverview> subscriptions() async =>
      SubscriptionsOverview.fromJson(await _api.getObject('/subscriptions'));

  Future<SubscriptionBill> markBillPaid(int id) async =>
      SubscriptionBill.fromJson(
        await _api.postObject('/subscriptions/$id/mark-paid'),
      );

  Future<PlansOverview> billingPlans() async =>
      PlansOverview.fromJson(await _api.getObject('/billing-plans'));

  Future<BillingPlan> fetchPlan(int id) async =>
      BillingPlan.fromJson(await _api.getObject('/billing-plans/$id'));

  /// Creates the next period's draft invoice and returns it.
  Future<Invoice> generatePlanInvoice(int id) async =>
      Invoice.fromJson(await _api.postObject('/billing-plans/$id/generate'));

  Future<BillingPlan> setPlanStatus(int id, PlanStatus status) async =>
      BillingPlan.fromJson(
        await _api.postObject(
          '/billing-plans/$id/status',
          body: {'status': status.name},
        ),
      );
}
