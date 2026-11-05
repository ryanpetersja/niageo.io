import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/dates.dart';
import '../../core/refresh.dart';
import '../../core/theme.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/section_card.dart';
import '../../core/widgets/stat_tile.dart';
import 'operations_repository.dart';
import 'uptime_models.dart';
import 'widgets/ops_badges.dart';

class EndpointDetailScreen extends ConsumerStatefulWidget {
  const EndpointDetailScreen({super.key, required this.endpointId});

  final int endpointId;

  @override
  ConsumerState<EndpointDetailScreen> createState() =>
      _EndpointDetailScreenState();
}

class _EndpointDetailScreenState extends ConsumerState<EndpointDetailScreen> {
  bool _checking = false;

  Future<void> _checkNow() async {
    setState(() => _checking = true);
    try {
      final result = await ref
          .read(operationsRepositoryProvider)
          .checkEndpoint(widget.endpointId);
      ref.read(refreshTickProvider.notifier).bump();
      if (mounted) {
        final ms = result.lastResponseTimeMs;
        showSnack(context, switch (result.status) {
          EndpointStatus.up => 'Up${ms == null ? '' : ' in $ms ms'}.',
          EndpointStatus.degraded =>
            'Responding, but slowly${ms == null ? '' : ' ($ms ms)'}.',
          EndpointStatus.down =>
            'Down${result.lastErrorMessage == null ? '' : ': ${result.lastErrorMessage}'}',
          EndpointStatus.unknown => 'Checked.',
        });
      }
    } catch (e) {
      if (mounted) showErrorSnack(context, e);
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final endpoint = ref.watch(endpointDetailProvider(widget.endpointId));

    return Scaffold(
      appBar: AppBar(title: Text(endpoint.value?.name ?? 'Site')),
      body: endpoint.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorView(
          error: e,
          onRetry: () =>
              ref.invalidate(endpointDetailProvider(widget.endpointId)),
        ),
        data: (e) => RefreshIndicator(
          onRefresh: () =>
              ref.refresh(endpointDetailProvider(widget.endpointId).future),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
            children: [
              SectionCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        StatusDot(e.status, size: 14),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            e.name,
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                        PillBadge(
                          e.status.label,
                          colors: endpointColors(e.status),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      e.url,
                      style: const TextStyle(
                        color: AppColors.accentInk,
                        fontSize: 13,
                      ),
                    ),
                    if (e.clientName != null)
                      Text(
                        e.clientName!,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 13,
                        ),
                      ),
                    if (e.lastErrorMessage != null &&
                        e.lastErrorMessage!.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: AppColors.dangerSoft,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          e.lastErrorMessage!,
                          style: const TextStyle(
                            color: AppColors.danger,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 14),
                    FilledButton.icon(
                      onPressed: _checking ? null : _checkNow,
                      icon: _checking
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.network_ping_rounded),
                      label: Text(_checking ? 'Checking…' : 'Check now'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: StatTile(
                      label: 'Uptime 24h',
                      value: e.stats?.uptime24h == null
                          ? '—'
                          : '${e.stats!.uptime24h!.toStringAsFixed(e.stats!.uptime24h! == 100 ? 0 : 1)}%',
                      caption: '${e.stats?.checks24h ?? 0} checks',
                      color: (e.stats?.uptime24h ?? 100) >= 99
                          ? AppColors.good
                          : AppColors.warn,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: StatTile(
                      label: 'Response',
                      value: e.lastResponseTimeMs == null
                          ? '—'
                          : '${e.lastResponseTimeMs} ms',
                      caption: e.stats?.avgResponseMs24h == null
                          ? 'last check'
                          : 'avg ${e.stats!.avgResponseMs24h} ms today',
                      color:
                          e.lastResponseTimeMs != null &&
                              e.lastResponseTimeMs! > e.degradedThresholdMs
                          ? AppColors.warn
                          : AppColors.text,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              SectionCard(
                child: Column(
                  children: [
                    KeyValueRow(
                      'Last checked',
                      e.lastCheckedAt == null
                          ? 'Never'
                          : '${timeAgo(e.lastCheckedAt!)} · ${dateTimeLabel(e.lastCheckedAt!)}',
                    ),
                    KeyValueRow(
                      'Checks every',
                      '${e.checkIntervalMinutes} min',
                    ),
                    KeyValueRow(
                      'Degraded above',
                      '${e.degradedThresholdMs} ms',
                    ),
                    if (e.lastStatusCode != null)
                      KeyValueRow('Last HTTP status', '${e.lastStatusCode}'),
                    if (!e.isActive)
                      const KeyValueRow(
                        'Monitoring',
                        'Paused',
                        valueColor: AppColors.warn,
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              SectionCard(
                title: 'Recent checks',
                child: e.recentChecks.isEmpty
                    ? const Text(
                        'No checks recorded yet.',
                        style: TextStyle(color: AppColors.textMuted),
                      )
                    : Column(
                        children: [
                          for (final check in e.recentChecks.take(30))
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 5),
                              child: Row(
                                children: [
                                  StatusDot(check.status, size: 8),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      check.checkedAt == null
                                          ? '—'
                                          : dateTimeLabel(check.checkedAt!),
                                      style: const TextStyle(fontSize: 13),
                                    ),
                                  ),
                                  if (check.errorMessage != null &&
                                      check.errorMessage!.isNotEmpty)
                                    Flexible(
                                      child: Text(
                                        check.errorMessage!,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: AppColors.danger,
                                        ),
                                      ),
                                    )
                                  else
                                    Text(
                                      '${check.responseTimeMs ?? '—'} ms${check.statusCode == null ? '' : ' · ${check.statusCode}'}',
                                      style: const TextStyle(
                                        fontSize: 12,
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
      ),
    );
  }
}
