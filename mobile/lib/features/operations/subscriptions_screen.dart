import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/dates.dart';
import '../../core/money.dart';
import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/sheets.dart';
import '../../core/widgets/stat_tile.dart';
import 'operations_repository.dart';
import 'subscription_models.dart';
import 'widgets/ops_badges.dart';

class SubscriptionsScreen extends ConsumerStatefulWidget {
  const SubscriptionsScreen({super.key});

  @override
  ConsumerState<SubscriptionsScreen> createState() =>
      _SubscriptionsScreenState();
}

class _SubscriptionsScreenState extends ConsumerState<SubscriptionsScreen> {
  int? _paying;

  Future<void> _markPaid(SubscriptionBill bill) async {
    final ok = await confirm(
      context,
      title: 'Mark ${bill.serviceName} as paid?',
      message: bill.autoRenew
          ? '${money(bill.amount)} ${bill.cycleLabel.toLowerCase()}. The next due date moves one ${bill.billingCycle == 'annual' ? 'year' : (bill.billingCycle == 'quarterly' ? 'quarter' : 'month')} ahead.'
          : '${money(bill.amount)}. This bill does not renew automatically.',
      confirmLabel: 'Mark paid',
    );
    if (!ok) return;
    setState(() => _paying = bill.id);
    try {
      final updated = await ref
          .read(operationsRepositoryProvider)
          .markBillPaid(bill.id);
      ref.read(refreshTickProvider.notifier).bump();
      if (mounted) {
        showSnack(
          context,
          updated.status == BillStatus.paid || updated.nextDueDate == null
              ? '${bill.serviceName} marked as paid.'
              : '${bill.serviceName} paid. Next due ${shortDate(updated.nextDueDate!)}.',
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
    final overview = ref.watch(subscriptionsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Subscriptions')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(subscriptionsProvider.future),
        child: overview.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => ErrorView(
            error: e,
            onRetry: () => ref.invalidate(subscriptionsProvider),
          ),
          data: (data) {
            if (data.bills.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(
                    height: 420,
                    child: EmptyState(
                      icon: Icons.credit_card_outlined,
                      title: 'No subscriptions tracked',
                      message: 'Add the services you pay for (hosting, email, DevOps…) on the web app.',
                    ),
                  ),
                ],
              );
            }
            final s = data.summary;
            final groups = [
              (BillStatus.overdue, data.withStatus(BillStatus.overdue)),
              (BillStatus.dueSoon, data.withStatus(BillStatus.dueSoon)),
              (BillStatus.upcoming, data.withStatus(BillStatus.upcoming)),
              (BillStatus.paid, data.withStatus(BillStatus.paid)),
            ].where((g) => g.$2.isNotEmpty);

            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
              children: [
                Row(
                  children: [
                    Expanded(
                      child: StatTile(
                        label: 'Per month',
                        value: compactMoney(s.totalMonthly),
                        caption:
                            '${s.activeCount} active service${s.activeCount == 1 ? '' : 's'}',
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: StatTile(
                        label: 'Needs paying',
                        value: compactMoney(s.overdueTotal + s.dueSoonTotal),
                        caption:
                            '${s.overdueCount} overdue · ${s.dueSoonCount} due soon',
                        color: s.overdueCount > 0
                            ? AppColors.danger
                            : (s.dueSoonCount > 0
                                  ? AppColors.warn
                                  : AppColors.good),
                      ),
                    ),
                  ],
                ),
                for (final (status, bills) in groups) ...[
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      PillBadge(status.label, colors: billColors(status)),
                      const SizedBox(width: 8),
                      Text(
                        '${bills.length}',
                        style: const TextStyle(
                          color: AppColors.textFaint,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  for (final bill in bills)
                    BillTile(
                      bill: bill,
                      busy: _paying == bill.id,
                      onMarkPaid: bill.isUnpaid && _paying == null
                          ? () => _markPaid(bill)
                          : null,
                    ),
                ],
              ],
            );
          },
        ),
      ),
    );
  }
}

class BillTile extends StatelessWidget {
  const BillTile({
    super.key,
    required this.bill,
    this.busy = false,
    this.onMarkPaid,
  });

  final SubscriptionBill bill;
  final bool busy;
  final VoidCallback? onMarkPaid;

  @override
  Widget build(BuildContext context) {
    final colors = billColors(bill.status);
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 10, 12),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    bill.serviceName,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${bill.categoryLabel} · ${bill.cycleLabel}${bill.autoRenew ? '' : ' · no auto-renew'}',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textMuted,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    bill.status == BillStatus.paid && bill.lastPaidAt != null
                        ? 'Paid ${shortDate(bill.lastPaidAt!)}'
                        : '${bill.dueText}${bill.nextDueDate == null ? '' : ' · ${shortDate(bill.nextDueDate!)}'}',
                    style: TextStyle(
                      fontSize: 12,
                      color: colors.fg,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  money(bill.amount),
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                  ),
                ),
                if (bill.isUnpaid) ...[
                  const SizedBox(height: 6),
                  SizedBox(
                    height: 32,
                    child: FilledButton.tonal(
                      onPressed: onMarkPaid,
                      style: FilledButton.styleFrom(
                        minimumSize: const Size(0, 32),
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                      ),
                      child: busy
                          ? const SizedBox(
                              width: 14,
                              height: 14,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text(
                              'Mark paid',
                              style: TextStyle(fontSize: 12),
                            ),
                    ),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
