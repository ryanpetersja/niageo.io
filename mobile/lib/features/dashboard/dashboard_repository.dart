import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/refresh.dart';
import 'dashboard_models.dart';

final dashboardRepositoryProvider = Provider<DashboardRepository>(
  (ref) => DashboardRepository(ref.watch(apiClientProvider)),
);

/// The pipeline screen's data; refetches whenever something changes elsewhere in the app.
final dashboardProvider = FutureProvider.autoDispose<Dashboard>((ref) {
  ref.watch(refreshTickProvider);
  return ref.watch(dashboardRepositoryProvider).fetch();
});

class DashboardRepository {
  DashboardRepository(this._api);

  final ApiClient _api;

  Future<Dashboard> fetch() async =>
      Dashboard.fromJson(await _api.getObject('/dashboard'));
}
