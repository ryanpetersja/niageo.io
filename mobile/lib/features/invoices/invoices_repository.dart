import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/config.dart';
import '../../core/dates.dart';
import '../../core/refresh.dart';
import 'invoice_draft.dart';
import 'invoice_models.dart';

final invoicesRepositoryProvider = Provider<InvoicesRepository>(
  (ref) => InvoicesRepository(ref.watch(apiClientProvider)),
);

/// One invoice with everything the detail screen needs.
final invoiceDetailProvider = FutureProvider.autoDispose.family<Invoice, int>((
  ref,
  id,
) {
  ref.watch(refreshTickProvider);
  return ref.watch(invoicesRepositoryProvider).fetch(id);
});

class InvoicesRepository {
  InvoicesRepository(this._api);

  final ApiClient _api;

  Future<Paginated<Invoice>> list({
    int page = 1,
    int perPage = AppConfig.pageSize,
    String? status,
    int? clientId,
    String? search,
  }) async {
    final json = await _api.getObject(
      '/invoices',
      query: {
        'page': page,
        'per_page': perPage,
        if (status != null && status.isNotEmpty) 'status': status,
        'client_id': ?clientId,
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
      },
    );
    return Paginated.fromJson(json, Invoice.fromJson);
  }

  Future<Invoice> fetch(int id) async =>
      Invoice.fromJson(await _api.getObject('/invoices/$id'));

  Future<Invoice> create(InvoiceDraft draft) async => Invoice.fromJson(
    await _api.postObject('/invoices', body: draft.toJson()),
  );

  Future<Invoice> update(InvoiceDraft draft) async => Invoice.fromJson(
    await _api.putObject('/invoices/${draft.id}', body: draft.toJson()),
  );

  Future<void> delete(int id) => _api.delete('/invoices/$id');

  Future<Invoice> transition(int id, InvoiceStatus to, {String? notes}) async =>
      Invoice.fromJson(
        await _api.postObject(
          '/invoices/$id/transition',
          body: {
            'status': to.name,
            if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
          },
        ),
      );

  Future<Invoice> duplicate(int id) async =>
      Invoice.fromJson(await _api.postObject('/invoices/$id/duplicate'));

  Future<Invoice> recordPayment(
    int id, {
    required double amount,
    required DateTime date,
    String? method,
    String? reference,
    String? notes,
  }) async {
    String? clean(String? value) =>
        (value == null || value.trim().isEmpty) ? null : value.trim();
    return Invoice.fromJson(
      await _api.postObject(
        '/invoices/$id/payments',
        body: {
          'amount': amount,
          'payment_date': isoDate(date),
          'payment_method': clean(method),
          'reference': clean(reference),
          'notes': clean(notes),
        },
      ),
    );
  }

  Future<Invoice> deletePayment(int paymentId) async =>
      Invoice.fromJson(await _api.deleteObject('/payments/$paymentId'));

  Future<List<int>> pdfBytes(int id) => _api.getBytes('/invoices/$id/pdf');
}
