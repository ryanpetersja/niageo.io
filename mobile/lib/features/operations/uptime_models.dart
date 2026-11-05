import '../../core/dates.dart';
import '../../core/json.dart';

enum EndpointStatus {
  up,
  degraded,
  down,
  unknown;

  static EndpointStatus parse(Object? value) {
    final name = value?.toString();
    return values.firstWhere(
      (s) => s.name == name,
      orElse: () => EndpointStatus.unknown,
    );
  }

  String get label => switch (this) {
    EndpointStatus.up => 'Up',
    EndpointStatus.degraded => 'Degraded',
    EndpointStatus.down => 'Down',
    EndpointStatus.unknown => 'Not checked',
  };

  bool get isTrouble =>
      this == EndpointStatus.down || this == EndpointStatus.degraded;
}

class UptimeCheck {
  const UptimeCheck({
    required this.status,
    this.responseTimeMs,
    this.statusCode,
    this.errorMessage,
    this.checkedAt,
  });

  final EndpointStatus status;
  final int? responseTimeMs;
  final int? statusCode;
  final String? errorMessage;
  final DateTime? checkedAt;

  factory UptimeCheck.fromJson(Map<String, dynamic> json) => UptimeCheck(
    status: EndpointStatus.parse(json['status']),
    responseTimeMs: json['response_time_ms'] == null
        ? null
        : asInt(json['response_time_ms']),
    statusCode: json['status_code'] == null ? null : asInt(json['status_code']),
    errorMessage: asString(json['error_message']),
    checkedAt: parseDate(asString(json['checked_at'])),
  );
}

class EndpointStats {
  const EndpointStats({
    this.checks24h = 0,
    this.uptime24h,
    this.avgResponseMs24h,
  });

  final int checks24h;
  final double? uptime24h;
  final int? avgResponseMs24h;

  factory EndpointStats.fromJson(Map<String, dynamic> json) => EndpointStats(
    checks24h: asInt(json['checks_24h']),
    uptime24h: json['uptime_24h'] == null ? null : asDouble(json['uptime_24h']),
    avgResponseMs24h: json['avg_response_ms_24h'] == null
        ? null
        : asInt(json['avg_response_ms_24h']),
  );
}

class MonitoredEndpoint {
  const MonitoredEndpoint({
    required this.id,
    required this.name,
    required this.url,
    this.clientId,
    this.clientName,
    this.status = EndpointStatus.unknown,
    this.isActive = true,
    this.checkIntervalMinutes = 5,
    this.degradedThresholdMs = 2000,
    this.lastCheckedAt,
    this.lastResponseTimeMs,
    this.lastStatusCode,
    this.lastErrorMessage,
    this.stats,
    this.recentChecks = const [],
  });

  final int id;
  final String name;
  final String url;
  final int? clientId;
  final String? clientName;
  final EndpointStatus status;
  final bool isActive;
  final int checkIntervalMinutes;
  final int degradedThresholdMs;
  final DateTime? lastCheckedAt;
  final int? lastResponseTimeMs;
  final int? lastStatusCode;
  final String? lastErrorMessage;
  final EndpointStats? stats;
  final List<UptimeCheck> recentChecks;

  String get host => Uri.tryParse(url)?.host ?? url;

  factory MonitoredEndpoint.fromJson(Map<String, dynamic> json) {
    final client = asMap(json['client']);
    return MonitoredEndpoint(
      id: asInt(json['id']),
      name: asString(json['name']) ?? '',
      url: asString(json['url']) ?? '',
      clientId: client == null ? null : asInt(client['id']),
      clientName: client == null ? null : asString(client['company_name']),
      status: EndpointStatus.parse(json['status']),
      isActive: asBool(json['is_active'], true),
      checkIntervalMinutes: asInt(json['check_interval_minutes'], 5),
      degradedThresholdMs: asInt(json['degraded_threshold_ms'], 2000),
      lastCheckedAt: parseDate(asString(json['last_checked_at'])),
      lastResponseTimeMs: json['last_response_time_ms'] == null
          ? null
          : asInt(json['last_response_time_ms']),
      lastStatusCode: json['last_status_code'] == null
          ? null
          : asInt(json['last_status_code']),
      lastErrorMessage: asString(json['last_error_message']),
      stats: asMap(json['stats']) == null
          ? null
          : EndpointStats.fromJson(asMap(json['stats'])!),
      recentChecks: asList(json['recent_checks'])
          .map(UptimeCheck.fromJson)
          .toList(),
    );
  }
}

class UptimeSummary {
  const UptimeSummary({
    this.up = 0,
    this.degraded = 0,
    this.down = 0,
    this.total = 0,
  });

  final int up;
  final int degraded;
  final int down;
  final int total;

  bool get allUp => total > 0 && down == 0 && degraded == 0;

  factory UptimeSummary.fromJson(Map<String, dynamic> json) => UptimeSummary(
    up: asInt(json['up']),
    degraded: asInt(json['degraded']),
    down: asInt(json['down']),
    total: asInt(json['total']),
  );
}

class UptimeOverview {
  const UptimeOverview({required this.summary, required this.endpoints});

  final UptimeSummary summary;
  final List<MonitoredEndpoint> endpoints;

  factory UptimeOverview.fromJson(Map<String, dynamic> json) => UptimeOverview(
    summary: UptimeSummary.fromJson(asMap(json['summary']) ?? const {}),
    endpoints: asList(json['data']).map(MonitoredEndpoint.fromJson).toList(),
  );
}
