import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_view.dart';
import 'invoice_list_controller.dart';
import 'widgets/invoice_tile.dart';

class InvoicesScreen extends ConsumerStatefulWidget {
  const InvoicesScreen({super.key});

  @override
  ConsumerState<InvoicesScreen> createState() => _InvoicesScreenState();
}

class _InvoicesScreenState extends ConsumerState<InvoicesScreen> {
  final _search = TextEditingController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _search.text = ref.read(invoiceListProvider).search;
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      ref.read(invoiceListProvider.notifier).setSearch(value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(invoiceListProvider);
    final controller = ref.read(invoiceListProvider.notifier);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Invoices'),
        actions: [
          IconButton(
            tooltip: 'New invoice',
            onPressed: () => context.push('/invoices/new'),
            icon: const Icon(Icons.add_rounded),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 10),
            child: TextField(
              controller: _search,
              onChanged: _onSearchChanged,
              textInputAction: TextInputAction.search,
              decoration: InputDecoration(
                hintText: 'Search number, title or client',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: _search.text.isEmpty
                    ? null
                    : IconButton(
                        icon: const Icon(Icons.close_rounded),
                        onPressed: () {
                          _search.clear();
                          _onSearchChanged('');
                          setState(() {});
                        },
                      ),
                isDense: true,
              ),
            ),
          ),
          SizedBox(
            height: 44,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                for (final filter in InvoiceFilter.values)
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(filter.label),
                      selected: state.filter == filter,
                      onSelected: (_) => controller.setFilter(filter),
                    ),
                  ),
                if (state.clientId != null)
                  InputChip(
                    label: const Text('One client'),
                    avatar: const Icon(Icons.business_rounded, size: 16),
                    onDeleted: () => controller.setClient(null),
                  ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: controller.refresh,
              child: _Body(state: state),
            ),
          ),
        ],
      ),
    );
  }
}

class _Body extends ConsumerWidget {
  const _Body({required this.state});

  final InvoiceListState state;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final controller = ref.read(invoiceListProvider.notifier);

    if (state.isLoading && state.items.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (state.error != null && state.items.isEmpty) {
      return ErrorView(error: state.error!, onRetry: controller.refresh);
    }
    if (state.items.isEmpty) {
      final filtered =
          state.filter != InvoiceFilter.all ||
          state.search.isNotEmpty ||
          state.clientId != null;
      return ListView(
        children: [
          SizedBox(
            height: 420,
            child: EmptyState(
              icon: Icons.receipt_long_outlined,
              title: filtered ? 'Nothing matches' : 'No invoices yet',
              message: filtered
                  ? 'Try another filter or search.'
                  : 'Create your first invoice and it will show up here.',
              actionLabel: filtered ? null : 'New invoice',
              onAction: filtered ? null : () => context.push('/invoices/new'),
            ),
          ),
        ],
      );
    }

    final showFooter = state.hasMore || state.isLoadingMore;
    return ListView.separated(
      padding: const EdgeInsets.only(bottom: 24),
      itemCount: state.items.length + 1 + (showFooter ? 1 : 0),
      separatorBuilder: (_, index) => index == 0
          ? const SizedBox.shrink()
          : const Divider(indent: 16, endIndent: 16),
      itemBuilder: (context, index) {
        if (index == 0) {
          return Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 4),
            child: Text(
              '${state.total} invoice${state.total == 1 ? '' : 's'}',
              style: const TextStyle(
                fontSize: 12,
                color: AppColors.textFaint,
                fontWeight: FontWeight.w600,
              ),
            ),
          );
        }
        final itemIndex = index - 1;
        if (itemIndex >= state.items.length) {
          // Reaching the footer asks for the next page.
          if (state.hasMore && !state.isLoadingMore) {
            Future<void>.microtask(controller.loadMore);
          }
          return const Padding(
            padding: EdgeInsets.all(20),
            child: Center(
              child: SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            ),
          );
        }
        final invoice = state.items[itemIndex];
        return InvoiceTile(
          invoice: invoice,
          onTap: () => context.go('/invoices/${invoice.id}'),
        );
      },
    );
  }
}
