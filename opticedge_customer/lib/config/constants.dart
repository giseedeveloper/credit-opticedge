import 'package:flutter/material.dart';

class AppConstants {
  AppConstants._();

  static const String appName = 'Opticedge Customer';
  static const String tagline = 'Lipa. Fuatilia. Fanya Zaidi.';
  static const String appLogoAsset = 'assets/images/app_logo.png';

  // API
  static const String _productionBaseUrl =
      'https://credit.opticedgeafrica.net/api/v1/customer';
  static const String _configuredBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: '',
  );

  static String get baseUrl {
    if (_configuredBaseUrl.trim().isNotEmpty) return _configuredBaseUrl.trim();
    return _productionBaseUrl;
  }

  static Uri get apiBaseUri => Uri.parse(baseUrl);
  static String get apiOrigin =>
      '${apiBaseUri.scheme}://${apiBaseUri.authority}';

  // Storage keys
  static const String tokenKey = 'customer_auth_token';
  static const String customerKey = 'customer_profile';

  // ─── Brand Colors (matching FO app) ───────────────────────────────
  static const Color primary = Color(0xFFF36D34);
  static const Color primaryLight = Color(0xFFFF8B5B);
  static const Color primaryDark = Color(0xFFD7561E);
  static const Color primarySurface = Color(0xFFFFF1E8);

  static const Color success = Color(0xFF12B981);
  static const Color error = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);
  static const Color info = Color(0xFF2F80ED);
  static const Color ink = Color(0xFF0E1826);

  static const Color heroStart = Color(0xFF103454);
  static const Color heroEnd = Color(0xFF1F5A88);

  static const Color textPrimary = Color(0xFF111B2A);
  static const Color textSecondary = Color(0xFF607087);
  static const Color textHint = Color(0xFF94A3B8);

  static const Color background = Color(0xFFF2F5F9);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceRaised = Color(0xFFFDFEFF);
  static const Color surfaceMuted = Color(0xFFF6F8FB);

  static const Color border = Color(0xFFE2E8F0);
  static const Color borderLight = Color(0xFFF1F5F9);

  static const Color successSurface = Color(0xFFECFDF5);
  static const Color errorSurface = Color(0xFFFEF2F2);
  static const Color warningSurface = Color(0xFFFFF7ED);

  // ─── Loan status colors ───────────────────────────────────────────
  static Color loanStatusColor(String status) {
    return switch (status) {
      'paid' => success,
      'pending' => info,
      'partial' => warning,
      'overdue' => error,
      _ => textHint,
    };
  }

  static Color loanStatusBg(String status) {
    return switch (status) {
      'paid' => successSurface,
      'pending' => const Color(0xFFEFF6FF),
      'partial' => warningSurface,
      'overdue' => errorSurface,
      _ => surfaceMuted,
    };
  }
}
