import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config.dart';
import 'api_exception.dart';

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());

/// Thin wrapper over Dio: base URL + bearer token, JSON in/out, every failure
/// surfaced as an [ApiException] the UI can show.
class ApiClient {
  ApiClient({Dio? dio}) : dio = dio ?? Dio() {
    this.dio.options = BaseOptions(
      baseUrl: '${AppConfig.defaultBaseUrl}${AppConfig.apiPrefix}',
      connectTimeout: AppConfig.requestTimeout,
      receiveTimeout: AppConfig.requestTimeout,
      headers: const {'Accept': 'application/json'},
      responseType: ResponseType.json,
    );
    this.dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          final current = token;
          if (current != null && current.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $current';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          if (error.response?.statusCode == 401 && token != null) {
            onUnauthorized?.call();
          }
          handler.next(error);
        },
      ),
    );
  }

  final Dio dio;

  /// Sanctum token for the signed-in session; null when signed out.
  String? token;

  /// Called when the server rejects the token (revoked, expired): the auth layer signs out.
  void Function()? onUnauthorized;

  String _baseUrl = AppConfig.defaultBaseUrl;

  String get baseUrl => _baseUrl;

  set baseUrl(String url) {
    _baseUrl = normalizeBaseUrl(url);
    dio.options.baseUrl = '$_baseUrl${AppConfig.apiPrefix}';
  }

  /// "niageo.example.com/" → "https://niageo.example.com"; a pasted API URL loses its /api/v1.
  static String normalizeBaseUrl(String input) {
    var url = input.trim();
    if (url.isEmpty) return AppConfig.defaultBaseUrl;
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://$url';
    }
    while (url.endsWith('/')) {
      url = url.substring(0, url.length - 1);
    }
    if (url.endsWith(AppConfig.apiPrefix)) {
      url = url.substring(0, url.length - AppConfig.apiPrefix.length);
    }
    return url;
  }

  Future<Map<String, dynamic>> getObject(
    String path, {
    Map<String, dynamic>? query,
  }) async => _asObject(
    await _send(() => dio.get<dynamic>(path, queryParameters: query)),
  );

  Future<Map<String, dynamic>> postObject(String path, {Object? body}) async =>
      _asObject(await _send(() => dio.post<dynamic>(path, data: body)));

  Future<Map<String, dynamic>> putObject(String path, {Object? body}) async =>
      _asObject(await _send(() => dio.put<dynamic>(path, data: body)));

  Future<void> delete(String path) async =>
      _send(() => dio.delete<dynamic>(path));

  /// DELETE that answers with an object (e.g. the invoice after removing a payment).
  Future<Map<String, dynamic>> deleteObject(String path) async =>
      _asObject(await _send(() => dio.delete<dynamic>(path)));

  /// Raw bytes, e.g. an invoice PDF.
  Future<List<int>> getBytes(String path) async {
    final response = await _send(
      () => dio.get<List<int>>(
        path,
        options: Options(
          responseType: ResponseType.bytes,
          headers: const {'Accept': 'application/pdf'},
        ),
      ),
    );
    return response.data ?? const <int>[];
  }

  Future<Response<T>> _send<T>(Future<Response<T>> Function() call) async {
    try {
      return await call();
    } on DioException catch (e) {
      throw ApiException.fromDio(e);
    }
  }

  Map<String, dynamic> _asObject(Response<dynamic> response) {
    final data = response.data;
    return data is Map ? Map<String, dynamic>.from(data) : <String, dynamic>{};
  }
}
