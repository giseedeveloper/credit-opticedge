import 'package:flutter/material.dart';

import '../../config/customer_colors.dart';

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
    final cc = CustomerColors.of(context);
    final baseGradient = LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: cc.premiumGradientColors,
      stops: cc.premiumGradientStops,
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
              color: cc.premiumOrbSky.withValues(alpha: cc.isDark ? 0.14 : 0.12),
            ),
          ),
        if (useHeroTint)
          Positioned(
            top: -100,
            right: -120,
            child: _orb(
              size: 300,
              color: cc.premiumOrbPrimary.withValues(
                alpha: cc.isDark ? 0.16 : 0.10,
              ),
            ),
          ),
        Positioned(
          bottom: -160,
          left: -100,
          child: _orb(
            size: 320,
            color: cc.premiumOrbPrimaryLight.withValues(
              alpha: cc.isDark ? 0.12 : 0.08,
            ),
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
