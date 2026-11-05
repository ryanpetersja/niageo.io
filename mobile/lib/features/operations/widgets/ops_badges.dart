import 'package:flutter/material.dart';

import '../../../core/theme.dart';
import '../billing_plan_models.dart';
import '../subscription_models.dart';
import '../uptime_models.dart';

({Color fg, Color bg}) endpointColors(EndpointStatus status) =>
    switch (status) {
      EndpointStatus.up => (fg: AppColors.good, bg: AppColors.goodSoft),
      EndpointStatus.degraded => (fg: AppColors.warn, bg: AppColors.warnSoft),
      EndpointStatus.down => (fg: AppColors.danger, bg: AppColors.dangerSoft),
      EndpointStatus.unknown => (
        fg: AppColors.textMuted,
        bg: AppColors.surface3,
      ),
    };

({Color fg, Color bg}) billColors(BillStatus status) => switch (status) {
  BillStatus.overdue => (fg: AppColors.danger, bg: AppColors.dangerSoft),
  BillStatus.dueSoon => (fg: AppColors.warn, bg: AppColors.warnSoft),
  BillStatus.upcoming => (fg: AppColors.info, bg: AppColors.infoSoft),
  BillStatus.paid => (fg: AppColors.good, bg: AppColors.goodSoft),
};

({Color fg, Color bg}) planColors(PlanStatus status) => switch (status) {
  PlanStatus.active => (fg: AppColors.good, bg: AppColors.goodSoft),
  PlanStatus.paused => (fg: AppColors.warn, bg: AppColors.warnSoft),
  PlanStatus.ended => (fg: AppColors.textMuted, bg: AppColors.surface3),
};

class PillBadge extends StatelessWidget {
  const PillBadge(
    this.label, {
    super.key,
    required this.colors,
    this.compact = false,
  });

  final String label;
  final ({Color fg, Color bg}) colors;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 8 : 10,
        vertical: compact ? 3 : 4,
      ),
      decoration: BoxDecoration(
        color: colors.bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: colors.fg,
          fontSize: compact ? 11 : 12,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.3,
        ),
      ),
    );
  }
}

/// The coloured dot that says up / degraded / down at a glance.
class StatusDot extends StatelessWidget {
  const StatusDot(this.status, {super.key, this.size = 10});

  final EndpointStatus status;
  final double size;

  @override
  Widget build(BuildContext context) {
    final color = endpointColors(status).fg;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: color,
        shape: BoxShape.circle,
        boxShadow: status.isTrouble
            ? [BoxShadow(color: color.withValues(alpha: 0.5), blurRadius: 6)]
            : null,
      ),
    );
  }
}
