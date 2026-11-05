import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../core/money.dart';
import '../../../core/theme.dart';
import '../../../core/widgets/section_card.dart';
import '../../operations/widgets/ops_badges.dart';
import '../dashboard_models.dart';

/// Operational alerts on the pipeline screen: sites down, bills due, invoices waiting to be generated.
class AttentionCard extends StatelessWidget {
  const AttentionCard({super.key, required this.ops});

  final OperationsSummary ops;

  @override
  Widget build(BuildContext context) {
    final rows = <Widget>[
      for (final endpoint in ops.troubled)
        _Row(
          icon: Icons.cloud_off_rounded,
          color: endpointColors(endpoint.status).fg,
          title: '${endpoint.name} is ${endpoint.status.label.toLowerCase()}',
          subtitle: [
            endpoint.clientName,
            endpoint.lastErrorMessage,
          ].whereType<String>().where((t) => t.isNotEmpty).join(' · '),
          onTap: () => context.go('/ops/uptime/${endpoint.id}'),
        ),
      for (final bill in ops.billAlerts)
        _Row(
          icon: Icons.credit_card_rounded,
          color: billColors(bill.status).fg,
          title: '${bill.serviceName} · ${money(bill.amount)}',
          subtitle: bill.dueText,
          onTap: () => context.go('/ops/subscriptions'),
        ),
      for (final plan in ops.duePlans)
        _Row(
          icon: Icons.receipt_long_rounded,
          color: AppColors.accentInk,
          title: '${plan.name} invoice is ready to generate',
          subtitle:
              '${plan.clientName ?? ''} · ${money(plan.periodTotal ?? 0)}',
          onTap: () => context.go('/ops/billing-plans/${plan.id}'),
        ),
    ];
    if (rows.isEmpty) return const SizedBox.shrink();

    return SectionCard(
      title: 'Needs attention',
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      trailing: TextButton(
        onPressed: () => context.go('/ops'),
        child: const Text('Operations'),
      ),
      child: Column(children: rows.take(4).toList()),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({
    required this.icon,
    required this.color,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (subtitle.isNotEmpty)
                    Text(
                      subtitle,
                      style: TextStyle(fontSize: 12, color: color),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                ],
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
              color: AppColors.textFaint,
              size: 20,
            ),
          ],
        ),
      ),
    );
  }
}
