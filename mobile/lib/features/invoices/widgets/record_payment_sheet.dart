import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/dates.dart';
import '../../../core/money.dart';
import '../../../core/theme.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/sheets.dart';
import '../invoice_models.dart';
import '../invoices_repository.dart';

const paymentMethods = ['Bank transfer', 'Cash', 'Card', 'Cheque', 'Other'];

/// Records a payment against an invoice; pops with the updated invoice on success.
class RecordPaymentSheet extends ConsumerStatefulWidget {
  const RecordPaymentSheet({super.key, required this.invoice});

  final Invoice invoice;

  @override
  ConsumerState<RecordPaymentSheet> createState() => _RecordPaymentSheetState();
}

class _RecordPaymentSheetState extends ConsumerState<RecordPaymentSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _amount;
  final _reference = TextEditingController();
  final _notes = TextEditingController();
  DateTime _date = dateOnly(DateTime.now());
  String _method = paymentMethods.first;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _amount = TextEditingController(
      text: widget.invoice.balanceDue > 0
          ? widget.invoice.balanceDue.toStringAsFixed(2)
          : '',
    );
  }

  @override
  void dispose() {
    _amount.dispose();
    _reference.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2015),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _date = dateOnly(picked));
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final updated = await ref
          .read(invoicesRepositoryProvider)
          .recordPayment(
            widget.invoice.id,
            amount: parseAmount(_amount.text)!,
            date: _date,
            method: _method,
            reference: _reference.text,
            notes: _notes.text,
          );
      if (mounted) Navigator.of(context).pop(updated);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showErrorSnack(context, e);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final balance = widget.invoice.balanceDue;
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          SheetTitle(
            'Record payment',
            subtitle:
                '${widget.invoice.invoiceNumber} · balance due ${money(balance)}',
          ),
          TextFormField(
            controller: _amount,
            autofocus: true,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(
              labelText: 'Amount',
              prefixText: r'$ ',
            ),
            validator: (v) {
              final amount = parseAmount(v ?? '');
              if (amount == null || amount <= 0) {
                return 'Enter the amount received.';
              }
              if (balance > 0 && amount > balance + 0.005) {
                return 'That is more than the ${money(balance)} outstanding.';
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _pickDate,
                  icon: const Icon(Icons.event_rounded, size: 18),
                  label: Text(shortDate(_date)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _method,
                  decoration: const InputDecoration(labelText: 'Method'),
                  items: [
                    for (final m in paymentMethods)
                      DropdownMenuItem(value: m, child: Text(m)),
                  ],
                  onChanged: (v) => setState(() => _method = v ?? _method),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _reference,
            decoration: const InputDecoration(
              labelText: 'Reference (optional)',
              hintText: 'Transfer or cheque number',
            ),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _notes,
            decoration: const InputDecoration(labelText: 'Notes (optional)'),
            maxLines: 2,
          ),
          const SizedBox(height: 20),
          FilledButton(
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
                : const Text('Record payment'),
          ),
          const SizedBox(height: 6),
          const Text(
            'The invoice is marked paid automatically once the full amount is in.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: AppColors.textFaint),
          ),
        ],
      ),
    );
  }
}
