import 'package:flutter/material.dart';

import '../../../core/dates.dart';
import '../../../core/money.dart';
import '../../../core/theme.dart';
import '../../../core/widgets/status_badge.dart';
import '../invoice_models.dart';

/// One row of an invoice list: who and what on the left, how much and where it stands on the right.
class InvoiceTile extends StatelessWidget {
  const InvoiceTile({
    super.key,
    required this.invoice,
    this.onTap,
    this.showClient = true,
  });

  final Invoice invoice;
  final VoidCallback? onTap;
  final bool showClient;

  @override
  Widget build(BuildContext context) {
    final status = invoice.effectiveStatus;
    final amount = status.isOpen ? invoice.balanceDue : invoice.total;
    final (String secondLine, Color secondColor) = switch (status) {
      InvoiceStatus.draft => (
        'Draft · issued ${shortDate(invoice.issueDate)}',
        AppColors.textFaint,
      ),
      InvoiceStatus.sent => (dueLabel(invoice.dueDate), AppColors.textMuted),
      InvoiceStatus.overdue => (dueLabel(invoice.dueDate), AppColors.danger),
      InvoiceStatus.paid => ('Paid · ${money(invoice.total)}', AppColors.good),
      InvoiceStatus.cancelled => ('Cancelled', AppColors.textFaint),
    };

    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    showClient && invoice.clientName.isNotEmpty
                        ? invoice.clientName
                        : invoice.displayTitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    showClient && invoice.clientName.isNotEmpty
                        ? '${invoice.invoiceNumber}${invoice.title == null || invoice.title!.isEmpty ? '' : ' · ${invoice.title}'}'
                        : invoice.invoiceNumber,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 13,
                      color: AppColors.textMuted,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    secondLine,
                    style: TextStyle(
                      fontSize: 12,
                      color: secondColor,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  money(amount),
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    fontFeatures: [FontFeature.tabularFigures()],
                  ),
                ),
                const SizedBox(height: 6),
                StatusBadge(status, compact: true),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
