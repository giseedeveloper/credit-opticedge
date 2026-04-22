import 'dart:ui';

import 'package:flutter/material.dart';

import '../../config/constants.dart';
import '../../config/design_tokens.dart';

class GlassCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;
  final BorderRadiusGeometry borderRadius;
  final double blurSigma;
  final Color tint;
  final Color borderColor;
  final List<BoxShadow> boxShadow;

  const GlassCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.borderRadius = const BorderRadius.all(Radius.circular(22)),
    this.blurSigma = 16,
    this.tint = const Color(0xFFFFFFFF),
    this.borderColor = AppConstants.border,
    this.boxShadow = const [
      BoxShadow(
        color: Color(0x140B1220),
        blurRadius: 22,
        offset: Offset(0, 14),
      ),
    ],
  });

  /// Glass panel that matches Settings: theme [card]/surface tint and borders.
  factory GlassCard.surface(
    BuildContext context, {
    Key? key,
    required Widget child,
    EdgeInsetsGeometry padding = const EdgeInsets.all(16),
    BorderRadiusGeometry borderRadius = const BorderRadius.all(Radius.circular(22)),
    double blurSigma = 20,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final surface = theme.cardTheme.color ?? theme.colorScheme.surface;
    final border = isDark ? DesignTokens.darkBorder : AppConstants.border;
    return GlassCard(
      key: key,
      padding: padding,
      borderRadius: borderRadius,
      blurSigma: blurSigma,
      tint: surface,
      borderColor: border,
      boxShadow: isDark
          ? [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.38),
                blurRadius: 26,
                offset: const Offset(0, 14),
              ),
              BoxShadow(
                color: const Color(0xFF5B8FC9).withValues(alpha: 0.05),
                blurRadius: 20,
                offset: const Offset(0, -4),
              ),
            ]
          : const [
              BoxShadow(
                color: Color(0x140B1220),
                blurRadius: 22,
                offset: Offset(0, 14),
              ),
            ],
      child: child,
    );
  }

  @override
  Widget build(BuildContext context) {
    final resolvedRadius = borderRadius.resolve(Directionality.of(context));

    return ClipRRect(
      borderRadius: resolvedRadius,
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: blurSigma, sigmaY: blurSigma),
        child: Container(
          padding: padding,
          decoration: BoxDecoration(
            color: tint.withValues(alpha: 0.78),
            borderRadius: resolvedRadius,
            border: Border.all(
              color: borderColor.withValues(alpha: 0.65),
              width: 1,
            ),
            boxShadow: boxShadow,
          ),
          child: child,
        ),
      ),
    );
  }
}

