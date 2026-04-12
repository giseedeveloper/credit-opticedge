import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

class AppColorIcon extends StatelessWidget {
  final String assetName;
  final double size;
  final double opacity;
  final Color? tintColor;
  final BlendMode tintBlendMode;
  final String? semanticsLabel;

  const AppColorIcon({
    super.key,
    required this.assetName,
    this.size = 24,
    this.opacity = 1,
    this.tintColor,
    this.tintBlendMode = BlendMode.srcIn,
    this.semanticsLabel,
  });

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: opacity,
      child: SvgPicture.asset(
        assetName,
        width: size,
        height: size,
        fit: BoxFit.contain,
        semanticsLabel: semanticsLabel,
        colorFilter: tintColor == null
            ? null
            : ColorFilter.mode(tintColor!, tintBlendMode),
      ),
    );
  }
}
