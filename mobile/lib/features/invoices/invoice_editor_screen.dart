import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/api_exception.dart';
import '../../core/dates.dart';
import '../../core/money.dart';
import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../../core/widgets/sheets.dart';
import '../clients/client_models.dart';
import '../clients/client_picker_sheet.dart';
import '../clients/clients_repository.dart';
import 'invoice_draft.dart';
import 'invoice_models.dart';
import 'invoices_repository.dart';
import 'widgets/line_item_sheet.dart';

/// Create a new invoice, or edit a draft. One screen, one [InvoiceDraft].
class InvoiceEditorScreen extends ConsumerStatefulWidget {
  const InvoiceEditorScreen({super.key, this.invoiceId, this.clientId});

  final int? invoiceId;
  final int? clientId;

  @override
  ConsumerState<InvoiceEditorScreen> createState() =>
      _InvoiceEditorScreenState();
}

class _InvoiceEditorScreenState extends ConsumerState<InvoiceEditorScreen> {
  InvoiceDraft? _draft;
  Object? _loadError;
  bool _saving = false;
  bool _dirty = false;
  late final TextEditingController _taxRate;

  bool get _isNew => widget.invoiceId == null;

  @override
  void initState() {
    super.initState();
    _taxRate = TextEditingController();
    _load();
  }

  @override
  void dispose() {
    _taxRate.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      InvoiceDraft draft;
      if (widget.invoiceId != null) {
        final invoice = await ref
            .read(invoicesRepositoryProvider)
            .fetch(widget.invoiceId!);
        if (!invoice.canEdit) {
          throw ApiException('Only draft invoices can be edited.');
        }
        draft = InvoiceDraft.fromInvoice(invoice);
      } else {
        draft = InvoiceDraft.blank();
        if (widget.clientId != null) {
          final client = await ref
              .read(clientsRepositoryProvider)
              .fetch(widget.clientId!);
          draft = draft.withClient(client.toInvoiceClient());
        }
      }
      _taxRate.text = draft.taxRate == 0 ? '' : quantityText(draft.taxRate);
      if (mounted) setState(() => _draft = draft);
    } catch (e) {
      if (mounted) setState(() => _loadError = e);
    }
  }

  void _update(InvoiceDraft Function(InvoiceDraft draft) change) {
    setState(() {
      _draft = change(_draft!);
      _dirty = true;
    });
  }

  Future<void> _pickClient() async {
    final client = await showAppSheet<Client>(
      context,
      builder: (_) => ClientPickerSheet(selectedId: _draft?.client?.id),
    );
    if (client != null) _update((d) => d.withClient(client.toInvoiceClient()));
  }

  Future<void> _pickDate({required bool issue}) async {
    final draft = _draft!;
    final picked = await showDatePicker(
      context: context,
      initialDate: issue ? draft.issueDate : draft.dueDate,
      firstDate: DateTime(2015),
      lastDate: DateTime(2100),
      helpText: issue ? 'Issue date' : 'Due date',
    );
    if (picked == null) return;
    final date = dateOnly(picked);
    _update((d) {
      if (!issue) return d.copyWith(dueDate: date);
      // Moving the issue date keeps the same number of days until due.
      final terms = d.dueDate.difference(d.issueDate).inDays;
      return d.copyWith(
        issueDate: date,
        dueDate: date.add(Duration(days: terms < 0 ? 0 : terms)),
      );
    });
  }

  Future<void> _addLine() async {
    final line = await showAppSheet<LineItemDraft>(
      context,
      builder: (_) => const LineItemSheet(),
    );
    if (line != null) _update((d) => d.addLine(line));
  }

  Future<void> _editLine(int index) async {
    final line = await showAppSheet<LineItemDraft>(
      context,
      builder: (_) => LineItemSheet(initial: _draft!.lineItems[index]),
    );
    if (line != null) _update((d) => d.updateLine(index, line));
  }

  Future<void> _addFromProducts() async {
    final line = await showAppSheet<LineItemDraft>(
      context,
      builder: (_) => const ProductPickerSheet(),
    );
    if (line != null) _update((d) => d.addLine(line));
  }

  Future<void> _applyPreset() async {
    final clientId = _draft?.client?.id;
    if (clientId == null) return;
    try {
      final client = await ref.read(clientsRepositoryProvider).fetch(clientId);
      if (!mounted) return;
      if (client.pricingPresets.isEmpty) {
        showSnack(
          context,
          '${client.companyName} has no pricing presets. Add them on the client page on the web.',
        );
        return;
      }
      final lines = await showAppSheet<List<LineItemDraft>>(
        context,
        builder: (_) => PresetPickerSheet(presets: client.pricingPresets),
      );
      if (lines == null) return;
      if (_draft!.lineItems.isNotEmpty && mounted) {
        final ok = await confirm(
          context,
          title: 'Replace the current line items?',
          confirmLabel: 'Replace',
        );
        if (!ok) return;
      }
      _update((d) => d.copyWith(lineItems: lines));
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    }
  }

  Future<void> _save() async {
    final draft = _draft!;
    final problems = draft.validate();
    if (problems.isNotEmpty) {
      showSnack(context, problems.first);
      return;
    }
    setState(() => _saving = true);
    try {
      final repo = ref.read(invoicesRepositoryProvider);
      final saved = _isNew
          ? await repo.create(draft)
          : await repo.update(draft);
      ref.read(refreshTickProvider.notifier).bump();
      if (!mounted) return;
      _dirty = false;
      if (_isNew) {
        context.go('/invoices/${saved.id}');
      } else {
        context.pop();
      }
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showErrorSnack(context, e);
      }
    }
  }

  Future<void> _close() async {
    if (_dirty) {
      final ok = await confirm(
        context,
        title: 'Discard changes?',
        confirmLabel: 'Discard',
        destructive: true,
      );
      if (!ok) return;
    }
    if (!mounted) return;
    if (context.canPop()) {
      context.pop();
    } else {
      context.go('/invoices');
    }
  }

  @override
  Widget build(BuildContext context) {
    final draft = _draft;
    return PopScope(
      canPop: !_dirty,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _close();
      },
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            onPressed: _close,
            icon: const Icon(Icons.close_rounded),
          ),
          title: Text(_isNew ? 'New invoice' : 'Edit invoice'),
        ),
        body: draft == null
            ? (_loadError != null
                  ? ErrorView(error: _loadError!, onRetry: () => context.pop())
                  : const Center(child: CircularProgressIndicator()))
            : ListView(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                children: [
                  _ClientField(client: draft.client, onTap: _pickClient),
                  const SizedBox(height: 12),
                  TextFormField(
                    initialValue: draft.title,
                    textCapitalization: TextCapitalization.sentences,
                    decoration: const InputDecoration(
                      labelText: 'Title (optional)',
                      hintText: 'e.g. October maintenance',
                    ),
                    onChanged: (v) => _update((d) => d.copyWith(title: v)),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _DateField(
                          label: 'Issue date',
                          date: draft.issueDate,
                          onTap: () => _pickDate(issue: true),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _DateField(
                          label: 'Due date',
                          date: draft.dueDate,
                          caption:
                              'Net ${draft.dueDate.difference(draft.issueDate).inDays}',
                          onTap: () => _pickDate(issue: false),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  SectionCard(
                    title: 'Line items',
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (draft.lineItems.isEmpty)
                          const Padding(
                            padding: EdgeInsets.only(bottom: 8),
                            child: Text(
                              'Nothing billed yet. Add a line, pick a product, or apply a preset.',
                              style: TextStyle(color: AppColors.textMuted),
                            ),
                          ),
                        for (var i = 0; i < draft.lineItems.length; i++)
                          Dismissible(
                            key: ValueKey(
                              'line-$i-${draft.lineItems[i].description}',
                            ),
                            direction: DismissDirection.endToStart,
                            background: Container(
                              alignment: Alignment.centerRight,
                              padding: const EdgeInsets.only(right: 16),
                              decoration: BoxDecoration(
                                color: AppColors.dangerSoft,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(
                                Icons.delete_outline_rounded,
                                color: AppColors.danger,
                              ),
                            ),
                            onDismissed: (_) => _update((d) => d.removeLine(i)),
                            child: _LineRow(
                              line: draft.lineItems[i],
                              onTap: () => _editLine(i),
                            ),
                          ),
                        const SizedBox(height: 4),
                        Wrap(
                          spacing: 8,
                          runSpacing: 4,
                          children: [
                            ActionChip(
                              avatar: const Icon(Icons.add_rounded, size: 18),
                              label: const Text('Add line'),
                              onPressed: _addLine,
                            ),
                            ActionChip(
                              avatar: const Icon(
                                Icons.inventory_2_outlined,
                                size: 18,
                              ),
                              label: const Text('From products'),
                              onPressed: _addFromProducts,
                            ),
                            if (draft.client != null)
                              ActionChip(
                                avatar: const Icon(
                                  Icons.bookmark_outline_rounded,
                                  size: 18,
                                ),
                                label: const Text('Apply preset'),
                                onPressed: _applyPreset,
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  SectionCard(
                    child: Column(
                      children: [
                        Row(
                          children: [
                            const Expanded(
                              child: Text(
                                'Tax rate',
                                style: TextStyle(color: AppColors.textMuted),
                              ),
                            ),
                            SizedBox(
                              width: 110,
                              child: TextField(
                                controller: _taxRate,
                                textAlign: TextAlign.right,
                                keyboardType:
                                    const TextInputType.numberWithOptions(
                                      decimal: true,
                                    ),
                                decoration: const InputDecoration(
                                  suffixText: '%',
                                  hintText: '0',
                                  isDense: true,
                                ),
                                onChanged: (v) => _update(
                                  (d) => d.copyWith(
                                    taxRate: (parseAmount(v) ?? 0)
                                        .clamp(0, 100)
                                        .toDouble(),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        KeyValueRow('Subtotal', money(draft.subtotal)),
                        if (draft.taxRate > 0)
                          KeyValueRow('Tax', money(draft.taxAmount)),
                        const Divider(height: 16),
                        KeyValueRow('Total', money(draft.total), bold: true),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    initialValue: draft.notes,
                    maxLines: 3,
                    minLines: 2,
                    textCapitalization: TextCapitalization.sentences,
                    decoration: const InputDecoration(
                      labelText: 'Notes (visible to client)',
                    ),
                    onChanged: (v) => _update((d) => d.copyWith(notes: v)),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    initialValue: draft.internalNotes,
                    maxLines: 3,
                    minLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Internal notes',
                    ),
                    onChanged: (v) =>
                        _update((d) => d.copyWith(internalNotes: v)),
                  ),
                ],
              ),
        bottomNavigationBar: draft == null
            ? null
            : SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                  child: FilledButton(
                    onPressed: _saving ? null : _save,
                    child: _saving
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            _isNew
                                ? 'Create invoice · ${money(draft.total)}'
                                : 'Save changes · ${money(draft.total)}',
                          ),
                  ),
                ),
              ),
      ),
    );
  }
}

class _ClientField extends StatelessWidget {
  const _ClientField({required this.client, required this.onTap});

  final InvoiceClient? client;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        onTap: onTap,
        leading: const Icon(Icons.business_rounded, color: AppColors.accentInk),
        title: Text(
          client?.companyName ?? 'Choose a client',
          style: TextStyle(
            fontWeight: FontWeight.w700,
            color: client == null ? AppColors.textMuted : AppColors.text,
          ),
        ),
        subtitle: client == null
            ? null
            : Text(
                client!.billingEmail ?? 'No billing email on file',
                style: const TextStyle(fontSize: 12),
              ),
        trailing: const Icon(Icons.chevron_right_rounded),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  const _DateField({
    required this.label,
    required this.date,
    required this.onTap,
    this.caption,
  });

  final String label;
  final DateTime date;
  final VoidCallback onTap;
  final String? caption;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          suffixIcon: const Icon(Icons.event_rounded, size: 20),
        ),
        child: Text(
          caption == null ? shortDate(date) : '${shortDate(date)}  ·  $caption',
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}

class _LineRow extends StatelessWidget {
  const _LineRow({required this.line, required this.onTap});

  final LineItemDraft line;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    line.description.isEmpty
                        ? '(no description)'
                        : line.description,
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  Text(
                    '${quantityText(line.quantity)} × ${money(line.unitPrice)}',
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
              money(line.total),
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                fontFeatures: [FontFeature.tabularFigures()],
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
    );
  }
}
