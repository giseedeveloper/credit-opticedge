import 'package:flutter/material.dart';
import 'constants.dart';

/// Brand surfaces aligned with Opticedge FO — use with glass UI.
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

  static const Color heroStart = AppConstants.heroStart;
  static const Color heroEnd = AppConstants.heroEnd;

  static const Color accentSky = Color(0xFF38BDF8);

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

  /// Customer app headers / hero — light frosted, no heavy navy blocks.
  static LinearGradient get consumerLightHero => const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          Color(0xFFFFFFFF),
          Color(0xFFFDF8F5),
          Color(0xFFFFF6F0),
        ],
        stops: [0.0, 0.45, 1.0],
      );
}
