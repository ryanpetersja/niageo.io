import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/money.dart';
import '../../core/theme.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/sheets.dart';
import 'client_form_sheet.dart';
import 'client_models.dart';
import 'clients_repository.dart';

class ClientsScreen extends ConsumerStatefulWidget {
  const ClientsScreen({super.key});

  @override
  ConsumerState<ClientsScreen> createState() => _ClientsScreenState();
}

class _ClientsScreenState extends ConsumerState<ClientsScreen> {
  String _query = '';

  Future<void> _create() async {
    final created = await showAppSheet<Client>(
      context,
      builder: (_) => const ClientFormSheet(),
    );
    if (created != null && mounted) context.go('/clients/${created.id}');
  }

  @override
  Widget build(BuildContext context) {
    final clients = ref.watch(clientsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Clients'),
        actions: [
          IconButton(
            tooltip: 'New client',
            onPressed: _create,
            icon: const Icon(Icons.add_rounded),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: TextField(
              onChanged: (v) => setState(() => _query = v.trim().toLowerCase()),
              decoration: const InputDecoration(
                hintText: 'Search clients',
                prefixIcon: Icon(Icons.search_rounded),
                isDense: true,
              ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(clientsProvider.future),
              child: clients.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(clientsProvider),
                ),
                data: (all) {
                  final items = all
                      .where(
                        (c) =>
                            _query.isEmpty ||
                            c.companyName.toLowerCase().contains(_query),
                      )
                      .toList();
                  if (items.isEmpty) {
                    return ListView(
                      children: [
                        SizedBox(
                          height: 420,
                          child: EmptyState(
                            icon: Icons.business_outlined,
                            title: _query.isEmpty
                                ? 'No clients yet'
                                : 'No client matches',
                            message: _query.isEmpty
                                ? 'Add the companies you bill.'
                                : 'Try a different name.',
                            actionLabel: _query.isEmpty ? 'New client' : null,
                            onAction: _query.isEmpty ? _create : null,
                          ),
                        ),
                      ],
                    );
                  }
                  return ListView.separated(
                    padding: const EdgeInsets.only(bottom: 24),
                    itemCount: items.length,
                    separatorBuilder: (_, _) =>
                        const Divider(indent: 16, endIndent: 16),
                    itemBuilder: (context, index) {
                      final client = items[index];
                      return ListTile(
                        onTap: () => context.go('/clients/${client.id}'),
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
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          client.billingTermsLabel,
                          style: const TextStyle(fontSize: 12),
                        ),
                        trailing: client.outstandingBalance > 0
                            ? Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    money(client.outstandingBalance),
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w800,
                                      color: AppColors.warn,
                                    ),
                                  ),
                                  const Text(
                                    'outstanding',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: AppColors.textFaint,
                                    ),
                                  ),
                                ],
                              )
                            : const Icon(
                                Icons.chevron_right_rounded,
                                color: AppColors.textFaint,
                              ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
