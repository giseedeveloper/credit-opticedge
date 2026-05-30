import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

class AppConstants {
  AppConstants._();

  static const String appName = 'Opticedge FO';
  static const String tagline = 'Fast. Secure. Verified.';
  static const String appLogoAsset = 'assets/images/opticedge_app_logo.png';

  /// Brand blue from Opticedge Africa logo (#0072BC).
  static const Color brandBlue = Color(0xFF0072BC);
  static const String _productionBaseUrl =
      'https://credit.opticedgeafrica.net/api/v1';

  /// TLS SHA-256 (cert DER) for credit.opticedgeafrica.net.
  static const String productionCertSha256 =
      '9aee56891d6d8451d74cc2ad139a8576648e8ce63d3d3d813fa419ee31d1ae48ff';
  static const String _configuredBaseUrl =
      String.fromEnvironment('API_BASE_URL', defaultValue: '');

  static String get baseUrl => resolveBaseUrl(
        isWeb: kIsWeb,
        isDebug: kDebugMode,
        currentUri: Uri.base,
        configuredBaseUrl: _configuredBaseUrl,
        targetPlatform: defaultTargetPlatform,
      );

  static String resolveBaseUrl({
    required bool isWeb,
    required bool isDebug,
    required Uri currentUri,
    String configuredBaseUrl = '',
    TargetPlatform? targetPlatform,
  }) {
    if (configuredBaseUrl.trim().isNotEmpty) {
      return configuredBaseUrl.trim();
    }

    return _productionBaseUrl;
  }

  static Uri get apiBaseUri => Uri.parse(baseUrl);

  static String get apiOrigin =>
      '${apiBaseUri.scheme}://${apiBaseUri.authority}';

  static String? resolveMediaUrl(String? rawUrl) {
    if (rawUrl == null || rawUrl.trim().isEmpty) {
      return null;
    }

    final parsed = Uri.tryParse(rawUrl.trim());
    if (parsed == null) {
      return rawUrl;
    }

    const storageMarker = '/storage/';
    final markerIndex = parsed.path.indexOf(storageMarker);

    if (markerIndex == -1) {
      return rawUrl;
    }

    final mediaPath = parsed.path.substring(markerIndex + storageMarker.length);
    final proxyUri = Uri.parse('$apiOrigin/api/v1/public-media').replace(
      queryParameters: {'path': mediaPath},
    );

    return proxyUri.toString();
  }

  // Storage keys
  static const String tokenKey = 'fo_auth_token';
  static const String userKey = 'fo_user_data';
  static const String draftPrefix = 'kyc_draft_';

  // Colors
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

  /// KYC wizard — deep nautical header + warm surfaces (distinct from generic hero)
  static const Color kycWizardHeroTop = Color(0xFF070E18);
  static const Color kycWizardHeroMid = Color(0xFF0F2844);
  static const Color kycWizardHeroBottom = Color(0xFF163A5E);
  static const Color kycWizardAccentLine = Color(0xFFFF9F7A);
  static const Color kycWizardHeaderTitle = Color(0xFFF8FAFC);
  static const Color kycWizardSurface = Color(0xFFFBFCFE);
  static const Color kycWizardInsightTop = Color(0xFFFFF8F4);
  static const Color kycWizardInsightBottom = Color(0xFFFFF1E8);
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
  static const Color infoSurface = Color(0xFFEFF6FF);

  // Status colors
  static const Map<String, Color> statusColors = {
    'draft': Color(0xFF6B7280),
    'pending': Color(0xFF3B82F6),
    'approved': Color(0xFF10B981),
    'rejected': Color(0xFFEF4444),
    'needs_correction': Color(0xFFF59E0B),
  };

  static const Map<String, Color> statusBg = {
    'draft': Color(0xFFF3F4F6),
    'pending': Color(0xFFEFF6FF),
    'approved': Color(0xFFECFDF5),
    'rejected': Color(0xFFFEF2F2),
    'needs_correction': Color(0xFFFFFBEB),
  };

  static const Map<String, String> statusLabels = {
    'draft': 'Draft',
    'pending': 'Pending',
    'approved': 'Approved',
    'rejected': 'Rejected',
    'needs_correction': 'Correction',
  };
}
