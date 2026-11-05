import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/refresh.dart';
import 'invoice_models.dart';
import 'invoices_repository.dart';

/// The filter chips on the invoices screen.
enum InvoiceFilter {
  all,
  open,
  draft,
  overdue,
  paid;

  String get label => switch (this) {
    InvoiceFilter.all => 'All',
    InvoiceFilter.open => 'Open',
    InvoiceFilter.draft => 'Drafts',
    InvoiceFilter.overdue => 'Overdue',
    InvoiceFilter.paid => 'Paid',
  };

  /// The API's status parameter; "open" is everything awaiting payment.
  String? get statusParam => switch (this) {
    InvoiceFilter.all => null,
    InvoiceFilter.open => 'sent,overdue',
    InvoiceFilter.draft => 'draft',
    InvoiceFilter.overdue => 'overdue',
    InvoiceFilter.paid => 'paid',
  };
}

class InvoiceListState {
  const InvoiceListState({
    this.filter = InvoiceFilter.all,
    this.search = '',
    this.clientId,
    this.items = const [],
    this.page = 0,
    this.lastPage = 1,
    this.total = 0,
    this.isLoading = true,
    this.isLoadingMore = false,
    this.error,
  });

  final InvoiceFilter filter;
  final String search;
  final int? clientId;
  final List<Invoice> items;
  final int page;
  final int lastPage;
  final int total;

  /// Loading the first page (nothing to show yet, or a filter just changed).
  final bool isLoading;
  final bool isLoadingMore;
  final Object? error;

  bool get hasMore => page < lastPage;

  InvoiceListState copyWith({
    InvoiceFilter? filter,
    String? search,
    int? clientId,
    bool clearClient = false,
    List<Invoice>? items,
    int? page,
    int? lastPage,
    int? total,
    bool? isLoading,
    bool? isLoadingMore,
    Object? error,
    bool clearError = false,
  }) => InvoiceListState(
    filter: filter ?? this.filter,
    search: search ?? this.search,
    clientId: clearClient ? null : (clientId ?? this.clientId),
    items: items ?? this.items,
    page: page ?? this.page,
    lastPage: lastPage ?? this.lastPage,
    total: total ?? this.total,
    isLoading: isLoading ?? this.isLoading,
    isLoadingMore: isLoadingMore ?? this.isLoadingMore,
    error: clearError ? null : (error ?? this.error),
  );
}

final invoiceListProvider =
    NotifierProvider<InvoiceListController, InvoiceListState>(
      InvoiceListController.new,
    );

/// Paged, filterable invoice list. Requests are numbered so a slow page never
/// overwrites a newer filter's results.
class InvoiceListController extends Notifier<InvoiceListState> {
  int _requestId = 0;

  @override
  InvoiceListState build() {
    ref.listen(refreshTickProvider, (_, _) => refresh());
    Future<void>.microtask(refresh);
    return const InvoiceListState();
  }

  Future<void> refresh() => _load(reset: true);

  Future<void> loadMore() async {
    if (state.isLoading || state.isLoadingMore || !state.hasMore) return;
    await _load(reset: false);
  }

  void setFilter(InvoiceFilter filter) {
    if (filter == state.filter) return;
    state = state.copyWith(
      filter: filter,
      items: const [],
      page: 0,
      isLoading: true,
      clearError: true,
    );
    refresh();
  }

  void setSearch(String query) {
    if (query == state.search) return;
    state = state.copyWith(
      search: query,
      items: const [],
      page: 0,
      isLoading: true,
      clearError: true,
    );
    refresh();
  }

  void setClient(int? clientId) {
    if (clientId == state.clientId) return;
    state = state.copyWith(
      clientId: clientId,
      clearClient: clientId == null,
      items: const [],
      page: 0,
      isLoading: true,
      clearError: true,
    );
    refresh();
  }

  Future<void> _load({required bool reset}) async {
    final request = ++_requestId;
    final nextPage = reset ? 1 : state.page + 1;
    state = reset
        ? state.copyWith(isLoading: state.items.isEmpty, clearError: true)
        : state.copyWith(isLoadingMore: true, clearError: true);

    try {
      final page = await ref
          .read(invoicesRepositoryProvider)
          .list(
            page: nextPage,
            status: state.filter.statusParam,
            clientId: state.clientId,
            search: state.search,
          );
      if (request != _requestId) return;
      state = state.copyWith(
        items: reset ? page.items : [...state.items, ...page.items],
        page: page.currentPage,
        lastPage: page.lastPage,
        total: page.total,
        isLoading: false,
        isLoadingMore: false,
      );
    } catch (error) {
      if (request != _requestId) return;
      state = state.copyWith(
        isLoading: false,
        isLoadingMore: false,
        error: error,
      );
    }
  }
}
