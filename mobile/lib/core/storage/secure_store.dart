import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Where the session lives between launches: the API token in the platform keystore,
/// plus the server address and a cached copy of the signed-in user.
abstract class SessionStore {
  Future<StoredSession?> read();

  /// The last server address used, kept after sign-out so the next sign-in starts from it.
  Future<String?> readBaseUrl();

  Future<void> write(StoredSession session);
  Future<void> clear();
}

class StoredSession {
  const StoredSession({
    required this.baseUrl,
    required this.token,
    required this.userJson,
  });

  final String baseUrl;
  final String token;
  final String userJson;
}

class SecureSessionStore implements SessionStore {
  SecureSessionStore([FlutterSecureStorage? storage])
    : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'auth_token';
  static const _baseUrlKey = 'base_url';
  static const _userKey = 'auth_user';

  @override
  Future<StoredSession?> read() async {
    final token = await _storage.read(key: _tokenKey);
    final baseUrl = await _storage.read(key: _baseUrlKey);
    final user = await _storage.read(key: _userKey);
    if (token == null || token.isEmpty || baseUrl == null || user == null) {
      return null;
    }
    return StoredSession(baseUrl: baseUrl, token: token, userJson: user);
  }

  @override
  Future<String?> readBaseUrl() => _storage.read(key: _baseUrlKey);

  @override
  Future<void> write(StoredSession session) async {
    await _storage.write(key: _tokenKey, value: session.token);
    await _storage.write(key: _baseUrlKey, value: session.baseUrl);
    await _storage.write(key: _userKey, value: session.userJson);
  }

  @override
  Future<void> clear() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _userKey);
    // The server address is kept so the next sign-in starts from it.
  }
}

/// In-memory store for tests.
class MemorySessionStore implements SessionStore {
  StoredSession? session;
  String? baseUrl;

  @override
  Future<StoredSession?> read() async => session;

  @override
  Future<String?> readBaseUrl() async => session?.baseUrl ?? baseUrl;

  @override
  Future<void> write(StoredSession session) async {
    this.session = session;
    baseUrl = session.baseUrl;
  }

  @override
  Future<void> clear() async => session = null;
}
