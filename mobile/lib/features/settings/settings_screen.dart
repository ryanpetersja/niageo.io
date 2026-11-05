import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api/api_exception.dart';
import '../../core/dates.dart';
import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../../core/widgets/sheets.dart';
import '../auth/auth_controller.dart';
import 'app_update.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(authControllerProvider).value;
    final installed = ref.watch(installedVersionProvider).value;

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
          const _AppUpdateCard(),
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
                _Soon('Code reviews and the voice assistant'),
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
          Center(
            child: Text(
              installed == null
                  ? 'Niageo Ops'
                  : 'Niageo Ops · ${installed.version} (build ${installed.buildNumber})',
              style: const TextStyle(color: AppColors.textFaint, fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}

/// Installed version, and on Android whether a newer build is on the website.
class _AppUpdateCard extends ConsumerWidget {
  const _AppUpdateCard();

  bool get _isAndroid => !kIsWeb && Platform.isAndroid;

  Future<void> _download(BuildContext context, String url) async {
    final opened = await launchUrl(
      Uri.parse(url),
      mode: LaunchMode.externalApplication,
    );
    if (!opened && context.mounted) {
      showSnack(
        context,
        'Could not open the download. Open the Mobile app page on the website instead.',
      );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final installed = ref.watch(installedVersionProvider).value;
    final installedBuild = int.tryParse(installed?.buildNumber ?? '') ?? 0;
    final latest = ref.watch(latestReleaseProvider);

    return SectionCard(
      title: 'App',
      trailing: _isAndroid
          ? IconButton(
              tooltip: 'Check again',
              onPressed: () => ref.invalidate(latestReleaseProvider),
              icon: const Icon(Icons.refresh_rounded, size: 20),
            )
          : null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          KeyValueRow(
            'Installed',
            installed == null
                ? '—'
                : '${installed.version} (build ${installed.buildNumber})',
          ),
          if (_isAndroid)
            latest.when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  children: [
                    SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                    SizedBox(width: 10),
                    Text(
                      'Checking for updates…',
                      style: TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
              ),
              error: (e, _) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  'Could not check for updates: ${describeError(e)}',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 13,
                  ),
                ),
              ),
              data: (release) {
                if (release == null) {
                  return const KeyValueRow(
                    'Latest build',
                    'None published yet',
                  );
                }
                final newer = release.isNewerThan(installedBuild);
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    KeyValueRow(
                      'Latest build',
                      release.displayVersion,
                      valueColor: newer ? AppColors.warn : AppColors.good,
                    ),
                    if (release.releasedAt != null)
                      KeyValueRow(
                        'Released',
                        '${dateTimeLabel(release.releasedAt!)} · ${timeAgo(release.releasedAt!)}',
                      ),
                    if (newer && release.downloadUrl != null) ...[
                      const SizedBox(height: 10),
                      FilledButton.icon(
                        onPressed: () =>
                            _download(context, release.downloadUrl!),
                        icon: const Icon(Icons.system_update_rounded),
                        label: Text(
                          'Download ${release.version}${release.sizeLabel == null ? '' : ' · ${release.sizeLabel}'}',
                        ),
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'The download opens in your browser. Open the file once it finishes to install the update; you stay signed in.',
                        style: TextStyle(
                          fontSize: 12,
                          color: AppColors.textFaint,
                        ),
                      ),
                    ] else if (!newer)
                      const Padding(
                        padding: EdgeInsets.only(top: 6),
                        child: Text(
                          'You have the latest build.',
                          style: TextStyle(
                            color: AppColors.good,
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                  ],
                );
              },
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
