import 'dart:ui';

import 'package:flutter/material.dart';

import '../../config/constants.dart';
import '../../config/customer_colors.dart';

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
    this.borderRadius = const BorderRadius.all(Radius.circular(24)),
    this.blurSigma = 26,
    this.tint = const Color(0xFFFFFFFF),
    this.borderColor = AppConstants.border,
    this.boxShadow = const [
      BoxShadow(
        color: Color(0x120B1220),
        blurRadius: 24,
        offset: Offset(0, 12),
      ),
    ],
  });

  factory GlassCard.surface(
    BuildContext context, {
    Key? key,
    required Widget child,
    EdgeInsetsGeometry padding = const EdgeInsets.all(16),
    BorderRadiusGeometry borderRadius =
        const BorderRadius.all(Radius.circular(24)),
    double blurSigma = 26,
  }) {
    final cc = CustomerColors.of(context);
    return GlassCard(
      key: key,
      padding: padding,
      borderRadius: borderRadius,
      blurSigma: blurSigma,
      tint: cc.glassCardTint,
      borderColor: cc.glassCardBorder,
      boxShadow: [
        BoxShadow(
          color: cc.isDark
              ? Colors.black.withValues(alpha: 0.45)
              : const Color(0x0C0B1220),
          blurRadius: 32,
          offset: const Offset(0, 16),
        ),
      ],
      child: child,
    );
  }

  /// Tinted glass for quick-action / accent tiles.
  factory GlassCard.tinted({
    Key? key,
    required Widget child,
    required Color surfaceTint,
    required Color accent,
    EdgeInsetsGeometry padding = const EdgeInsets.all(16),
    BorderRadiusGeometry borderRadius =
        const BorderRadius.all(Radius.circular(24)),
    double blurSigma = 20,
  }) {
    return GlassCard(
      key: key,
      padding: padding,
      borderRadius: borderRadius,
      blurSigma: blurSigma,
      tint: surfaceTint,
      borderColor: accent.withValues(alpha: 0.22),
      boxShadow: [
        BoxShadow(
          color: accent.withValues(alpha: 0.10),
          blurRadius: 22,
          offset: const Offset(0, 12),
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
            color: tint.withValues(alpha: 0.58),
            borderRadius: resolvedRadius,
            border: Border.all(
              color: borderColor.withValues(alpha: 0.42),
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
