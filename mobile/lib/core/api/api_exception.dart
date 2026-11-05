import 'package:dio/dio.dart';

/// One error type for the UI: a readable message plus, for validation failures,
/// the per-field messages Laravel sends back.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors = const {}});

  final String message;
  final int? statusCode;
  final Map<String, List<String>> errors;

  bool get isUnauthorized => statusCode == 401;
  bool get isValidation => statusCode == 422;

  String? fieldError(String field) => errors[field]?.first;

  factory ApiException.fromDio(DioException e) {
    final response = e.response;
    if (response != null) {
      final errors = <String, List<String>>{};
      var message = 'Request failed (${response.statusCode}).';
      final data = response.data;
      if (data is Map) {
        final rawMessage = data['message'];
        if (rawMessage is String && rawMessage.isNotEmpty) message = rawMessage;
        final rawErrors = data['errors'];
        if (rawErrors is Map) {
          rawErrors.forEach((key, value) {
            if (value is List) {
              errors['$key'] = value.map((v) => '$v').toList();
            } else if (value != null) {
              errors['$key'] = ['$value'];
            }
          });
        }
        // Laravel's summary line ("… (and 2 more errors)") is less useful than the first field message.
        if (response.statusCode == 422 && errors.isNotEmpty) {
          message = errors.values.first.first;
        }
      }
      if (response.statusCode == 401) {
        message = 'Your session has expired. Please sign in again.';
      } else if (response.statusCode == 429) {
        message = 'Too many attempts. Wait a minute and try again.';
      } else if ((response.statusCode ?? 0) >= 500) {
        message = 'The server hit an error. Please try again shortly.';
      }
      return ApiException(
        message,
        statusCode: response.statusCode,
        errors: errors,
      );
    }

    return switch (e.type) {
      DioExceptionType.connectionTimeout ||
      DioExceptionType.sendTimeout ||
      DioExceptionType.receiveTimeout => ApiException(
        'The server took too long to respond. Check your connection and try again.',
      ),
      DioExceptionType.connectionError => ApiException(
        'Could not reach the server. Check the server address and your connection.',
      ),
      DioExceptionType.badCertificate => ApiException(
        "The server's security certificate is not trusted.",
      ),
      _ => ApiException(e.message ?? 'Something went wrong. Please try again.'),
    };
  }

  @override
  String toString() => message;
}

/// Message for any error that reaches the UI.
String describeError(Object error) => error is ApiException
    ? error.message
    : 'Something went wrong. Please try again.';
