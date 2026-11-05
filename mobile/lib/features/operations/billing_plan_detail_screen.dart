import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/dates.dart';
import '../../core/money.dart';
import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../../core/widgets/sheets.dart';
import '../invoices/widgets/invoice_tile.dart';
import 'billing_plan_models.dart';
import 'operations_repository.dart';
import 'widgets/ops_badges.dart';

class BillingPlanDetailScreen extends ConsumerStatefulWidget {
  const BillingPlanDetailScreen({super.key, required this.planId});

  final int planId;

  @override
  ConsumerState<BillingPlanDetailScreen> createState() =>
      _BillingPlanDetailScreenState();
}

class _BillingPlanDetailScreenState
    extends ConsumerState<BillingPlanDetailScreen> {
  bool _busy = false;

  OperationsRepository get _repo => ref.read(operationsRepositoryProvider);

  Future<void> _run(Future<void> Function() action) async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      await action();
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _generate(BillingPlan plan) async {
    final period = plan.upcoming.firstOrNull;
    final ok = await confirm(
      context,
      title: 'Generate the next invoice?',
      message: period == null
          ? 'A draft invoice for the next period will be created.'
          : 'A draft for ${period.label} (${money(plan.periodTotal ?? 0)}) will be created, due ${shortDate(period.dueDate)}. You can edit it before sending.',
      confirmLabel: 'Generate',
    );
    if (!ok) return;
    await _run(() async {
      final invoice = await _repo.generatePlanInvoice(plan.id);
      ref.read(refreshTickProvider.notifier).bump();
      if (mounted) context.go('/invoices/${invoice.id}');
    });
  }

  Future<void> _setStatus(BillingPlan plan, PlanStatus status) async {
    final ok = await confirm(
      context,
      title: switch (status) {
        PlanStatus.paused => 'Pause ${plan.name}?',
        PlanStatus.ended => 'End ${plan.name}?',
        PlanStatus.active => 'Resume ${plan.name}?',
      },
      message: switch (status) {
        PlanStatus.paused =>
          'No invoices are generated while a plan is paused.',
        PlanStatus.ended =>
          'No more invoices will be generated. Existing invoices are kept.',
        PlanStatus.active => 'Invoices resume from the next period.',
      },
      confirmLabel: status == PlanStatus.active
          ? 'Resume'
          : (status == PlanStatus.paused ? 'Pause' : 'End plan'),
      destructive: status == PlanStatus.ended,
    );
    if (!ok) return;
    await _run(() async {
      await _repo.setPlanStatus(plan.id, status);
      ref.read(refreshTickProvider.notifier).bump();
    });
  }

  @override
  Widget build(BuildContext context) {
    final plan = ref.watch(planDetailProvider(widget.planId));

    return Scaffold(
      appBar: AppBar(title: Text(plan.value?.name ?? 'Billing plan')),
      body: plan.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorView(
          error: e,
          onRetry: () => ref.invalidate(planDetailProvider(widget.planId)),
        ),
        data: (p) => RefreshIndicator(
          onRefresh: () =>
              ref.refresh(planDetailProvider(widget.planId).future),
          child: Stack(
            children: [
              ListView(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                children: [
                  SectionCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                p.name,
                                style: const TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                            PillBadge(
                              p.status.label,
                              colors: planColors(p.status),
                            ),
                          ],
                        ),
                        if (p.clientName != null)
                          Text(
                            p.clientName!,
                            style: const TextStyle(color: AppColors.textMuted),
                          ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: _Cell(
                                'Per ${p.billingCycle == 'quarterly' ? 'quarter' : 'month'}',
                                money(p.periodTotal ?? 0),
                              ),
                            ),
                            Expanded(
                              child: _Cell(
                                'Next invoice',
                                p.nextIssueDate == null
                                    ? '—'
                                    : shortDate(p.nextIssueDate!),
                                caption: p.isDue ? 'ready now' : null,
                                captionColor: AppColors.warn,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Expanded(
                              child: _Cell(
                                'Terms',
                                'Due ${p.dueDays} days after issue',
                              ),
                            ),
                            Expanded(
                              child: _Cell(
                                'Issued',
                                p.issueDaysBefore == 0
                                    ? 'On the period start'
                                    : '${p.issueDaysBefore} days before',
                              ),
                            ),
                          ],
                        ),
                        if (p.canGenerate) ...[
                          const SizedBox(height: 14),
                          FilledButton.icon(
                            onPressed: _busy ? null : () => _generate(p),
                            icon: const Icon(Icons.receipt_long_rounded),
                            label: Text(
                              p.isDue
                                  ? 'Generate invoice now'
                                  : 'Generate next invoice early',
                            ),
                          ),
                        ],
                        Wrap(
                          alignment: WrapAlignment.center,
                          children: [
                            if (p.status == PlanStatus.active)
                              TextButton(
                                onPressed: _busy
                                    ? null
                                    : () => _setStatus(p, PlanStatus.paused),
                                child: const Text('Pause'),
                              ),
                            if (p.status == PlanStatus.paused)
                              TextButton(
                                onPressed: _busy
                                    ? null
                                    : () => _setStatus(p, PlanStatus.active),
                                child: const Text('Resume'),
                              ),
                            if (p.status != PlanStatus.ended)
                              TextButton(
                                onPressed: _busy
                                    ? null
                                    : () => _setStatus(p, PlanStatus.ended),
                                style: TextButton.styleFrom(
                                  foregroundColor: AppColors.danger,
                                ),
                                child: const Text('End plan'),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  if (p.upcoming.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    SectionCard(
                      title: 'Upcoming periods',
                      child: Column(
                        children: [
                          for (final period in p.upcoming)
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 5),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          period.label,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        Text(
                                          'Issue ${shortDate(period.issueDate)} · due ${shortDate(period.dueDate)}',
                                          style: const TextStyle(
                                            fontSize: 12,
                                            color: AppColors.textMuted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Text(
                                    money(p.periodTotal ?? 0),
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  SectionCard(
                    title: 'Package',
                    child: p.items.isEmpty
                        ? const Text(
                            'This plan has no line items; add them on the web app before generating.',
                            style: TextStyle(color: AppColors.warn),
                          )
                        : Column(
                            children: [
                              for (final item in p.items)
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 5,
                                  ),
                                  child: Row(
                                    children: [
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              item.description,
                                              style: const TextStyle(
                                                fontWeight: FontWeight.w600,
                                              ),
                                            ),
                                            Text(
                                              '${quantityText(item.quantity)} × ${money(item.unitPrice)}',
                                              style: const TextStyle(
                                                fontSize: 12,
                                                color: AppColors.textMuted,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Text(
                                        money(item.total),
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              const Divider(height: 16),
                              KeyValueRow(
                                'Subtotal',
                                money(p.periodSubtotal ?? 0),
                              ),
                              if (p.taxRate > 0)
                                KeyValueRow(
                                  'Tax (${quantityText(p.taxRate)}%)',
                                  money(
                                    (p.periodTotal ?? 0) -
                                        (p.periodSubtotal ?? 0),
                                  ),
                                ),
                              KeyValueRow(
                                'Total per period',
                                money(p.periodTotal ?? 0),
                                bold: true,
                              ),
                            ],
                          ),
                  ),
                  const SizedBox(height: 12),
                  SectionCard(
                    title: 'Generated invoices',
                    padding: const EdgeInsets.fromLTRB(0, 16, 0, 4),
                    child: p.invoices.isEmpty
                        ? const Padding(
                            padding: EdgeInsets.fromLTRB(16, 0, 16, 12),
                            child: Text(
                              'No invoices generated yet.',
                              style: TextStyle(color: AppColors.textMuted),
                            ),
                          )
                        : Column(
                            children: [
                              for (final invoice in p.invoices)
                                InvoiceTile(
                                  invoice: invoice,
                                  showClient: false,
                                  onTap: () =>
                                      context.go('/invoices/${invoice.id}'),
                                ),
                            ],
                          ),
                  ),
                  if ((p.notes ?? '').isNotEmpty) ...[
                    const SizedBox(height: 12),
                    SectionCard(title: 'Invoice notes', child: Text(p.notes!)),
                  ],
                ],
              ),
              if (_busy)
                const Positioned(
                  left: 0,
                  right: 0,
                  top: 0,
                  child: LinearProgressIndicator(minHeight: 2),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Cell extends StatelessWidget {
  const _Cell(this.label, this.value, {this.caption, this.captionColor});

  final String label;
  final String value;
  final String? caption;
  final Color? captionColor;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w700,
            color: AppColors.textFaint,
            letterSpacing: 0.6,
          ),
        ),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
        if (caption != null)
          Text(
            caption!,
            style: TextStyle(
              fontSize: 12,
              color: captionColor,
              fontWeight: FontWeight.w600,
            ),
          ),
      ],
    );
  }
}
