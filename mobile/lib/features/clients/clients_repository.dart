import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/json.dart';
import '../../core/refresh.dart';
import 'client_models.dart';

final clientsRepositoryProvider = Provider<ClientsRepository>(
  (ref) => ClientsRepository(ref.watch(apiClientProvider)),
);

/// Every active client; small enough to filter on the phone.
final clientsProvider = FutureProvider.autoDispose<List<Client>>((ref) {
  ref.watch(refreshTickProvider);
  return ref.watch(clientsRepositoryProvider).list();
});

final clientDetailProvider = FutureProvider.autoDispose.family<Client, int>((
  ref,
  id,
) {
  ref.watch(refreshTickProvider);
  return ref.watch(clientsRepositoryProvider).fetch(id);
});

class ClientsRepository {
  ClientsRepository(this._api);

  final ApiClient _api;

  Future<List<Client>> list({String? search}) async {
    final json = await _api.getObject(
      '/clients',
      query: {
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      },
    );
    return asList(json['data']).map(Client.fromJson).toList();
  }

  Future<Client> fetch(int id) async =>
      Client.fromJson(await _api.getObject('/clients/$id'));

  Future<Client> create({
    required String companyName,
    String billingTerms = 'net_30',
    String? billingEmail,
    String? contactName,
    String? contactEmail,
    String? contactPhone,
  }) async {
    String? clean(String? value) =>
        (value == null || value.trim().isEmpty) ? null : value.trim();
    final json = await _api.postObject(
      '/clients',
      body: {
        'company_name': companyName.trim(),
        'billing_terms': billingTerms,
        'billing_email': clean(billingEmail),
        'contact_name': clean(contactName),
        'contact_email': clean(contactEmail),
        'contact_phone': clean(contactPhone),
      },
    );
    return Client.fromJson(json);
  }
}
