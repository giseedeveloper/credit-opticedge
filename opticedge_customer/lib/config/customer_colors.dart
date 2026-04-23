import 'package:flutter/material.dart';

import 'constants.dart';

/// Semantic colors for customer glass UI (light + dark).
@immutable
class CustomerColors extends ThemeExtension<CustomerColors> {
  final bool isDark;
  final Color textPrimary;
  final Color textSecondary;
  final Color textHint;
  final Color glassNavMuted;
  final Color border;
  final Color glassInputFill;
  final Color successSurface;
  final Color errorSurface;
  final Color warningSurface;
  final Color primarySurface;
  final List<Color> premiumGradientColors;
  final List<double> premiumGradientStops;
  final Color premiumOrbSky;
  final Color premiumOrbPrimary;
  final Color premiumOrbPrimaryLight;
  final List<Color> homeHeroGradientColors;
  final Color homeHeroOrbPrimary;
  final Color homeHeroOrbSky;
  final Color glassCardTint;
  final Color glassCardBorder;
  final Color floatingNavFill;
  final Color floatingNavBorder;
  final Color scheduleStatGlassFill;
  final Color chromeMuted;

  const CustomerColors({
    required this.isDark,
    required this.textPrimary,
    required this.textSecondary,
    required this.textHint,
    required this.glassNavMuted,
    required this.border,
    required this.glassInputFill,
    required this.successSurface,
    required this.errorSurface,
    required this.warningSurface,
    required this.primarySurface,
    required this.premiumGradientColors,
    required this.premiumGradientStops,
    required this.premiumOrbSky,
    required this.premiumOrbPrimary,
    required this.premiumOrbPrimaryLight,
    required this.homeHeroGradientColors,
    required this.homeHeroOrbPrimary,
    required this.homeHeroOrbSky,
    required this.glassCardTint,
    required this.glassCardBorder,
    required this.floatingNavFill,
    required this.floatingNavBorder,
    required this.scheduleStatGlassFill,
    required this.chromeMuted,
  });

  static CustomerColors of(BuildContext context) {
    return Theme.of(context).extension<CustomerColors>() ??
        CustomerColors.light;
  }

  static const CustomerColors light = CustomerColors(
    isDark: false,
    textPrimary: AppConstants.textPrimary,
    textSecondary: AppConstants.textSecondary,
    textHint: AppConstants.textHint,
    glassNavMuted: AppConstants.glassNavMuted,
    border: AppConstants.border,
    glassInputFill: Color(0xB8FFFFFF),
    successSurface: AppConstants.successSurface,
    errorSurface: AppConstants.errorSurface,
    warningSurface: AppConstants.warningSurface,
    primarySurface: AppConstants.primarySurface,
    premiumGradientColors: [
      Color(0xFFF5F7FB),
      Color(0xFFFDF9F6),
      Color(0xFFE9EEF5),
    ],
    premiumGradientStops: [0.0, 0.52, 1.0],
    premiumOrbSky: Color(0xFF38BDF8),
    premiumOrbPrimary: AppConstants.primary,
    premiumOrbPrimaryLight: AppConstants.primaryLight,
    homeHeroGradientColors: [
      Color(0xFFFFFFFF),
      Color(0xFFFDF8F5),
      Color(0xFFFFF6F0),
    ],
    homeHeroOrbPrimary: AppConstants.primary,
    homeHeroOrbSky: Color(0xFF38BDF8),
    glassCardTint: Colors.white,
    glassCardBorder: Color(0xB8FFFFFF),
    floatingNavFill: Color(0xB8FFFFFF),
    floatingNavBorder: Color(0xD9FFFFFF),
    scheduleStatGlassFill: Color(0x8CFFFFFF),
    chromeMuted: AppConstants.surfaceMuted,
  );

  static const CustomerColors dark = CustomerColors(
    isDark: true,
    textPrimary: Color(0xFFF1F5F9),
    textSecondary: Color(0xFF94A3B8),
    textHint: Color(0xFF64748B),
    glassNavMuted: Color(0xFF8B9CB5),
    border: Color(0xFF334155),
    glassInputFill: Color(0x99202636),
    successSurface: Color(0xFF0F241C),
    errorSurface: Color(0xFF2C1518),
    warningSurface: Color(0xFF2A2212),
    primarySurface: Color(0xFF2A1C16),
    premiumGradientColors: [
      Color(0xFF0E1219),
      Color(0xFF121A24),
      Color(0xFF151D2A),
    ],
    premiumGradientStops: [0.0, 0.48, 1.0],
    premiumOrbSky: Color(0xFF38BDF8),
    premiumOrbPrimary: AppConstants.primary,
    premiumOrbPrimaryLight: AppConstants.primaryLight,
    homeHeroGradientColors: [
      Color(0xFF161E2A),
      Color(0xFF1A2433),
      Color(0xFF141C28),
    ],
    homeHeroOrbPrimary: AppConstants.primary,
    homeHeroOrbSky: Color(0xFF38BDF8),
    glassCardTint: Color(0xFF1E293B),
    glassCardBorder: Color(0xFF64748B),
    floatingNavFill: Color(0xB3263040),
    floatingNavBorder: Color(0x5CFFFFFF),
    scheduleStatGlassFill: Color(0x8C1E293B),
    chromeMuted: Color(0xFF1E2635),
  );

  Color loanStatusColor(String status) {
    return switch (status) {
      'paid' => AppConstants.success,
      'pending' => isDark ? AppConstants.primaryLight : AppConstants.primaryDark,
      'partial' => AppConstants.warning,
      'overdue' => AppConstants.error,
      _ => textHint,
    };
  }

  Color loanStatusBg(String status) {
    return switch (status) {
      'paid' => successSurface,
      'pending' => primarySurface.withValues(alpha: isDark ? 0.95 : 0.65),
      'partial' => warningSurface,
      'overdue' => errorSurface,
      _ => glassCardTint.withValues(alpha: 0.55),
    };
  }

  @override
  CustomerColors copyWith({
    bool? isDark,
    Color? textPrimary,
    Color? textSecondary,
    Color? textHint,
    Color? glassNavMuted,
    Color? border,
    Color? glassInputFill,
    Color? successSurface,
    Color? errorSurface,
    Color? warningSurface,
    Color? primarySurface,
    List<Color>? premiumGradientColors,
    List<double>? premiumGradientStops,
    Color? premiumOrbSky,
    Color? premiumOrbPrimary,
    Color? premiumOrbPrimaryLight,
    List<Color>? homeHeroGradientColors,
    Color? homeHeroOrbPrimary,
    Color? homeHeroOrbSky,
    Color? glassCardTint,
    Color? glassCardBorder,
    Color? floatingNavFill,
    Color? floatingNavBorder,
    Color? scheduleStatGlassFill,
    Color? chromeMuted,
  }) {
    return CustomerColors(
      isDark: isDark ?? this.isDark,
      textPrimary: textPrimary ?? this.textPrimary,
      textSecondary: textSecondary ?? this.textSecondary,
      textHint: textHint ?? this.textHint,
      glassNavMuted: glassNavMuted ?? this.glassNavMuted,
      border: border ?? this.border,
      glassInputFill: glassInputFill ?? this.glassInputFill,
      successSurface: successSurface ?? this.successSurface,
      errorSurface: errorSurface ?? this.errorSurface,
      warningSurface: warningSurface ?? this.warningSurface,
      primarySurface: primarySurface ?? this.primarySurface,
      premiumGradientColors:
          premiumGradientColors ?? this.premiumGradientColors,
      premiumGradientStops:
          premiumGradientStops ?? this.premiumGradientStops,
      premiumOrbSky: premiumOrbSky ?? this.premiumOrbSky,
      premiumOrbPrimary: premiumOrbPrimary ?? this.premiumOrbPrimary,
      premiumOrbPrimaryLight:
          premiumOrbPrimaryLight ?? this.premiumOrbPrimaryLight,
      homeHeroGradientColors:
          homeHeroGradientColors ?? this.homeHeroGradientColors,
      homeHeroOrbPrimary: homeHeroOrbPrimary ?? this.homeHeroOrbPrimary,
      homeHeroOrbSky: homeHeroOrbSky ?? this.homeHeroOrbSky,
      glassCardTint: glassCardTint ?? this.glassCardTint,
      glassCardBorder: glassCardBorder ?? this.glassCardBorder,
      floatingNavFill: floatingNavFill ?? this.floatingNavFill,
      floatingNavBorder: floatingNavBorder ?? this.floatingNavBorder,
      scheduleStatGlassFill:
          scheduleStatGlassFill ?? this.scheduleStatGlassFill,
      chromeMuted: chromeMuted ?? this.chromeMuted,
    );
  }

  @override
  CustomerColors lerp(ThemeExtension<CustomerColors>? other, double t) {
    if (other is! CustomerColors) {
      return this;
    }
    if (t == 0) {
      return this;
    }
    if (t == 1) {
      return other;
    }
    Color lc(Color a, Color b) => Color.lerp(a, b, t)!;
    return CustomerColors(
      isDark: t < 0.5 ? isDark : other.isDark,
      textPrimary: lc(textPrimary, other.textPrimary),
      textSecondary: lc(textSecondary, other.textSecondary),
      textHint: lc(textHint, other.textHint),
      glassNavMuted: lc(glassNavMuted, other.glassNavMuted),
      border: lc(border, other.border),
      glassInputFill: lc(glassInputFill, other.glassInputFill),
      successSurface: lc(successSurface, other.successSurface),
      errorSurface: lc(errorSurface, other.errorSurface),
      warningSurface: lc(warningSurface, other.warningSurface),
      primarySurface: lc(primarySurface, other.primarySurface),
      premiumGradientColors: t < 0.5
          ? premiumGradientColors
          : other.premiumGradientColors,
      premiumGradientStops: t < 0.5
          ? premiumGradientStops
          : other.premiumGradientStops,
      premiumOrbSky: lc(premiumOrbSky, other.premiumOrbSky),
      premiumOrbPrimary: lc(premiumOrbPrimary, other.premiumOrbPrimary),
      premiumOrbPrimaryLight: lc(
        premiumOrbPrimaryLight,
        other.premiumOrbPrimaryLight,
      ),
      homeHeroGradientColors: t < 0.5
          ? homeHeroGradientColors
          : other.homeHeroGradientColors,
      homeHeroOrbPrimary: lc(homeHeroOrbPrimary, other.homeHeroOrbPrimary),
      homeHeroOrbSky: lc(homeHeroOrbSky, other.homeHeroOrbSky),
      glassCardTint: lc(glassCardTint, other.glassCardTint),
      glassCardBorder: lc(glassCardBorder, other.glassCardBorder),
      floatingNavFill: lc(floatingNavFill, other.floatingNavFill),
      floatingNavBorder: lc(floatingNavBorder, other.floatingNavBorder),
      scheduleStatGlassFill: lc(
        scheduleStatGlassFill,
        other.scheduleStatGlassFill,
      ),
      chromeMuted: lc(chromeMuted, other.chromeMuted),
    );
  }
}
