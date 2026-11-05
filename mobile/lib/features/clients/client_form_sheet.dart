import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/refresh.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/sheets.dart';
import 'client_models.dart';
import 'clients_repository.dart';

/// Quick-create a client from the phone; pops with the new [Client].
class ClientFormSheet extends ConsumerStatefulWidget {
  const ClientFormSheet({super.key, this.initialName});

  final String? initialName;

  @override
  ConsumerState<ClientFormSheet> createState() => _ClientFormSheetState();
}

class _ClientFormSheetState extends ConsumerState<ClientFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _company;
  final _billingEmail = TextEditingController();
  final _contactName = TextEditingController();
  final _contactEmail = TextEditingController();
  final _contactPhone = TextEditingController();
  String _terms = 'net_30';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _company = TextEditingController(text: widget.initialName ?? '');
  }

  @override
  void dispose() {
    _company.dispose();
    _billingEmail.dispose();
    _contactName.dispose();
    _contactEmail.dispose();
    _contactPhone.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final client = await ref
          .read(clientsRepositoryProvider)
          .create(
            companyName: _company.text,
            billingTerms: _terms,
            billingEmail: _billingEmail.text,
            contactName: _contactName.text,
            contactEmail: _contactEmail.text,
            contactPhone: _contactPhone.text,
          );
      ref.read(refreshTickProvider.notifier).bump();
      if (mounted) Navigator.of(context).pop(client);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        showErrorSnack(context, e);
      }
    }
  }

  String? _optionalEmail(String? value) =>
      (value == null || value.trim().isEmpty || value.contains('@'))
      ? null
      : 'Enter a valid email address.';

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          const SheetTitle(
            'New client',
            subtitle:
                'Just the essentials; the rest can be filled in on the web.',
          ),
          TextFormField(
            controller: _company,
            autofocus: true,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(labelText: 'Company name'),
            validator: (v) => (v == null || v.trim().isEmpty)
                ? 'Enter the company name.'
                : null,
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: _terms,
            decoration: const InputDecoration(labelText: 'Payment terms'),
            items: [
              for (final e in billingTermsOptions.entries)
                DropdownMenuItem(value: e.key, child: Text(e.value)),
            ],
            onChanged: (v) => setState(() => _terms = v ?? _terms),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _billingEmail,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(
              labelText: 'Billing email (optional)',
            ),
            validator: _optionalEmail,
          ),
          const SizedBox(height: 20),
          const Text(
            'Primary contact (optional)',
            style: TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: _contactName,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(labelText: 'Name'),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: _contactEmail,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'Email'),
                  validator: _optionalEmail,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: _contactPhone,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(labelText: 'Phone'),
                ),
              ),
            ],
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
                : const Text('Create client'),
          ),
        ],
      ),
    );
  }
}
