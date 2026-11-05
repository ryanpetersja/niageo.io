import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/dates.dart';
import '../../core/theme.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_view.dart';
import 'operations_repository.dart';
import 'uptime_models.dart';
import 'widgets/ops_badges.dart';

class UptimeScreen extends ConsumerWidget {
  const UptimeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final overview = ref.watch(uptimeProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Uptime')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(uptimeProvider.future),
        child: overview.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => ErrorView(
            error: e,
            onRetry: () => ref.invalidate(uptimeProvider),
          ),
          data: (data) {
            if (data.endpoints.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(
                    height: 420,
                    child: EmptyState(
                      icon: Icons.monitor_heart_outlined,
                      title: 'No sites monitored',
                      message: 'Add endpoints under Monitoring on the web app and they will show up here.',
                    ),
                  ),
                ],
              );
            }
            return ListView.separated(
              padding: const EdgeInsets.only(bottom: 24),
              itemCount: data.endpoints.length + 1,
              separatorBuilder: (_, index) => index == 0
                  ? const SizedBox.shrink()
                  : const Divider(indent: 16, endIndent: 16),
              itemBuilder: (context, index) {
                if (index == 0) return _SummaryBar(summary: data.summary);
                final endpoint = data.endpoints[index - 1];
                return EndpointTile(
                  endpoint: endpoint,
                  onTap: () => context.go('/ops/uptime/${endpoint.id}'),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

class _SummaryBar extends StatelessWidget {
  const _SummaryBar({required this.summary});

  final UptimeSummary summary;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 10),
      child: Row(
        children: [
          for (final (status, count) in [
            (EndpointStatus.up, summary.up),
            (EndpointStatus.degraded, summary.degraded),
            (EndpointStatus.down, summary.down),
          ])
            Expanded(
              child: Container(
                margin: const EdgeInsets.only(right: 8),
                padding: const EdgeInsets.symmetric(vertical: 10),
                decoration: BoxDecoration(
                  color: endpointColors(status).bg,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    Text(
                      '$count',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        color: endpointColors(status).fg,
                      ),
                    ),
                    Text(
                      status.label,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: endpointColors(status).fg,
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class EndpointTile extends StatelessWidget {
  const EndpointTile({super.key, required this.endpoint, this.onTap});

  final MonitoredEndpoint endpoint;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final checked = endpoint.lastCheckedAt;
    return ListTile(
      onTap: onTap,
      leading: Padding(
        padding: const EdgeInsets.only(top: 6),
        child: StatusDot(endpoint.status, size: 12),
      ),
      title: Text(
        endpoint.name,
        style: TextStyle(
          fontWeight: FontWeight.w700,
          color: endpoint.isActive ? AppColors.text : AppColors.textFaint,
        ),
      ),
      subtitle: Text(
        [
          if (endpoint.clientName != null) endpoint.clientName!,
          if (!endpoint.isActive) 'paused',
          checked == null ? 'never checked' : 'checked ${timeAgo(checked)}',
        ].join(' · '),
        style: const TextStyle(fontSize: 12),
      ),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          PillBadge(
            endpoint.status.label,
            colors: endpointColors(endpoint.status),
            compact: true,
          ),
          if (endpoint.lastResponseTimeMs != null) ...[
            const SizedBox(height: 4),
            Text(
              '${endpoint.lastResponseTimeMs} ms',
              style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
            ),
          ],
        ],
      ),
    );
  }
}
