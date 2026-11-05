import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Bumped after anything changes on the server (an invoice saved, a payment recorded…).
/// Data providers watch it, so every screen refetches without wiring them to each other.
final refreshTickProvider = NotifierProvider<RefreshTick, int>(RefreshTick.new);

class RefreshTick extends Notifier<int> {
  @override
  int build() => 0;

  void bump() => state = state + 1;
}
