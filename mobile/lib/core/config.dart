/// App-wide constants. The server address is editable on the sign-in screen and
/// remembered on the device; this is only the default.
class AppConfig {
  static const String defaultBaseUrl = 'https://niageo-io.on-forge.com';
  static const String apiPrefix = '/api/v1';
  static const int pageSize = 25;
  static const Duration requestTimeout = Duration(seconds: 30);
}
