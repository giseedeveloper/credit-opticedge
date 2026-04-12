import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Whether the device currently has a usable network path (Wi‑Fi or mobile).
final onlineStatusProvider = StreamProvider<bool>((ref) async* {
  final connectivity = Connectivity();

  bool online(List<ConnectivityResult> results) =>
      results.any((r) => r != ConnectivityResult.none);

  yield online(await connectivity.checkConnectivity());
  await for (final results in connectivity.onConnectivityChanged) {
    yield online(results);
  }
});
