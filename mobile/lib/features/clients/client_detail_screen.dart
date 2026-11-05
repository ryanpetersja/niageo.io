import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../invoices/invoice_list_controller.dart';
import '../invoices/widgets/invoice_tile.dart';
import 'clients_repository.dart';

class ClientDetailScreen extends ConsumerWidget {
  const ClientDetailScreen({super.key, required this.clientId});

  final int clientId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final client = ref.watch(clientDetailProvider(clientId));

    return Scaffold(
      appBar: AppBar(title: Text(client.value?.companyName ?? 'Client')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/invoices/new?client=$clientId'),
        icon: const Icon(Icons.add_rounded),
        label: const Text('New invoice'),
      ),
      body: client.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorView(
          error: e,
          onRetry: () => ref.invalidate(clientDetailProvider(clientId)),
        ),
        data: (c) => RefreshIndicator(
          onRefresh: () => ref.refresh(clientDetailProvider(clientId).future),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 96),
            children: [
              SectionCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      c.companyName,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      [
                        c.billingTermsLabel,
                        if ((c.billingEmail ?? '').isNotEmpty) c.billingEmail!,
                      ].join(' · '),
                      style: const TextStyle(color: AppColors.textMuted),
                    ),
                    if (c.primaryContact != null) ...[
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          const Icon(
                            Icons.person_outline_rounded,
                            size: 18,
                            color: AppColors.textMuted,
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              [
                                    c.primaryContact!.name,
                                    c.primaryContact!.email,
                                    c.primaryContact!.phone,
                                  ]
                                  .whereType<String>()
                                  .where((s) => s.isNotEmpty)
                                  .join(' · '),
                              style: const TextStyle(fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 14),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: c.outstandingBalance > 0
                            ? AppColors.warnSoft
                            : AppColors.goodSoft,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          const Expanded(
                            child: Text(
                              'Outstanding',
                              style: TextStyle(fontWeight: FontWeight.w600),
                            ),
                          ),
                          Text(
                            money(c.outstandingBalance),
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                              color: c.outstandingBalance > 0
                                  ? AppColors.warn
                                  : AppColors.good,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if ((c.notes ?? '').isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Text(
                        c.notes!,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              if (c.pricingPresets.isNotEmpty) ...[
                const SizedBox(height: 12),
                SectionCard(
                  title: 'Pricing presets',
                  child: Column(
                    children: [
                      for (final preset in c.pricingPresets)
                        KeyValueRow(
                          '${preset.name} (${preset.items.length} line${preset.items.length == 1 ? '' : 's'})',
                          money(preset.total),
                        ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 12),
              SectionCard(
                title: 'Invoices',
                padding: const EdgeInsets.fromLTRB(0, 16, 0, 4),
                trailing: TextButton(
                  onPressed: () {
                    final list = ref.read(invoiceListProvider.notifier);
                    list.setFilter(InvoiceFilter.all);
                    list.setClient(clientId);
                    context.go('/invoices');
                  },
                  child: const Text('See all'),
                ),
                child: c.recentInvoices.isEmpty
                    ? const Padding(
                        padding: EdgeInsets.fromLTRB(16, 0, 16, 12),
                        child: Text(
                          'No invoices yet.',
                          style: TextStyle(color: AppColors.textMuted),
                        ),
                      )
                    : Column(
                        children: [
                          for (final invoice in c.recentInvoices)
                            InvoiceTile(
                              invoice: invoice,
                              showClient: false,
                              onTap: () =>
                                  context.go('/invoices/${invoice.id}'),
                            ),
                        ],
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
