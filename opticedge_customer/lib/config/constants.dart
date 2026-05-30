import 'package:flutter/material.dart';

class AppConstants {
  AppConstants._();

  static const String appName = 'Opticedge Customer';
  static const String tagline = 'Lipa. Fuatilia. Fanya Zaidi.';
  static const String appLogoAsset =
      'assets/images/customer_app_icon_circle.png';

  // API
  static const String _productionBaseUrl =
      'https://credit.opticedgeafrica.net/api/v1/customer';

  /// TLS SHA-256 (cert DER) for credit.opticedgeafrica.net — override via dart-define.
  static const String productionCertSha256 =
      '9aee56891d6d8451d74cc2ad139a8576648e8ce63d3d3d813fa419ee31d1ae48ff';
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
  static const String biometricEnabledKey = 'customer_biometric_enabled';

  // ─── Brand Colors (matching FO app) ───────────────────────────────
  static const Color primary = Color(0xFFF36D34);
  static const Color primaryLight = Color(0xFFFF8B5B);
  static const Color primaryDark = Color(0xFFD7561E);
  static const Color primarySurface = Color(0xFFFFF1E8);

  static const Color success = Color(0xFF12B981);
  static const Color error = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);
  static const Color info = Color(0xFF2F80ED);
  /// Deep ink reserved for snackbars / tiny accents — avoid large UI fills.
  static const Color ink = Color(0xFF1A2836);

  static const Color heroStart = Color(0xFF103454);
  static const Color heroEnd = Color(0xFF1F5A88);

  /// Softer than pure black — reads well on frosted glass.
  static const Color textPrimary = Color(0xFF2A3648);
  static const Color textSecondary = Color(0xFF5C6B7D);
  static const Color textHint = Color(0xFF8B99AA);

  /// Bottom nav / chrome: visible but calm on light glass.
  static const Color glassNavMuted = Color(0xFF5A6D82);

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
      'pending' => primaryDark,
      'partial' => warning,
      'overdue' => error,
      _ => textHint,
    };
  }

  static Color loanStatusBg(String status) {
    return switch (status) {
      'paid' => successSurface,
      'pending' => primarySurface.withValues(alpha: 0.65),
      'partial' => warningSurface,
      'overdue' => errorSurface,
      _ => surfaceMuted,
    };
  }
}
