import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../core/api/api_client.dart';
import '../../core/api/api_exception.dart';
import '../../core/dates.dart';
import '../../core/json.dart';

/// A build published on the website's Mobile app page.
class AppRelease {
  const AppRelease({
    required this.version,
    required this.buildNumber,
    this.releasedAt,
    this.sizeLabel,
    this.notes,
    this.downloadUrl,
  });

  final String version;
  final int buildNumber;
  final DateTime? releasedAt;
  final String? sizeLabel;
  final String? notes;
  final String? downloadUrl;

  String get displayVersion => '$version (build $buildNumber)';

  bool isNewerThan(int installedBuild) => buildNumber > installedBuild;

  factory AppRelease.fromJson(Map<String, dynamic> json) => AppRelease(
    version: asString(json['version']) ?? '',
    buildNumber: asInt(json['build_number']),
    releasedAt: parseDate(asString(json['released_at'])),
    sizeLabel: asString(json['size_label']),
    notes: asString(json['notes']),
    downloadUrl: asString(json['download_url']),
  );
}

/// What is installed on this phone, from the build itself.
final installedVersionProvider = FutureProvider<PackageInfo>(
  (ref) => PackageInfo.fromPlatform(),
);

/// The newest published build, or null when none has been published yet.
final latestReleaseProvider = FutureProvider.autoDispose<AppRelease?>((
  ref,
) async {
  try {
    return AppRelease.fromJson(
      await ref.watch(apiClientProvider).getObject('/app-releases/latest'),
    );
  } on ApiException catch (e) {
    if (e.statusCode == 404) return null;
    rethrow;
  }
});
