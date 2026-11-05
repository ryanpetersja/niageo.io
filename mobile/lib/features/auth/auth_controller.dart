import 'dart:convert';
import 'dart:io' show Platform;

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/config.dart';
import '../../core/storage/secure_store.dart';
import 'auth_models.dart';
import 'auth_repository.dart';

final sessionStoreProvider = Provider<SessionStore>(
  (ref) => SecureSessionStore(),
);

/// The signed-in session, restored from secure storage on launch.
/// Loading while restoring; `null` when signed out.
final authControllerProvider = AsyncNotifierProvider<AuthController, Session?>(
  AuthController.new,
);

final isSignedInProvider = Provider<bool>(
  (ref) => ref.watch(authControllerProvider).value != null,
);

/// A one-line notice for the sign-in screen (e.g. after the server revoked the token).
final authNoticeProvider = NotifierProvider<AuthNotice, String?>(
  AuthNotice.new,
);

class AuthNotice extends Notifier<String?> {
  @override
  String? build() => null;

  void set(String? message) => state = message;
}

class AuthController extends AsyncNotifier<Session?> {
  @override
  Future<Session?> build() async {
    final api = ref.watch(apiClientProvider);
    final store = ref.watch(sessionStoreProvider);
    api.onUnauthorized = () => Future<void>.microtask(() => _expire());

    final stored = await store.read();
    final lastBaseUrl = stored?.baseUrl ?? await store.readBaseUrl();
    api.baseUrl = lastBaseUrl ?? AppConfig.defaultBaseUrl;
    if (stored == null) return null;

    try {
      final user = AppUser.fromJson(
        jsonDecode(stored.userJson) as Map<String, dynamic>,
      );
      api.token = stored.token;
      return Session(baseUrl: api.baseUrl, token: stored.token, user: user);
    } catch (_) {
      await store.clear();
      return null;
    }
  }

  /// The server address to prefill on the sign-in screen.
  String get baseUrl => ref.read(apiClientProvider).baseUrl;

  /// Signs in; throws an [ApiException] for the screen to show. The session state
  /// only changes on success, so a failed attempt never bounces the router.
  Future<void> signIn({
    required String baseUrl,
    required String email,
    required String password,
  }) async {
    final session = await ref
        .read(authRepositoryProvider)
        .login(
          baseUrl: baseUrl,
          email: email,
          password: password,
          deviceName: _deviceName(),
        );
    ref.read(apiClientProvider).token = session.token;
    await ref
        .read(sessionStoreProvider)
        .write(
          StoredSession(
            baseUrl: session.baseUrl,
            token: session.token,
            userJson: jsonEncode(session.user.toJson()),
          ),
        );
    ref.read(authNoticeProvider.notifier).set(null);
    state = AsyncData(session);
  }

  Future<void> signOut() async {
    await ref.read(authRepositoryProvider).logout();
    await _clearLocal();
  }

  Future<void> _expire() async {
    if (state.value == null) return;
    await _clearLocal();
    ref
        .read(authNoticeProvider.notifier)
        .set('Your session has expired. Please sign in again.');
  }

  Future<void> _clearLocal() async {
    ref.read(apiClientProvider).token = null;
    await ref.read(sessionStoreProvider).clear();
    state = const AsyncData(null);
  }

  String _deviceName() {
    try {
      return switch (Platform.operatingSystem) {
        'ios' => 'iPhone',
        'android' => 'Android phone',
        final other => other,
      };
    } catch (_) {
      return 'Mobile';
    }
  }
}
