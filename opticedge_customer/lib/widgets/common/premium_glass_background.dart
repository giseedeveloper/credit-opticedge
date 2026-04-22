import 'package:flutter/material.dart';

import '../../config/design_tokens.dart';

/// Soft premium backdrop: light frosted wash + brand orbs (matches FO).
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
    final baseGradient = LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: [
        const Color(0xFFF8FAFD),
        DesignTokens.primarySurface.withValues(alpha: 0.55),
        const Color(0xFFEEF2F8),
      ],
      stops: const [0.0, 0.45, 1.0],
    );

    return Stack(
      fit: StackFit.expand,
      children: [
        DecoratedBox(decoration: BoxDecoration(gradient: baseGradient)),
        if (useHeroTint)
          Positioned(
            top: -140,
            left: -100,
            child: _orb(
              size: 280,
              color: DesignTokens.accentSky.withValues(alpha: 0.12),
            ),
          ),
        if (useHeroTint)
          Positioned(
            top: -100,
            right: -120,
            child: _orb(
              size: 300,
              color: DesignTokens.primary.withValues(alpha: 0.10),
            ),
          ),
        Positioned(
          bottom: -160,
          left: -100,
          child: _orb(
            size: 320,
            color: DesignTokens.primaryLight.withValues(alpha: 0.08),
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
        decoration: BoxDecoration(shape: BoxShape.circle, color: color),
      ),
    );
  }
}
