import 'package:flutter/material.dart';
import '../../config/constants.dart';

class AppButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final bool isLoading;
  final bool outlined;
  final IconData? icon;
  final Color? color;
  final double? width;
  final bool compact;

  /// Screen reader label (defaults to [label]).
  final String? semanticsLabel;

  const AppButton({
    super.key,
    required this.label,
    this.onPressed,
    this.isLoading = false,
    this.outlined = false,
    this.icon,
    this.color,
    this.width,
    this.compact = false,
    this.semanticsLabel,
  });

  @override
  Widget build(BuildContext context) {
    final bg = color ?? AppConstants.primary;
    final verticalPadding = compact ? 12.0 : 17.0;
    final horizontalPadding = compact ? 16.0 : 24.0;
    final borderRadius = compact ? 14.0 : 20.0;
    final iconSize = compact ? 16.0 : 18.0;
    final child = isLoading
        ? const SizedBox(
            width: 20,
            height: 20,
            child: CircularProgressIndicator(
              strokeWidth: 2.5,
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          )
        : Row(
            mainAxisSize: MainAxisSize.max,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (icon != null) ...[
                Icon(icon, size: iconSize),
                SizedBox(width: compact ? 6 : 8),
              ],
              Flexible(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: compact ? 13 : 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          );

    if (outlined) {
      return Semantics(
        button: true,
        label: semanticsLabel ?? label,
        enabled: onPressed != null && !isLoading,
        child: SizedBox(
          width: width,
          child: OutlinedButton(
            onPressed: isLoading ? null : onPressed,
            style: OutlinedButton.styleFrom(
              foregroundColor: bg,
              side: BorderSide(color: bg, width: 1.5),
              backgroundColor: Colors.white.withValues(alpha: 0.88),
              padding: EdgeInsets.symmetric(
                vertical: verticalPadding,
                horizontal: horizontalPadding,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(borderRadius),
              ),
            ),
            child: child,
          ),
        ),
      );
    }

    return Semantics(
      button: true,
      label: semanticsLabel ?? label,
      enabled: onPressed != null && !isLoading,
      child: SizedBox(
        width: width,
        child: DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                bg,
                Color.lerp(bg, AppConstants.primaryDark, 0.35) ??
                    AppConstants.primaryDark,
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(borderRadius),
            boxShadow: [
              BoxShadow(
                color: bg.withValues(alpha: 0.28),
                blurRadius: 24,
                offset: const Offset(0, 12),
              ),
            ],
          ),
          child: ElevatedButton(
            onPressed: isLoading ? null : onPressed,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.transparent,
              foregroundColor: Colors.white,
              disabledBackgroundColor: Colors.transparent,
              disabledForegroundColor: Colors.white.withValues(alpha: 0.72),
              shadowColor: Colors.transparent,
              surfaceTintColor: Colors.transparent,
              padding: EdgeInsets.symmetric(
                vertical: verticalPadding,
                horizontal: horizontalPadding,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(borderRadius),
              ),
              elevation: 0,
            ),
            child: child,
          ),
        ),
      ),
    );
  }
}
