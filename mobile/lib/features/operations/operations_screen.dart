import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/dates.dart';
import '../../core/money.dart';
import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../dashboard/dashboard_models.dart';
import '../dashboard/dashboard_repository.dart';
import 'operations_repository.dart';
import 'subscription_models.dart';
import 'uptime_models.dart';
import 'widgets/ops_badges.dart';

/// The operations hub: is every site up, which bills are due, what recurring work is about to be invoiced.
class OperationsScreen extends ConsumerWidget {
  const OperationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(dashboardProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Operations')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(dashboardProvider.future),
        child: dashboard.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorView(
            error: error,
            onRetry: () => ref.invalidate(dashboardProvider),
          ),
          data: (data) {
            final ops = data.operations;
            if (ops == null) {
              return const ErrorView(
                error: 'The server does not report operations yet. Deploy the latest version of the web app.',
              );
            }
            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
              children: [
                _UptimeCard(ops: ops),
                const SizedBox(height: 12),
                _SubscriptionsCard(ops: ops),
                const SizedBox(height: 12),
                _RecurringCard(ops: ops),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _UptimeCard extends StatelessWidget {
  const _UptimeCard({required this.ops});

  final OperationsSummary ops;

  @override
  Widget build(BuildContext context) {
    final s = ops.uptime;
    return SectionCard(
      title: 'Uptime',
      trailing: TextButton(
        onPressed: () => context.go('/ops/uptime'),
        child: const Text('All sites'),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: 14,
            runSpacing: 6,
            children: [
              _Count(EndpointStatus.up, s.up),
              _Count(EndpointStatus.degraded, s.degraded),
              _Count(EndpointStatus.down, s.down),
            ],
          ),
          const SizedBox(height: 10),
          if (s.total == 0)
            const Text(
              'No sites are being monitored yet.',
              style: TextStyle(color: AppColors.textMuted),
            )
          else if (ops.troubled.isEmpty)
            const Row(
              children: [
                Icon(
                  Icons.check_circle_rounded,
                  color: AppColors.good,
                  size: 18,
                ),
                SizedBox(width: 8),
                Text(
                  'All sites are up.',
                  style: TextStyle(
                    color: AppColors.good,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            )
          else
            for (final endpoint in ops.troubled)
              ListTile(
                contentPadding: EdgeInsets.zero,
                dense: true,
                leading: StatusDot(endpoint.status),
                title: Text(
                  endpoint.name,
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                subtitle: Text(
                  [
                    endpoint.clientName,
                    endpoint.lastErrorMessage,
                  ].whereType<String>().where((t) => t.isNotEmpty).join(' · '),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                trailing: PillBadge(
                  endpoint.status.label,
                  colors: endpointColors(endpoint.status),
                  compact: true,
                ),
                onTap: () => context.go('/ops/uptime/${endpoint.id}'),
              ),
        ],
      ),
    );
  }
}

class _Count extends StatelessWidget {
  const _Count(this.status, this.count);

  final EndpointStatus status;
  final int count;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        StatusDot(status, size: 8),
        const SizedBox(width: 6),
        Text(
          '$count ${status.label.toLowerCase()}',
          style: const TextStyle(
            fontSize: 13,
            color: AppColors.textMuted,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _SubscriptionsCard extends ConsumerStatefulWidget {
  const _SubscriptionsCard({required this.ops});

  final OperationsSummary ops;

  @override
  ConsumerState<_SubscriptionsCard> createState() => _SubscriptionsCardState();
}

class _SubscriptionsCardState extends ConsumerState<_SubscriptionsCard> {
  int? _paying;

  Future<void> _pay(int id, String name) async {
    setState(() => _paying = id);
    try {
      final bill = await ref
          .read(operationsRepositoryProvider)
          .markBillPaid(id);
      ref.read(refreshTickProvider.notifier).bump();
      if (mounted) {
        showSnack(
          context,
          bill.nextDueDate == null || bill.status == BillStatus.paid
              ? '$name marked as paid.'
              : '$name paid. Next due ${shortDate(bill.nextDueDate!)}.',
        );
      }
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _paying = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.ops.subscriptions;
    return SectionCard(
      title: 'Subscriptions',
      trailing: TextButton(
        onPressed: () => context.go('/ops/subscriptions'),
        child: const Text('All bills'),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${money(s.totalMonthly)} / month across ${s.activeCount} service${s.activeCount == 1 ? '' : 's'}',
            style: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 10),
          if (widget.ops.billAlerts.isEmpty)
            const Row(
              children: [
                Icon(
                  Icons.check_circle_rounded,
                  color: AppColors.good,
                  size: 18,
                ),
                SizedBox(width: 8),
                Text(
                  'Nothing due in the next week.',
                  style: TextStyle(
                    color: AppColors.good,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            )
          else
            for (final bill in widget.ops.billAlerts)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            bill.serviceName,
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          Text(
                            '${money(bill.amount)} · ${bill.dueText}',
                            style: TextStyle(
                              fontSize: 12,
                              color: billColors(bill.status).fg,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ),
                    FilledButton.tonal(
                      onPressed: _paying != null
                          ? null
                          : () => _pay(bill.id, bill.serviceName),
                      style: FilledButton.styleFrom(
                        minimumSize: const Size(0, 36),
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                      ),
                      child: _paying == bill.id
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text('Paid'),
                    ),
                  ],
                ),
              ),
        ],
      ),
    );
  }
}

class _RecurringCard extends StatelessWidget {
  const _RecurringCard({required this.ops});

  final OperationsSummary ops;

  @override
  Widget build(BuildContext context) {
    final next = ops.nextPlan;
    return SectionCard(
      title: 'Recurring billing',
      trailing: TextButton(
        onPressed: () => context.go('/ops/billing-plans'),
        child: const Text('All plans'),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${ops.activePlans} active plan${ops.activePlans == 1 ? '' : 's'} · ${money(ops.plansMonthlyValue)} / month',
            style: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 10),
          if (ops.duePlans.isNotEmpty) ...[
            for (final plan in ops.duePlans)
              ListTile(
                contentPadding: EdgeInsets.zero,
                dense: true,
                leading: const Icon(
                  Icons.receipt_long_rounded,
                  color: AppColors.warn,
                ),
                title: Text(
                  plan.name,
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                subtitle: Text(
                  '${plan.clientName ?? ''} · invoice ready to generate',
                ),
                trailing: Text(
                  money(plan.periodTotal ?? 0),
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                onTap: () => context.go('/ops/billing-plans/${plan.id}'),
              ),
          ] else if (next != null && next.nextIssueDate != null)
            Row(
              children: [
                const Icon(
                  Icons.event_repeat_rounded,
                  color: AppColors.accentInk,
                  size: 18,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Next invoice: ${next.name} on ${shortDate(next.nextIssueDate!)} (${money(next.periodTotal ?? 0)})',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            )
          else
            const Text(
              'No active billing plans.',
              style: TextStyle(color: AppColors.textMuted),
            ),
        ],
      ),
    );
  }
}
