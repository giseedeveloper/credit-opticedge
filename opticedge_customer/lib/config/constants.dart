import 'package:flutter/material.dart';

class AppConstants {
  AppConstants._();

  // API
  static const String apiBaseUrl = 'https://credit.opticedgeafrica.net/api/v1/customer';
  static const String devApiBaseUrl = 'http://10.0.2.2:8000/api/v1/customer';

  static String get effectiveBaseUrl {
    const bool isDev = bool.fromEnvironment('DEV', defaultValue: false);
    return isDev ? devApiBaseUrl : apiBaseUrl;
  }

  // Storage keys
  static const String tokenKey = 'customer_auth_token';
  static const String customerKey = 'customer_profile';

  // Colors
  static const Color primary = Color(0xFF1E3A5F);
  static const Color primaryLight = Color(0xFF2D5A8E);
  static const Color accent = Color(0xFF4CAF50);
  static const Color warning = Color(0xFFFF9800);
  static const Color danger = Color(0xFFF44336);
  static const Color surface = Color(0xFFF5F7FA);
  static const Color cardBg = Colors.white;

  // Loan status colors
  static Color loanStatusColor(String status) {
    return switch (status) {
      'paid' => accent,
      'pending' => const Color(0xFF90CAF9),
      'partial' => warning,
      'overdue' => danger,
      _ => Colors.grey,
    };
  }
}
