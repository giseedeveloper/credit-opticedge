import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:opticedge_fo/config/constants.dart';
import 'package:opticedge_fo/core/providers/auth_provider.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  tearDown(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  test('AuthNotifier resolves unauthenticated when secure storage is empty',
      () async {
    FlutterSecureStorage.setMockInitialValues({});
    final container = ProviderContainer();

    await waitForAuthInit(container);
    expect(container.read(authProvider).status, AuthStatus.unauthenticated);

    container.dispose();
  });

  test(
      'AuthNotifier resolves authenticated when token and user JSON are stored',
      () async {
    FlutterSecureStorage.setMockInitialValues({
      AppConstants.tokenKey: 'test-token',
      AppConstants.userKey: jsonEncode({
        'id': '1',
        'name': 'Tester',
        'email': 't@example.com',
        'is_active': true,
        'permissions': <String, dynamic>{},
      }),
    });
    final container = ProviderContainer();

    await waitForAuthInit(container);

    final state = container.read(authProvider);
    expect(state.status, AuthStatus.authenticated);
    expect(state.user?.email, 't@example.com');

    container.dispose();
  });

  test(
      'AuthNotifier offers biometric unlock only for a saved biometric session',
      () {
    expect(
      AuthNotifier.shouldOfferBiometricUnlock(
        biometricEnabled: true,
        hasToken: true,
        hasUser: true,
      ),
      isTrue,
    );

    expect(
      AuthNotifier.shouldOfferBiometricUnlock(
        biometricEnabled: false,
        hasToken: true,
        hasUser: true,
      ),
      isFalse,
    );

    expect(
      AuthNotifier.shouldOfferBiometricUnlock(
        biometricEnabled: true,
        hasToken: false,
        hasUser: true,
      ),
      isFalse,
    );
  });
}

Future<void> waitForAuthInit(ProviderContainer container) async {
  final deadline = DateTime.now().add(const Duration(seconds: 15));
  while (DateTime.now().isBefore(deadline)) {
    if (container.read(authProvider).status != AuthStatus.unknown) {
      return;
    }
    await Future<void>.delayed(const Duration(milliseconds: 20));
  }
  fail(
    'AuthNotifier stayed on AuthStatus.unknown (secure storage init not completing?)',
  );
}
