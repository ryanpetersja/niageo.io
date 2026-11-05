import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../core/dates.dart';
import '../../core/money.dart';
import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../../core/widgets/sheets.dart';
import '../../core/widgets/status_badge.dart';
import 'invoice_models.dart';
import 'invoices_repository.dart';
import 'widgets/record_payment_sheet.dart';

class InvoiceDetailScreen extends ConsumerStatefulWidget {
  const InvoiceDetailScreen({super.key, required this.invoiceId});

  final int invoiceId;

  @override
  ConsumerState<InvoiceDetailScreen> createState() =>
      _InvoiceDetailScreenState();
}

class _InvoiceDetailScreenState extends ConsumerState<InvoiceDetailScreen> {
  bool _busy = false;

  InvoicesRepository get _repo => ref.read(invoicesRepositoryProvider);

  void _changed() => ref.read(refreshTickProvider.notifier).bump();

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

  Future<void> _transition(Invoice invoice, InvoiceStatus to) async {
    if (to == InvoiceStatus.cancelled || to == InvoiceStatus.paid) {
      final ok = await confirm(
        context,
        title: to == InvoiceStatus.cancelled
            ? 'Cancel ${invoice.invoiceNumber}?'
            : 'Mark ${invoice.invoiceNumber} as paid?',
        message: to == InvoiceStatus.cancelled
            ? 'A cancelled invoice stays on record but no longer counts as outstanding.'
            : 'This marks the full ${money(invoice.total)} as settled without recording a payment. Use "Record payment" to log the money received.',
        confirmLabel: to == InvoiceStatus.cancelled
            ? 'Cancel invoice'
            : 'Mark as paid',
        destructive: to == InvoiceStatus.cancelled,
      );
      if (!ok) return;
    }
    await _run(() async {
      await _repo.transition(invoice.id, to);
      _changed();
      if (mounted) showSnack(context, 'Marked as ${to.label.toLowerCase()}.');
    });
  }

  Future<void> _recordPayment(Invoice invoice) async {
    final updated = await showAppSheet<Invoice>(
      context,
      builder: (_) => RecordPaymentSheet(invoice: invoice),
    );
    if (updated != null) {
      _changed();
      if (mounted) {
        showSnack(
          context,
          updated.status == InvoiceStatus.paid
              ? 'Payment recorded. Invoice is now paid.'
              : 'Payment recorded.',
        );
      }
    }
  }

  Future<void> _deletePayment(Payment payment) async {
    final ok = await confirm(
      context,
      title: 'Remove this payment?',
      message:
          '${money(payment.amount)} on ${shortDate(payment.paymentDate)} will be removed and the balance updated.',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!ok) return;
    await _run(() async {
      await _repo.deletePayment(payment.id);
      _changed();
    });
  }

  Future<void> _sharePdf(Invoice invoice) => _run(() async {
    final bytes = await _repo.pdfBytes(invoice.id);
    final dir = await getTemporaryDirectory();
    final file = File('${dir.path}/${invoice.invoiceNumber}.pdf');
    await file.writeAsBytes(bytes, flush: true);
    await SharePlus.instance.share(
      ShareParams(
        files: [XFile(file.path, mimeType: 'application/pdf')],
        subject: 'Invoice ${invoice.invoiceNumber}',
        text:
            'Invoice ${invoice.invoiceNumber} for ${invoice.clientName}: ${money(invoice.total)}, due ${shortDate(invoice.dueDate)}.',
      ),
    );
  });

  Future<void> _duplicate(Invoice invoice) => _run(() async {
    final copy = await _repo.duplicate(invoice.id);
    _changed();
    if (mounted) context.push('/invoices/${copy.id}/edit');
  });

  Future<void> _delete(Invoice invoice) async {
    final ok = await confirm(
      context,
      title: 'Delete ${invoice.invoiceNumber}?',
      message: 'This cannot be undone.',
      confirmLabel: 'Delete',
      destructive: true,
    );
    if (!ok) return;
    await _run(() async {
      await _repo.delete(invoice.id);
      _changed();
      if (mounted) {
        if (context.canPop()) {
          context.pop();
        } else {
          context.go('/invoices');
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final invoice = ref.watch(invoiceDetailProvider(widget.invoiceId));

    return Scaffold(
      appBar: AppBar(
        title: Text(invoice.value?.invoiceNumber ?? 'Invoice'),
        actions: [
          if (invoice.value?.canEdit == true)
            IconButton(
              tooltip: 'Edit',
              onPressed: () =>
                  context.push('/invoices/${widget.invoiceId}/edit'),
              icon: const Icon(Icons.edit_outlined),
            ),
          if (invoice.value != null)
            PopupMenuButton<String>(
              onSelected: (value) => switch (value) {
                'pdf' => _sharePdf(invoice.value!),
                'duplicate' => _duplicate(invoice.value!),
                'delete' => _delete(invoice.value!),
                _ => null,
              },
              itemBuilder: (_) => [
                const PopupMenuItem(
                  value: 'pdf',
                  child: ListTile(
                    leading: Icon(Icons.ios_share_rounded),
                    title: Text('Share PDF'),
                  ),
                ),
                const PopupMenuItem(
                  value: 'duplicate',
                  child: ListTile(
                    leading: Icon(Icons.copy_rounded),
                    title: Text('Duplicate'),
                  ),
                ),
                if (invoice.value!.canDelete)
                  const PopupMenuItem(
                    value: 'delete',
                    child: ListTile(
                      leading: Icon(
                        Icons.delete_outline_rounded,
                        color: AppColors.danger,
                      ),
                      title: Text('Delete'),
                    ),
                  ),
              ],
            ),
        ],
      ),
      body: invoice.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(
          error: error,
          onRetry: () =>
              ref.invalidate(invoiceDetailProvider(widget.invoiceId)),
        ),
        data: (inv) => RefreshIndicator(
          onRefresh: () =>
              ref.refresh(invoiceDetailProvider(widget.invoiceId).future),
          child: Stack(
            children: [
              ListView(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                children: [
                  _Header(invoice: inv),
                  const SizedBox(height: 12),
                  _Totals(invoice: inv),
                  const SizedBox(height: 12),
                  _Actions(
                    invoice: inv,
                    busy: _busy,
                    onTransition: (to) => _transition(inv, to),
                    onRecordPayment: () => _recordPayment(inv),
                    onSharePdf: () => _sharePdf(inv),
                  ),
                  const SizedBox(height: 12),
                  SectionCard(
                    title: 'Line items',
                    child: Column(
                      children: [
                        for (final item in inv.lineItems)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
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
                                const SizedBox(width: 12),
                                Text(
                                  money(item.total),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontFeatures: [
                                      FontFeature.tabularFigures(),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                  if (inv.canRecordPayment || inv.payments.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    SectionCard(
                      title: 'Payments',
                      trailing: inv.canRecordPayment
                          ? TextButton.icon(
                              onPressed: _busy
                                  ? null
                                  : () => _recordPayment(inv),
                              icon: const Icon(Icons.add_rounded, size: 18),
                              label: const Text('Record'),
                            )
                          : null,
                      child: inv.payments.isEmpty
                          ? const Text(
                              'No payments recorded yet.',
                              style: TextStyle(color: AppColors.textMuted),
                            )
                          : Column(
                              children: [
                                for (final payment in inv.payments)
                                  ListTile(
                                    contentPadding: EdgeInsets.zero,
                                    dense: true,
                                    leading: const Icon(
                                      Icons.payments_outlined,
                                      color: AppColors.good,
                                    ),
                                    title: Text(
                                      money(payment.amount),
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    subtitle: Text(
                                      [
                                            shortDate(payment.paymentDate),
                                            payment.paymentMethod,
                                            payment.reference,
                                          ]
                                          .whereType<String>()
                                          .where((s) => s.isNotEmpty)
                                          .join(' · '),
                                    ),
                                    trailing: IconButton(
                                      tooltip: 'Remove payment',
                                      onPressed: _busy
                                          ? null
                                          : () => _deletePayment(payment),
                                      icon: const Icon(
                                        Icons.delete_outline_rounded,
                                        size: 20,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                    ),
                  ],
                  if ((inv.notes ?? '').isNotEmpty ||
                      (inv.internalNotes ?? '').isNotEmpty) ...[
                    const SizedBox(height: 12),
                    SectionCard(
                      title: 'Notes',
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if ((inv.notes ?? '').isNotEmpty) Text(inv.notes!),
                          if ((inv.internalNotes ?? '').isNotEmpty) ...[
                            const SizedBox(height: 8),
                            const Text(
                              'INTERNAL',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: AppColors.warn,
                                letterSpacing: 0.6,
                              ),
                            ),
                            Text(
                              inv.internalNotes!,
                              style: const TextStyle(
                                color: AppColors.textMuted,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                  if (inv.statusHistory.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    SectionCard(
                      title: 'History',
                      child: Column(
                        children: [
                          for (final entry in inv.statusHistory)
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 6),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Container(
                                    width: 8,
                                    height: 8,
                                    margin: const EdgeInsets.only(
                                      top: 6,
                                      right: 12,
                                    ),
                                    decoration: BoxDecoration(
                                      color: statusColors(entry.toStatus).fg,
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          entry.fromStatus == null
                                              ? entry.toStatus.label
                                              : '${entry.fromStatus!.label} → ${entry.toStatus.label}',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        Text(
                                          [
                                            if (entry.changedBy != null)
                                              'by ${entry.changedBy}',
                                            if (entry.createdAt != null)
                                              dateTimeLabel(entry.createdAt!),
                                          ].join(' · '),
                                          style: const TextStyle(
                                            fontSize: 12,
                                            color: AppColors.textFaint,
                                          ),
                                        ),
                                        if ((entry.notes ?? '').isNotEmpty)
                                          Text(
                                            entry.notes!,
                                            style: const TextStyle(
                                              fontSize: 13,
                                              color: AppColors.textMuted,
                                            ),
                                          ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                        ],
                      ),
                    ),
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

class _Header extends StatelessWidget {
  const _Header({required this.invoice});

  final Invoice invoice;

  @override
  Widget build(BuildContext context) {
    final status = invoice.effectiveStatus;
    return SectionCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  invoice.clientName,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              StatusBadge(status),
            ],
          ),
          if ((invoice.title ?? '').isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              invoice.title!,
              style: const TextStyle(color: AppColors.textMuted),
            ),
          ],
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: _DateCell('Issued', shortDate(invoice.issueDate)),
              ),
              Expanded(
                child: _DateCell(
                  'Due',
                  shortDate(invoice.dueDate),
                  caption: status.isOpen ? dueLabel(invoice.dueDate) : null,
                  captionColor: status == InvoiceStatus.overdue
                      ? AppColors.danger
                      : AppColors.textMuted,
                ),
              ),
            ],
          ),
          if (invoice.isRecurring) ...[
            const SizedBox(height: 10),
            const Row(
              children: [
                Icon(
                  Icons.autorenew_rounded,
                  size: 16,
                  color: AppColors.accentInk,
                ),
                SizedBox(width: 6),
                Text(
                  'Generated from a billing plan',
                  style: TextStyle(fontSize: 12, color: AppColors.accentInk),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _DateCell extends StatelessWidget {
  const _DateCell(this.label, this.value, {this.caption, this.captionColor});

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

class _Totals extends StatelessWidget {
  const _Totals({required this.invoice});

  final Invoice invoice;

  @override
  Widget build(BuildContext context) {
    final open = invoice.effectiveStatus.isOpen;
    return SectionCard(
      child: Column(
        children: [
          KeyValueRow('Subtotal', money(invoice.subtotal)),
          if (invoice.taxRate > 0)
            KeyValueRow(
              'Tax (${quantityText(invoice.taxRate)}%)',
              money(invoice.taxAmount),
            ),
          const Divider(height: 16),
          KeyValueRow('Total', money(invoice.total), bold: true),
          if (invoice.amountPaid > 0)
            KeyValueRow(
              'Paid',
              '− ${money(invoice.amountPaid)}',
              valueColor: AppColors.good,
            ),
          if (open || invoice.amountPaid > 0)
            KeyValueRow(
              'Balance due',
              money(invoice.balanceDue),
              bold: true,
              valueColor: invoice.balanceDue > 0
                  ? (invoice.effectiveStatus == InvoiceStatus.overdue
                        ? AppColors.danger
                        : AppColors.warn)
                  : AppColors.good,
            ),
        ],
      ),
    );
  }
}

class _Actions extends StatelessWidget {
  const _Actions({
    required this.invoice,
    required this.busy,
    required this.onTransition,
    required this.onRecordPayment,
    required this.onSharePdf,
  });

  final Invoice invoice;
  final bool busy;
  final void Function(InvoiceStatus to) onTransition;
  final VoidCallback onRecordPayment;
  final VoidCallback onSharePdf;

  @override
  Widget build(BuildContext context) {
    final primary = <Widget>[];
    final secondary = <Widget>[];

    if (invoice.canRecordPayment && invoice.balanceDue > 0) {
      primary.add(
        FilledButton.icon(
          onPressed: busy ? null : onRecordPayment,
          icon: const Icon(Icons.payments_outlined),
          label: const Text('Record payment'),
        ),
      );
    }
    if (invoice.validTransitions.contains(InvoiceStatus.sent)) {
      primary.add(
        FilledButton.icon(
          onPressed: busy ? null : () => onTransition(InvoiceStatus.sent),
          icon: const Icon(Icons.send_rounded),
          label: const Text('Mark as sent'),
        ),
      );
    }
    primary.add(
      OutlinedButton.icon(
        onPressed: busy ? null : onSharePdf,
        icon: const Icon(Icons.ios_share_rounded),
        label: const Text('Share PDF'),
      ),
    );
    for (final to in invoice.validTransitions) {
      if (to == InvoiceStatus.sent) continue;
      secondary.add(
        TextButton(
          onPressed: busy ? null : () => onTransition(to),
          style: to == InvoiceStatus.cancelled
              ? TextButton.styleFrom(foregroundColor: AppColors.danger)
              : null,
          child: Text(to.actionLabel),
        ),
      );
    }

    if (primary.isEmpty && secondary.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        for (var i = 0; i < primary.length; i++) ...[
          if (i > 0) const SizedBox(height: 8),
          primary[i],
        ],
        if (secondary.isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Wrap(alignment: WrapAlignment.center, children: secondary),
          ),
      ],
    );
  }
}
