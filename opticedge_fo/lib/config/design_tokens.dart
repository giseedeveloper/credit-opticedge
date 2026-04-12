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

  /// Hero gradients (dashboard, marketing headers)
  static const Color heroStart = AppConstants.heroStart;
  static const Color heroEnd = AppConstants.heroEnd;

  /// Bottom navigation (light)
  static const Color navSelectedBg = AppConstants.primarySurface;
  static const Color navSelectedFg = primary;
  static const Color navUnselectedFg = textHint;

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
}
