import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/sheets.dart';
import 'client_form_sheet.dart';
import 'client_models.dart';
import 'clients_repository.dart';

/// Pick a client for an invoice; pops with the chosen [Client]. Offers to create one when nothing matches.
class ClientPickerSheet extends ConsumerStatefulWidget {
  const ClientPickerSheet({super.key, this.selectedId});

  final int? selectedId;

  @override
  ConsumerState<ClientPickerSheet> createState() => _ClientPickerSheetState();
}

class _ClientPickerSheetState extends ConsumerState<ClientPickerSheet> {
  String _query = '';

  Future<void> _createNew() async {
    final created = await showAppSheet<Client>(
      context,
      builder: (_) => ClientFormSheet(initialName: _query),
    );
    if (created != null && mounted) Navigator.of(context).pop(created);
  }

  @override
  Widget build(BuildContext context) {
    final clients = ref.watch(clientsProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisSize: MainAxisSize.min,
      children: [
        const SheetTitle('Choose a client'),
        TextField(
          autofocus: true,
          onChanged: (v) => setState(() => _query = v.trim().toLowerCase()),
          decoration: const InputDecoration(
            hintText: 'Search clients',
            prefixIcon: Icon(Icons.search_rounded),
            isDense: true,
          ),
        ),
        const SizedBox(height: 8),
        SizedBox(
          height: 380,
          child: clients.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (e, _) => ErrorView(
              error: e,
              onRetry: () => ref.invalidate(clientsProvider),
            ),
            data: (all) {
              final matches = all
                  .where(
                    (c) =>
                        _query.isEmpty ||
                        c.companyName.toLowerCase().contains(_query),
                  )
                  .toList();
              return ListView(
                children: [
                  for (final client in matches)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: CircleAvatar(
                        backgroundColor: AppColors.surface3,
                        child: Text(
                          client.companyName.isEmpty
                              ? '?'
                              : client.companyName[0].toUpperCase(),
                          style: const TextStyle(
                            color: AppColors.accentInk,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      title: Text(
                        client.companyName,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text(
                        client.outstandingBalance > 0
                            ? '${client.billingTermsLabel} · ${money(client.outstandingBalance)} outstanding'
                            : client.billingTermsLabel,
                        style: const TextStyle(fontSize: 12),
                      ),
                      trailing: client.id == widget.selectedId
                          ? const Icon(
                              Icons.check_rounded,
                              color: AppColors.accentInk,
                            )
                          : null,
                      onTap: () => Navigator.of(context).pop(client),
                    ),
                  if (matches.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 24),
                      child: Text(
                        'No client matches.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.textMuted),
                      ),
                    ),
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    onPressed: _createNew,
                    icon: const Icon(Icons.add_business_outlined),
                    label: Text(
                      _query.isEmpty ? 'New client' : 'Create "$_query"',
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }
}
