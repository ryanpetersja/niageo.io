import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:niageo_ops/core/api/api_exception.dart';
import 'package:niageo_ops/core/storage/secure_store.dart';
import 'package:niageo_ops/core/theme.dart';
import 'package:niageo_ops/features/auth/auth_controller.dart';
import 'package:niageo_ops/features/auth/auth_models.dart';
import 'package:niageo_ops/features/auth/auth_repository.dart';
import 'package:niageo_ops/features/auth/login_screen.dart';

class _FakeAuthRepository implements AuthRepository {
  _FakeAuthRepository({this.failWith});

  final ApiException? failWith;
  final calls = <Map<String, String>>[];

  @override
  Future<Session> login({
    required String baseUrl,
    required String email,
    required String password,
    required String deviceName,
  }) async {
    calls.add({
      'baseUrl': baseUrl,
      'email': email,
      'password': password,
      'deviceName': deviceName,
    });
    if (failWith != null) throw failWith!;
    return Session(
      baseUrl: baseUrl,
      token: 'tok',
      user: AppUser(id: 1, name: 'Ryan', email: email),
    );
  }

  @override
  Future<void> logout() async {}

  @override
  Future<AppUser> me() async => throw UnimplementedError();
}

Future<ProviderContainer> pumpLogin(
  WidgetTester tester, {
  ApiException? failWith,
}) async {
  final store = MemorySessionStore();
  final repo = _FakeAuthRepository(failWith: failWith);
  final container = ProviderContainer(
    overrides: [
      sessionStoreProvider.overrideWithValue(store),
      authRepositoryProvider.overrideWithValue(repo),
    ],
  );
  addTearDown(container.dispose);
  // Resolve the stored-session lookup before the screen exists, as the app does.
  await container.read(authControllerProvider.future);
  await tester.pumpWidget(
    UncontrolledProviderScope(
      container: container,
      child: MaterialApp(theme: buildAppTheme(), home: const LoginScreen()),
    ),
  );
  await tester.pumpAndSettle();
  return container;
}

void main() {
  testWidgets('validates before calling the server', (tester) async {
    await pumpLogin(tester);

    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pumpAndSettle();

    expect(find.text('Enter your email address.'), findsOneWidget);
    expect(find.text('Enter your password.'), findsOneWidget);
  });

  testWidgets('shows the server\'s message when credentials are wrong', (
    tester,
  ) async {
    await pumpLogin(
      tester,
      failWith: ApiException(
        'Wrong',
        statusCode: 422,
        errors: {
          'email': ['The email or password is incorrect.'],
        },
      ),
    );

    await tester.enterText(
      find.widgetWithText(TextFormField, 'Email'),
      'ryan@example.test',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Password'),
      'nope',
    );
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pumpAndSettle();

    expect(find.text('The email or password is incorrect.'), findsOneWidget);
  });

  testWidgets('a successful sign-in stores the session', (tester) async {
    final container = await pumpLogin(tester);

    await tester.enterText(
      find.widgetWithText(TextFormField, 'Email'),
      'ryan@example.test',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Password'),
      'secret123',
    );
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pumpAndSettle();

    final session = container.read(authControllerProvider).value;
    expect(session?.user.email, 'ryan@example.test');
    expect(session?.token, 'tok');
    expect((await container.read(sessionStoreProvider).read())?.token, 'tok');
  });
}
