import 'package:flutter/material.dart';
import 'constants.dart';

/// Single reference for brand surfaces used across login, shell, dashboard, and KYC.
/// Prefer these (or [Theme.of(context).colorScheme]) over ad-hoc hex in widgets.
abstract final class DesignTokens {
  static const Color primary = AppConstants.primary;
  static const Color primaryDark = AppConstants.primaryDark;
  static const Color primaryLight = AppConstants.primaryLight;
  static const Color primarySurface = AppConstants.primarySurface;

  static const Color background = AppConstants.background;
  static const Color surface = AppConstants.surface;
  static const Color surfaceRaised = AppConstants.surfaceRaised;
  static const Color surfaceMuted = AppConstants.surfaceMuted;

  static const Color textPrimary = AppConstants.textPrimary;
  static const Color textSecondary = AppConstants.textSecondary;
  static const Color textHint = AppConstants.textHint;

  static const Color border = AppConstants.border;
  static const Color success = AppConstants.success;
  static const Color error = AppConstants.error;
  static const Color warning = AppConstants.warning;

  /// Hero gradients (dashboard, login, splash — same system)
  static const Color heroStart = AppConstants.heroStart;
  static const Color heroEnd = AppConstants.heroEnd;

  /// Decorative cool accent (orbs, highlights)
  static const Color accentSky = Color(0xFF38BDF8);

  /// Dark mode shell — navy-tinted, aligned with [heroStart] / [AppConstants.ink]
  static const Color darkBackground = Color(0xFF0B1220);
  static const Color darkSurface = Color(0xFF131B2A);
  static const Color darkSurfaceElevated = Color(0xFF1A2436);
  static const Color darkBorder = Color(0xFF243047);

  /// Dashboard stat / quick-action accents (light)
  static const Color statBlue = AppConstants.info;
  static const Color statBlueBg = Color(0xFFF4F8FF);
  static const Color statBlueAccent = Color(0xFFDCEAFF);
  static const Color statAmber = AppConstants.warning;
  static const Color statAmberBg = Color(0xFFFFF8EC);
  static const Color statAmberAccent = Color(0xFFFFE9BF);
  static const Color statViolet = Color(0xFF8B5CF6);
  static const Color statVioletBg = Color(0xFFF6F1FF);
  static const Color statVioletAccent = Color(0xFFE6DAFF);
  static const Color statGreen = AppConstants.success;
  static const Color statGreenBg = AppConstants.successSurface;
  static const Color statGreenAccent = Color(0xFFCFF8E3);

  /// Dashboard stats / quick-action glass (dark)
  static const Color statBlueBgDark = Color(0xFF152A3F);
  static const Color statBlueAccentDark = Color(0xFF1C3A56);
  static const Color statAmberBgDark = Color(0xFF2B2318);
  static const Color statAmberAccentDark = Color(0xFF3D2E1A);
  static const Color statVioletBgDark = Color(0xFF221C35);
  static const Color statVioletAccentDark = Color(0xFF2E2650);
  static const Color statGreenBgDark = Color(0xFF142925);
  static const Color statGreenAccentDark = Color(0xFF1B3D35);
  static const Color statRegisterBgDark = Color(0xFF2A1F18);

  /// KYC outcome banner (dark)
  static const Color kycInsightTopDark = Color(0xFF1A2538);
  static const Color kycInsightBottomDark = Color(0xFF152030);

  /// Bottom navigation (light)
  static const Color navSelectedBg = AppConstants.primarySurface;
  static const Color navSelectedFg = primary;
  static const Color navUnselectedFg = textHint;

  /// Bottom navigation (dark)
  static const Color darkNavBarBg = darkSurfaceElevated;
  static const Color darkNavBarBorder = darkBorder;

  /// Same gradient as dashboard hero — use for login header, splash, profile hero.
  static LinearGradient get heroGradient => const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          heroStart,
          heroEnd,
          Color(0xFF10263F),
        ],
        stops: [0.0, 0.62, 1.0],
      );

  /// Subtle warm wash at bottom of brand surfaces (CTA emphasis)
  static LinearGradient get heroGradientWithPrimaryHint => LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          heroStart,
          heroEnd,
          Color.lerp(const Color(0xFF10263F), primary, 0.08)!,
        ],
        stops: const [0.0, 0.58, 1.0],
      );

  /// Hero for dashboard / auth when app is in dark mode (keeps light typography)
  static LinearGradient get heroGradientDark => const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          Color(0xFF0C1528),
          Color(0xFF132A45),
          Color(0xFF0B1220),
        ],
        stops: [0.0, 0.6, 1.0],
      );

  static LinearGradient get heroGradientWithPrimaryHintDark => LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          const Color(0xFF0D1729),
          const Color(0xFF16324E),
          Color.lerp(const Color(0xFF0B1220), primary, 0.07)!,
        ],
        stops: const [0.0, 0.56, 1.0],
      );
}
