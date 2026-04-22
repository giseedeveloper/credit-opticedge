import 'package:flutter/material.dart';

import '../../config/design_tokens.dart';

class PremiumGlassBackground extends StatelessWidget {
  final Widget child;
  final bool useHeroTint;

  const PremiumGlassBackground({
    super.key,
    required this.child,
    this.useHeroTint = true,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final baseGradient = isDark
        ? const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              DesignTokens.darkBackground,
              Color(0xFF0C1930),
              Color(0xFF0B1220),
            ],
            stops: [0.0, 0.55, 1.0],
          )
        : LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              const Color(0xFFFFFFFF),
              DesignTokens.primarySurface.withValues(alpha: 0.72),
              const Color(0xFFFFFFFF),
            ],
            stops: const [0.0, 0.55, 1.0],
          );

    return Stack(
      fit: StackFit.expand,
      children: [
        DecoratedBox(decoration: BoxDecoration(gradient: baseGradient)),
        if (useHeroTint)
          Positioned(
            top: -160,
            left: -120,
            child: _orb(
              size: 320,
              color: DesignTokens.accentSky.withValues(alpha: isDark ? 0.12 : 0.10),
            ),
          ),
        if (useHeroTint)
          Positioned(
            top: -120,
            right: -140,
            child: _orb(
              size: 360,
              color: DesignTokens.primary.withValues(alpha: isDark ? 0.12 : 0.10),
            ),
          ),
        Positioned(
          bottom: -180,
          left: -120,
          child: _orb(
            size: 360,
            color: DesignTokens.primaryLight.withValues(alpha: isDark ? 0.10 : 0.08),
          ),
        ),
        child,
      ],
    );
  }

  Widget _orb({required double size, required Color color}) {
    return IgnorePointer(
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: color,
        ),
      ),
    );
  }
}

