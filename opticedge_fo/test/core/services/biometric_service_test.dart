import 'package:flutter_test/flutter_test.dart';
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
}
