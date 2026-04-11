import 'package:flutter/material.dart';

class AppConstants {
  AppConstants._();

  static const String appName = 'Opticedge FO';
  static const String tagline = 'Fast. Secure. Verified.';
  static const String baseUrl = 'https://credit.opticedgeafrica.net/api/v1';

  // Storage keys
  static const String tokenKey = 'fo_auth_token';
  static const String userKey = 'fo_user_data';
  static const String draftPrefix = 'kyc_draft_';

  // Colors
  static const Color primary = Color(0xFFEA580C);
  static const Color primaryLight = Color(0xFFF97316);
  static const Color primaryDark = Color(0xFFC2410C);
  static const Color primarySurface = Color(0xFFFFF7ED);
  static const Color success = Color(0xFF10B981);
  static const Color error = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);
  static const Color info = Color(0xFF3B82F6);
  static const Color textPrimary = Color(0xFF111827);
  static const Color textSecondary = Color(0xFF6B7280);
  static const Color textHint = Color(0xFF9CA3AF);
  static const Color background = Color(0xFFF9FAFB);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color border = Color(0xFFE5E7EB);
  static const Color borderLight = Color(0xFFF3F4F6);

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
