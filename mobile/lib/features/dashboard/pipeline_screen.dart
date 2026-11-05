import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../../core/widgets/stat_tile.dart';
import '../../core/widgets/status_badge.dart';
import '../auth/auth_controller.dart';
import '../invoices/invoice_list_controller.dart';
import '../invoices/invoice_models.dart';
import '../invoices/widgets/invoice_tile.dart';
import 'dashboard_models.dart';
import 'dashboard_repository.dart';
import 'widgets/attention_card.dart';

/// Home: how much is out there, what is overdue, and where every invoice sits.
class PipelineScreen extends ConsumerWidget {
  const PipelineScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(dashboardProvider);
    final user = ref.watch(authControllerProvider).value?.user;

    return Scaffold(
      appBar: AppBar(title: const Text('Pipeline')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/invoices/new'),
        icon: const Icon(Icons.add_rounded),
        label: const Text('New invoice'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(dashboardProvider.future),
        child: dashboard.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorView(
            error: error,
            onRetry: () => ref.invalidate(dashboardProvider),
          ),
          data: (data) => _PipelineBody(
            data: data,
            firstName: user?.name.split(' ').first ?? '',
          ),
        ),
      ),
    );
  }
}

class _PipelineBody extends ConsumerWidget {
  const _PipelineBody({required this.data, required this.firstName});

  final Dashboard data;
  final String firstName;

  void _openInvoices(
    BuildContext context,
    WidgetRef ref,
    InvoiceFilter filter,
  ) {
    ref.read(invoiceListProvider.notifier).setFilter(filter);
    context.go('/invoices');
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final m = data.metrics;
    final hour = DateTime.now().hour;
    final greeting = hour < 12
        ? 'Good morning'
        : (hour < 18 ? 'Good afternoon' : 'Good evening');
    final openCount =
        (data.stage(InvoiceStatus.sent)?.count ?? 0) +
        (data.stage(InvoiceStatus.overdue)?.count ?? 0);
    final collectedDelta = data.collectedThisMonth - data.collectedLastMonth;

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 96),
      children: [
        Text(
          firstName.isEmpty ? greeting : '$greeting, $firstName',
          style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
        ),
        Text(
          DateFormat('EEEE, MMMM d').format(DateTime.now()),
          style: const TextStyle(color: AppColors.textMuted),
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: StatTile(
                label: 'Outstanding',
                value: compactMoney(m.totalOutstanding),
                caption: '$openCount open invoice${openCount == 1 ? '' : 's'}',
                color: AppColors.warn,
                onTap: () => _openInvoices(context, ref, InvoiceFilter.open),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: StatTile(
                label: 'Overdue',
                value: compactMoney(m.totalOverdue),
                caption:
                    '${m.overdueCount} invoice${m.overdueCount == 1 ? '' : 's'}',
                color: m.totalOverdue > 0 ? AppColors.danger : AppColors.text,
                onTap: () => _openInvoices(context, ref, InvoiceFilter.overdue),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: StatTile(
                label: 'Collected this month',
                value: compactMoney(data.collectedThisMonth),
                caption: collectedDelta == 0
                    ? 'Same as last month'
                    : '${collectedDelta > 0 ? '+' : '−'}${compactMoney(collectedDelta.abs())} vs last month',
                color: AppColors.good,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: StatTile(
                label: 'Collection rate',
                value:
                    '${m.collectionRate.toStringAsFixed(m.collectionRate == m.collectionRate.roundToDouble() ? 0 : 1)}%',
                caption: '${m.paidCount}/${m.invoiceCount} invoices paid',
                color: AppColors.accentInk,
              ),
            ),
          ],
        ),
        if ((data.operations?.alertCount ?? 0) > 0) ...[
          const SizedBox(height: 16),
          AttentionCard(ops: data.operations!),
        ],
        const SizedBox(height: 16),
        SectionCard(
          title: 'Pipeline',
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: _PipelineBars(
            data: data,
            onTap: (f) => _openInvoices(context, ref, f),
          ),
        ),
        if (data.overdueInvoices.isNotEmpty) ...[
          const SizedBox(height: 16),
          SectionCard(
            title: 'Needs attention',
            padding: const EdgeInsets.fromLTRB(0, 16, 0, 4),
            trailing: TextButton(
              onPressed: () =>
                  _openInvoices(context, ref, InvoiceFilter.overdue),
              child: const Text('View all'),
            ),
            child: Column(
              children: [
                for (final invoice in data.overdueInvoices.take(5))
                  InvoiceTile(
                    invoice: invoice,
                    onTap: () => context.go('/invoices/${invoice.id}'),
                  ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 16),
        SectionCard(
          title: 'Recent activity',
          padding: const EdgeInsets.fromLTRB(0, 16, 0, 4),
          trailing: TextButton(
            onPressed: () => _openInvoices(context, ref, InvoiceFilter.all),
            child: const Text('All invoices'),
          ),
          child: data.recentInvoices.isEmpty
              ? const Padding(
                  padding: EdgeInsets.fromLTRB(16, 0, 16, 12),
                  child: Text(
                    'No invoices yet. Create the first one.',
                    style: TextStyle(color: AppColors.textMuted),
                  ),
                )
              : Column(
                  children: [
                    for (final invoice in data.recentInvoices.take(6))
                      InvoiceTile(
                        invoice: invoice,
                        onTap: () => context.go('/invoices/${invoice.id}'),
                      ),
                  ],
                ),
        ),
      ],
    );
  }
}

/// Section padding differs for list-style cards; SectionCard's title row needs its own inset there.
class _PipelineBars extends StatelessWidget {
  const _PipelineBars({required this.data, required this.onTap});

  final Dashboard data;
  final void Function(InvoiceFilter filter) onTap;

  @override
  Widget build(BuildContext context) {
    final stages = [
      (InvoiceStatus.draft, InvoiceFilter.draft),
      (InvoiceStatus.sent, InvoiceFilter.open),
      (InvoiceStatus.overdue, InvoiceFilter.overdue),
      (InvoiceStatus.paid, InvoiceFilter.paid),
    ];
    double amountOf(PipelineStage? stage) => stage == null
        ? 0
        : (stage.status.isOpen || stage.status == InvoiceStatus.draft
              ? stage.balanceDue
              : stage.total);
    final maxAmount = stages
        .map((s) => amountOf(data.stage(s.$1)))
        .fold(0.0, (a, b) => a > b ? a : b);

    return Column(
      children: [
        for (final (status, filter) in stages)
          InkWell(
            onTap: () => onTap(filter),
            borderRadius: BorderRadius.circular(8),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Row(
                children: [
                  SizedBox(width: 92, child: StatusBadge(status)),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                '${data.stage(status)?.count ?? 0} invoice${(data.stage(status)?.count ?? 0) == 1 ? '' : 's'}',
                                style: const TextStyle(
                                  fontSize: 13,
                                  color: AppColors.textMuted,
                                ),
                              ),
                            ),
                            Text(
                              money(amountOf(data.stage(status))),
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontFeatures: [FontFeature.tabularFigures()],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(999),
                          child: LinearProgressIndicator(
                            minHeight: 6,
                            value: maxAmount == 0
                                ? 0
                                : amountOf(data.stage(status)) / maxAmount,
                            backgroundColor: AppColors.surface3,
                            color: statusColors(status).fg,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 4),
                  const Icon(
                    Icons.chevron_right_rounded,
                    color: AppColors.textFaint,
                    size: 20,
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}
