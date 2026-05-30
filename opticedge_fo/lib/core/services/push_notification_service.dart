import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Push notifications (FCM) — prepared hook; enable when Firebase is configured.
class PushNotificationService {
  PushNotificationService._();
  static final instance = PushNotificationService._();

  static const String _tokenKey = 'fo_push_token_placeholder';

  bool _initialized = false;

  Future<bool> initialize({required bool userEnabled}) async {
    if (!userEnabled) {
      return false;
    }

    if (kIsWeb) {
      return false;
    }

    // FCM requires google-services.json / GoogleService-Info.plist.
    // When firebase_messaging is wired, register token here.
    _initialized = true;
    debugPrint(
      'PushNotificationService: FCM not configured — in-app settings only.',
    );

    return false;
  }

  Future<void> syncPreference(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('fo_push_enabled', enabled);
    if (!enabled) {
      await prefs.remove(_tokenKey);
    }
  }

  bool get isConfigured => _initialized;
}
