import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/theme.dart';
import 'features/auth/auth_controller.dart';
import 'router.dart';

class NiageoApp extends ConsumerWidget {
  const NiageoApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final theme = buildAppTheme();

    // Restoring the stored session takes a moment; the router only exists once we know.
    if (auth.isLoading && !auth.hasValue) {
      return MaterialApp(
        title: 'Niageo Ops',
        theme: theme,
        debugShowCheckedModeBanner: false,
        home: const Scaffold(body: Center(child: CircularProgressIndicator())),
      );
    }

    return MaterialApp.router(
      title: 'Niageo Ops',
      theme: theme,
      debugShowCheckedModeBanner: false,
      routerConfig: ref.watch(routerProvider),
    );
  }
}
