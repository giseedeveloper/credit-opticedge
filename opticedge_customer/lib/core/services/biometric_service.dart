import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

class BiometricService {
  BiometricService._();
  static final instance = BiometricService._();

  final _auth = LocalAuthentication();

  Future<bool> get isAvailable async {
    if (kIsWeb) {
      return false;
    }
    try {
      if (!await _auth.isDeviceSupported()) {
        return false;
      }
      return await _auth.canCheckBiometrics &&
          (await _auth.getAvailableBiometrics()).isNotEmpty;
    } on PlatformException {
      return false;
    }
  }

  Future<bool> authenticate({
    String reason = 'Thibitisha utambulisho wako',
  }) async {
    if (kIsWeb) {
      return false;
    }
    try {
      return await _auth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );
    } on PlatformException {
      return false;
    }
  }
}
