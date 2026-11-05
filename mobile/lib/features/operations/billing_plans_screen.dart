import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/dates.dart';
import '../../core/money.dart';
import '../../core/theme.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/stat_tile.dart';
import 'billing_plan_models.dart';
import 'operations_repository.dart';
import 'widgets/ops_badges.dart';

class BillingPlansScreen extends ConsumerWidget {
  const BillingPlansScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final overview = ref.watch(billingPlansProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Recurring billing')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(billingPlansProvider.future),
        child: overview.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => ErrorView(
            error: e,
            onRetry: () => ref.invalidate(billingPlansProvider),
          ),
          data: (data) {
            if (data.plans.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(
                    height: 420,
                    child: EmptyState(
                      icon: Icons.event_repeat_outlined,
                      title: 'No billing plans',
                      message: 'Set up a client\'s recurring package on the web app and its invoices will be generated on schedule.',
                    ),
                  ),
                ],
              );
            }
            final s = data.summary;
            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
              children: [
                Row(
                  children: [
                    Expanded(
                      child: StatTile(
                        label: 'Recurring / month',
                        value: compactMoney(s.monthlyValue),
                        caption:
                            '${s.activeCount} active plan${s.activeCount == 1 ? '' : 's'}',
                        color: AppColors.good,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: StatTile(
                        label: 'Ready to invoice',
                        value: '${s.dueNowCount}',
                        caption: s.pausedCount > 0
                            ? '${s.pausedCount} paused'
                            : 'generated daily',
                        color: s.dueNowCount > 0
                            ? AppColors.warn
                            : AppColors.text,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                for (final plan in data.plans)
                  PlanTile(
                    plan: plan,
                    onTap: () => context.go('/ops/billing-plans/${plan.id}'),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class PlanTile extends StatelessWidget {
  const PlanTile({super.key, required this.plan, this.onTap});

  final BillingPlan plan;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final next = plan.nextIssueDate;
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      plan.name,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      [
                        if (plan.clientName != null) plan.clientName!,
                        plan.cycleLabel,
                      ].join(' · '),
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textMuted,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      plan.status != PlanStatus.active
                          ? plan.status.label
                          : (next == null
                                ? 'No next period'
                                : (plan.isDue
                                      ? 'Invoice ready to generate'
                                      : 'Next invoice ${shortDate(next)}')),
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: plan.isDue
                            ? AppColors.warn
                            : (plan.status == PlanStatus.active
                                  ? AppColors.accentInk
                                  : AppColors.textFaint),
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
                    money(plan.periodTotal ?? 0),
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 15,
                    ),
                  ),
                  Text(
                    'per ${plan.billingCycle == 'quarterly' ? 'quarter' : 'month'}',
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textFaint,
                    ),
                  ),
                  const SizedBox(height: 4),
                  PillBadge(
                    plan.status.label,
                    colors: planColors(plan.status),
                    compact: true,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
