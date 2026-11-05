import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/api/api_exception.dart';
import '../../core/json.dart';
import 'auth_models.dart';

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.watch(apiClientProvider)),
);

class AuthRepository {
  AuthRepository(this._api);

  final ApiClient _api;

  Future<Session> login({
    required String baseUrl,
    required String email,
    required String password,
    required String deviceName,
  }) async {
    _api.baseUrl = baseUrl;
    _api.token = null;
    final json = await _api.postObject(
      '/auth/login',
      body: {
        'email': email.trim(),
        'password': password,
        'device_name': deviceName,
      },
    );
    final token = asString(json['token']) ?? '';
    if (token.isEmpty) {
      throw ApiException('The server did not return a sign-in token.');
    }
    return Session(
      baseUrl: _api.baseUrl,
      token: token,
      user: AppUser.fromJson(asMap(json['user']) ?? const {}),
    );
  }

  /// Revokes the device's token server-side; failures are ignored because the
  /// local session is cleared regardless.
  Future<void> logout() async {
    try {
      await _api.postObject('/auth/logout');
    } on ApiException {
      // Already signed out or offline: nothing more to do.
    }
  }

  Future<AppUser> me() async =>
      AppUser.fromJson(await _api.getObject('/auth/me'));
}
