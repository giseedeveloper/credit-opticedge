import 'package:flutter/material.dart';

import '../../config/constants.dart';

/// Brand logo used on splash, login, and anywhere the app icon is shown in-app.
class AppLogo extends StatelessWidget {
  const AppLogo({
    super.key,
    this.size = 120,
    this.borderRadius = 28,
    this.showShadow = true,
    this.elevation = 0,
  });

  final double size;
  final double borderRadius;
  final bool showShadow;
  final double elevation;

  @override
  Widget build(BuildContext context) {
    final logo = ClipRRect(
      borderRadius: BorderRadius.circular(borderRadius),
      child: Image.asset(
        AppConstants.appLogoAsset,
        width: size,
        height: size,
        fit: BoxFit.contain,
        filterQuality: FilterQuality.high,
        gaplessPlayback: true,
        errorBuilder: (_, __, ___) => Container(
          width: size,
          height: size,
          color: AppConstants.infoSurface,
          alignment: Alignment.center,
          child: const Icon(
            Icons.verified_user_rounded,
            color: AppConstants.info,
            size: 40,
          ),
        ),
      ),
    );

    if (!showShadow && elevation <= 0) {
      return logo;
    }

    return Material(
      elevation: elevation,
      shadowColor: Colors.black.withValues(alpha: 0.28),
      borderRadius: BorderRadius.circular(borderRadius),
      clipBehavior: Clip.antiAlias,
      color: Colors.transparent,
      child: showShadow
          ? DecoratedBox(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(borderRadius),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.22),
                    blurRadius: 24,
                    offset: const Offset(0, 12),
                  ),
                ],
              ),
              child: logo,
            )
          : logo,
    );
  }
}
