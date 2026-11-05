import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/section_card.dart';
import '../../core/widgets/sheets.dart';
import '../auth/auth_controller.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(authControllerProvider).value;

    return Scaffold(
      appBar: AppBar(title: const Text('Settings')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
        children: [
          SectionCard(
            title: 'Account',
            child: Column(
              children: [
                KeyValueRow('Name', session?.user.name ?? '—'),
                KeyValueRow('Email', session?.user.email ?? '—'),
                KeyValueRow(
                  'Role',
                  session?.user.isAdmin == true ? 'Admin' : 'Staff',
                ),
                KeyValueRow(
                  'Server',
                  session?.baseUrl.replaceFirst(RegExp(r'^https?://'), '') ??
                      '—',
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Data',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Lists refresh on their own after every change. Pull down on any screen to refresh by hand.',
                  style: TextStyle(color: AppColors.textMuted, fontSize: 13),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: () =>
                      ref.read(refreshTickProvider.notifier).bump(),
                  icon: const Icon(Icons.refresh_rounded),
                  label: const Text('Refresh everything'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Coming in later phases',
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Soon('Reports and AI summaries'),
                _Soon('Scopes'),
                _Soon('Uptime monitoring and subscription alerts'),
                _Soon('Billing plans and the voice assistant'),
              ],
            ),
          ),
          const SizedBox(height: 24),
          FilledButton.tonalIcon(
            onPressed: () async {
              final ok = await confirm(
                context,
                title: 'Sign out of this phone?',
                confirmLabel: 'Sign out',
              );
              if (ok) await ref.read(authControllerProvider.notifier).signOut();
            },
            icon: const Icon(Icons.logout_rounded),
            label: const Text('Sign out'),
          ),
          const SizedBox(height: 12),
          const Center(
            child: Text(
              'Niageo Ops · 0.1.0',
              style: TextStyle(color: AppColors.textFaint, fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}

class _Soon extends StatelessWidget {
  const _Soon(this.label);

  final String label;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          const Icon(
            Icons.schedule_rounded,
            size: 16,
            color: AppColors.textFaint,
          ),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(color: AppColors.textMuted, fontSize: 13),
          ),
        ],
      ),
    );
  }
}
