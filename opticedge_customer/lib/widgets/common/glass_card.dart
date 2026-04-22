import 'dart:ui';

import 'package:flutter/material.dart';

import '../../config/constants.dart';

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
    this.blurSigma = 20,
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
    double blurSigma = 22,
  }) {
    final surface = Theme.of(context).cardTheme.color ?? AppConstants.surface;
    return GlassCard(
      key: key,
      padding: padding,
      borderRadius: borderRadius,
      blurSigma: blurSigma,
      tint: surface,
      borderColor: AppConstants.border,
      boxShadow: const [
        BoxShadow(
          color: Color(0x100B1220),
          blurRadius: 28,
          offset: Offset(0, 14),
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
            color: tint.withValues(alpha: 0.72),
            borderRadius: resolvedRadius,
            border: Border.all(
              color: borderColor.withValues(alpha: 0.55),
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
