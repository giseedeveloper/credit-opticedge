import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/services.dart';
import 'package:opticedge_fo/core/services/biometric_service.dart';

void main() {
  test('BiometricService disables plugin-backed biometrics on web', () {
    expect(
      BiometricService.supportsCurrentPlatform(isWeb: true),
      isFalse,
    );
  });

  test('BiometricService keeps biometrics enabled on supported native targets',
      () {
    expect(
      BiometricService.supportsCurrentPlatform(isWeb: false),
      isTrue,
    );
  });

  test('BiometricService maps not enrolled availability errors clearly', () {
    final error = PlatformException(code: 'NotEnrolled');

    expect(
      BiometricService.availabilityErrorMessage(error),
      'No fingerprint or face profile is enrolled on this device yet.',
    );
  });

  test('BiometricService maps lockout authentication errors clearly', () {
    final error = PlatformException(code: 'LockedOut');

    expect(
      BiometricService.authenticationErrorMessage(error),
      'Biometric sensor is temporarily locked. Unlock the device with PIN or pattern, then try again.',
    );
  });
}
