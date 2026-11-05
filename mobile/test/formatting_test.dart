import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:niageo_ops/core/api/api_client.dart';
import 'package:niageo_ops/core/api/api_exception.dart';
import 'package:niageo_ops/core/dates.dart';
import 'package:niageo_ops/core/money.dart';

void main() {
  group('money', () {
    test('formats dollars', () {
      expect(money(141600), r'$141,600.00');
      expect(money(0.5), r'$0.50');
      expect(compactMoney(141600), r'$141.6K');
      expect(compactMoney(2500), r'$2,500.00');
    });

    test('parses typed amounts', () {
      expect(parseAmount(r'$1,250.50'), 1250.5);
      expect(parseAmount(' 80 '), 80);
      expect(parseAmount(''), isNull);
      expect(parseAmount('abc'), isNull);
    });

    test('quantities drop a pointless .0', () {
      expect(quantityText(2), '2');
      expect(quantityText(1.5), '1.5');
    });
  });

  group('dates', () {
    final today = DateTime(2026, 11, 5);

    test('due labels', () {
      expect(dueLabel(DateTime(2026, 11, 5), today: today), 'Due today');
      expect(dueLabel(DateTime(2026, 11, 6), today: today), 'Due tomorrow');
      expect(dueLabel(DateTime(2026, 11, 17), today: today), 'Due in 12 days');
      expect(dueLabel(DateTime(2026, 11, 4), today: today), 'Overdue by 1 day');
      expect(
        dueLabel(DateTime(2026, 10, 31), today: today),
        'Overdue by 5 days',
      );
    });

    test('iso and short formats', () {
      expect(isoDate(DateTime(2026, 1, 9)), '2026-01-09');
      expect(shortDate(DateTime(2026, 1, 9)), 'Jan 9, 2026');
      expect(parseDate('2026-11-01'), DateTime(2026, 11, 1));
      expect(parseDate(null), isNull);
    });
  });

  group('ApiClient.normalizeBaseUrl', () {
    test('adds https, strips trailing slashes and a pasted API path', () {
      expect(
        ApiClient.normalizeBaseUrl('niageo.example.com/'),
        'https://niageo.example.com',
      );
      expect(
        ApiClient.normalizeBaseUrl('https://niageo.example.com/api/v1'),
        'https://niageo.example.com',
      );
      expect(
        ApiClient.normalizeBaseUrl('http://localhost:8000'),
        'http://localhost:8000',
      );
      expect(ApiClient.normalizeBaseUrl('   '), isNotEmpty);
    });

    test('token goes on the Authorization header and the base URL carries the API prefix', () async {
      final client = ApiClient();
      client.baseUrl = 'https://x.test';
      client.token = 'abc';
      expect(client.dio.options.baseUrl, 'https://x.test/api/v1');
    });
  });

  group('ApiException', () {
    test(
      'surfaces the first validation message and keeps per-field errors',
      () {
        final request = RequestOptions(path: '/invoices');
        final exception = ApiException.fromDio(
          DioException(
            requestOptions: request,
            type: DioExceptionType.badResponse,
            response: Response(
              requestOptions: request,
              statusCode: 422,
              data: {
                'message': 'The due date must be a date after or equal to issue date. (and 1 more error)',
                'errors': {
                  'due_date': [
                    'The due date must be a date after or equal to issue date.',
                  ],
                  'line_items': ['The line items field is required.'],
                },
              },
            ),
          ),
        );

        expect(exception.isValidation, isTrue);
        expect(
          exception.message,
          'The due date must be a date after or equal to issue date.',
        );
        expect(
          exception.fieldError('line_items'),
          'The line items field is required.',
        );
      },
    );

    test('explains network failures and expiry', () {
      final request = RequestOptions(path: '/x');
      expect(
        ApiException.fromDio(
          DioException(
            requestOptions: request,
            type: DioExceptionType.connectionError,
          ),
        ).message,
        contains('Could not reach the server'),
      );
      final expired = ApiException.fromDio(
        DioException(
          requestOptions: request,
          type: DioExceptionType.badResponse,
          response: Response(
            requestOptions: request,
            statusCode: 401,
            data: {'message': 'Unauthenticated.'},
          ),
        ),
      );
      expect(expired.isUnauthorized, isTrue);
      expect(expired.message, contains('session has expired'));
    });
  });
}
