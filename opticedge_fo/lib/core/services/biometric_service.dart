import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

class BiometricAvailabilityResult {
  final bool isAvailable;
  final String? message;
  final List<BiometricType> enrolledBiometrics;

  const BiometricAvailabilityResult({
    required this.isAvailable,
    this.message,
    this.enrolledBiometrics = const [],
  });
}

class BiometricAuthenticationResult {
  final bool isAuthenticated;
  final String? message;

  const BiometricAuthenticationResult({
    required this.isAuthenticated,
    this.message,
  });
}

class BiometricService {
  BiometricService._();
  static final instance = BiometricService._();

  final _auth = LocalAuthentication();

  static bool supportsCurrentPlatform({required bool isWeb}) => !isWeb;

  static String availabilityErrorMessage(PlatformException error) {
    switch (error.code) {
      case 'NotAvailable':
        return 'Biometric sensor is not available on this device.';
      case 'NotEnrolled':
        return 'No fingerprint or face profile is enrolled on this device yet.';
      case 'PasscodeNotSet':
        return 'Set a screen lock on the device first, then try again.';
      default:
        return 'Biometric setup is not ready on this device yet.';
    }
  }

  static String authenticationErrorMessage(PlatformException error) {
    switch (error.code) {
      case 'LockedOut':
      case 'PermanentlyLockedOut':
        return 'Biometric sensor is temporarily locked. Unlock the device with PIN or pattern, then try again.';
      case 'NotAvailable':
        return 'Biometric authentication is not available on this device.';
      case 'NotEnrolled':
        return 'No fingerprint or face profile is enrolled on this device yet.';
      case 'PasscodeNotSet':
        return 'Set a screen lock on the device first, then try again.';
      default:
        return 'Biometric verification could not be completed.';
    }
  }

  Future<BiometricAvailabilityResult> availability() async {
    if (!supportsCurrentPlatform(isWeb: kIsWeb)) {
      return const BiometricAvailabilityResult(
        isAvailable: false,
        message: 'Biometric login is not supported in browser previews.',
      );
    }

    try {
      final isDeviceSupported = await _auth.isDeviceSupported();
      if (!isDeviceSupported) {
        return const BiometricAvailabilityResult(
          isAvailable: false,
          message: 'This device does not support biometric login.',
        );
      }

      final enrolled = await _auth.getAvailableBiometrics();
      if (enrolled.isEmpty) {
        return const BiometricAvailabilityResult(
          isAvailable: false,
          message:
              'No fingerprint or face profile is enrolled on this device yet.',
        );
      }

      final canCheck = await _auth.canCheckBiometrics;
      if (!canCheck) {
        return const BiometricAvailabilityResult(
          isAvailable: false,
          message: 'Biometric sensor is not available right now.',
        );
      }

      return BiometricAvailabilityResult(
        isAvailable: true,
        enrolledBiometrics: enrolled,
      );
    } on PlatformException catch (error) {
      return BiometricAvailabilityResult(
        isAvailable: false,
        message: availabilityErrorMessage(error),
      );
    }
  }

  /// Check if biometrics are available on this device.
  Future<bool> get isAvailable async {
    return (await availability()).isAvailable;
  }

  /// Returns the list of enrolled biometric types.
  Future<List<BiometricType>> get enrolledBiometrics async {
    return (await availability()).enrolledBiometrics;
  }

  /// Authenticate the user with biometrics.
  /// Returns `true` if authentication succeeded.
  Future<bool> authenticate(
      {String reason = 'Authenticate to continue'}) async {
    return (await authenticateWithResult(reason: reason)).isAuthenticated;
  }

  Future<BiometricAuthenticationResult> authenticateWithResult({
    String reason = 'Authenticate to continue',
  }) async {
    final availabilityResult = await availability();

    if (!availabilityResult.isAvailable) {
      return BiometricAuthenticationResult(
        isAuthenticated: false,
        message: availabilityResult.message,
      );
    }

    try {
      final authenticated = await _auth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
          sensitiveTransaction: true,
        ),
      );

      if (!authenticated) {
        return const BiometricAuthenticationResult(
          isAuthenticated: false,
          message: 'Biometric verification was cancelled or did not match.',
        );
      }

      return const BiometricAuthenticationResult(isAuthenticated: true);
    } on PlatformException catch (error) {
      return BiometricAuthenticationResult(
        isAuthenticated: false,
        message: authenticationErrorMessage(error),
      );
    }
  }
}
