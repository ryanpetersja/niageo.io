import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/auth/auth_controller.dart';
import 'features/auth/login_screen.dart';
import 'features/clients/client_detail_screen.dart';
import 'features/clients/clients_screen.dart';
import 'features/dashboard/pipeline_screen.dart';
import 'features/invoices/invoice_detail_screen.dart';
import 'features/invoices/invoice_editor_screen.dart';
import 'features/invoices/invoices_screen.dart';
import 'features/settings/settings_screen.dart';
import 'shell.dart';

final rootNavigatorKey = GlobalKey<NavigatorState>();

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = _AuthRefresh(ref);
  ref.onDispose(refresh.dispose);

  return GoRouter(
    navigatorKey: rootNavigatorKey,
    initialLocation: '/pipeline',
    refreshListenable: refresh,
    redirect: (context, state) {
      final signedIn = ref.read(authControllerProvider).value != null;
      final atLogin = state.matchedLocation == '/login';
      if (!signedIn) return atLogin ? null : '/login';
      if (atLogin) return '/pipeline';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      // Editors cover the whole screen (no tab bar underneath).
      GoRoute(
        path: '/invoices/new',
        parentNavigatorKey: rootNavigatorKey,
        builder: (context, state) => InvoiceEditorScreen(
          clientId: int.tryParse(state.uri.queryParameters['client'] ?? ''),
        ),
      ),
      GoRoute(
        path: '/invoices/:id/edit',
        parentNavigatorKey: rootNavigatorKey,
        builder: (context, state) => InvoiceEditorScreen(
          invoiceId: int.parse(state.pathParameters['id']!),
        ),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, shell) => AppShell(shell: shell),
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/pipeline',
                builder: (context, state) => const PipelineScreen(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/invoices',
                builder: (context, state) => const InvoicesScreen(),
                routes: [
                  GoRoute(
                    path: ':id',
                    builder: (context, state) => InvoiceDetailScreen(
                      invoiceId: int.parse(state.pathParameters['id']!),
                    ),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/clients',
                builder: (context, state) => const ClientsScreen(),
                routes: [
                  GoRoute(
                    path: ':id',
                    builder: (context, state) => ClientDetailScreen(
                      clientId: int.parse(state.pathParameters['id']!),
                    ),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/settings',
                builder: (context, state) => const SettingsScreen(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
});

/// Re-runs the router's redirect whenever the session changes (sign in, sign out, expiry).
class _AuthRefresh extends ChangeNotifier {
  _AuthRefresh(Ref ref) {
    _subscription = ref.listen(
      authControllerProvider,
      (_, _) => notifyListeners(),
    );
  }

  late final ProviderSubscription<AsyncValue<Object?>> _subscription;

  @override
  void dispose() {
    _subscription.close();
    super.dispose();
  }
}
