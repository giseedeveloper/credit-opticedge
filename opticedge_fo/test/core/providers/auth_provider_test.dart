import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/providers/auth_provider.dart';

void main() {
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
