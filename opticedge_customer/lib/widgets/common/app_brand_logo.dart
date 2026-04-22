import 'package:flutter/material.dart';

import '../../config/constants.dart';

/// Opticedge monogram (same asset as launcher icon), styled like FO splash.
class AppBrandLogo extends StatelessWidget {
  const AppBrandLogo({super.key, this.size = 120});

  final double size;

  @override
  Widget build(BuildContext context) {
    final radius = BorderRadius.circular(size * (32 / 120));

    return Material(
      elevation: 14,
      shadowColor: Colors.black.withValues(alpha: 0.35),
      borderRadius: radius,
      clipBehavior: Clip.antiAlias,
      color: Colors.transparent,
      child: SizedBox(
        width: size,
        height: size,
        child: Image.asset(
          AppConstants.appLogoAsset,
          fit: BoxFit.cover,
          filterQuality: FilterQuality.high,
          gaplessPlayback: true,
          errorBuilder: (_, _, _) => Container(
            color: Colors.white.withValues(alpha: 0.2),
            alignment: Alignment.center,
            child: Text(
              'OC',
              style: TextStyle(
                fontSize: size * 0.28,
                fontWeight: FontWeight.w900,
                color: Colors.white,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
