import 'package:flutter/material.dart';

import '../../config/constants.dart';
import '../common/app_color_icon.dart';

class StepIndicator extends StatelessWidget {
  final int totalSteps;
  final int currentStep;
  final List<String> labels;
  final List<String>? iconAssets;
  final bool compact;
  final bool onDarkBackground;

  const StepIndicator({
    super.key,
    required this.totalSteps,
    required this.currentStep,
    required this.labels,
    this.iconAssets,
    this.compact = false,
    this.onDarkBackground = false,
  });

  @override
  Widget build(BuildContext context) {
    final pillHeight = compact ? 42.0 : 52.0;
    final horizontalPadding = compact ? 11.0 : 14.0;
    final verticalPadding = compact ? 6.0 : 8.0;
    final badgeSize = compact ? 24.0 : 28.0;
    final stepFontSize = compact ? 9.0 : 10.0;
    final labelFontSize = compact ? 11.0 : 12.0;
    final inactiveSurface = onDarkBackground
        ? Colors.white.withValues(alpha: 0.12)
        : AppConstants.surfaceMuted;
    final inactiveBorder = onDarkBackground
        ? Colors.white.withValues(alpha: 0.12)
        : AppConstants.border;
    final inactiveBadge =
        onDarkBackground ? Colors.white.withValues(alpha: 0.08) : Colors.white;
    final inactiveText = onDarkBackground
        ? Colors.white.withValues(alpha: 0.96)
        : AppConstants.textSecondary;
    final inactiveStepText = onDarkBackground
        ? Colors.white.withValues(alpha: 0.68)
        : AppConstants.textHint;

    return SizedBox(
      height: pillHeight,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: totalSteps,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final stepNumber = index + 1;
          final isDone = currentStep > stepNumber;
          final isActive = currentStep == stepNumber;
          final iconAsset = iconAssets != null && index < iconAssets!.length
              ? iconAssets![index]
              : null;

          return AnimatedContainer(
            duration: const Duration(milliseconds: 260),
            padding: EdgeInsets.symmetric(
              horizontal: horizontalPadding,
              vertical: verticalPadding,
            ),
            decoration: BoxDecoration(
              gradient: isActive
                  ? const LinearGradient(
                      colors: [
                        AppConstants.primary,
                        AppConstants.primaryDark,
                      ],
                    )
                  : null,
              color: isDone ? AppConstants.successSurface : inactiveSurface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: isActive
                    ? (onDarkBackground
                        ? Colors.white.withValues(alpha: 0.18)
                        : AppConstants.primary.withValues(alpha: 0.16))
                    : isDone
                        ? AppConstants.success.withValues(alpha: 0.24)
                        : inactiveBorder,
              ),
              boxShadow: isActive
                  ? [
                      BoxShadow(
                        color: AppConstants.primary.withValues(alpha: 0.28),
                        blurRadius: 18,
                        offset: const Offset(0, 10),
                      ),
                    ]
                  : null,
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: badgeSize,
                  height: badgeSize,
                  decoration: BoxDecoration(
                    color: isActive
                        ? Colors.white.withValues(alpha: 0.16)
                        : isDone
                            ? AppConstants.success
                            : inactiveBadge,
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: isDone
                        ? const Icon(Icons.check_rounded,
                            size: 14, color: Colors.white)
                        : iconAsset != null
                            ? AppColorIcon(
                                assetName: iconAsset,
                                size: compact ? 14 : 16,
                                opacity: isActive ? 1 : 0.75,
                                semanticsLabel: labels[index],
                              )
                            : Text(
                                '$stepNumber',
                                style: TextStyle(
                                  fontSize: compact ? 10 : 11,
                                  fontWeight: FontWeight.w800,
                                  color: isActive
                                      ? Colors.white
                                      : onDarkBackground
                                          ? Colors.white.withValues(alpha: 0.82)
                                          : AppConstants.textSecondary,
                                ),
                              ),
                  ),
                ),
                const SizedBox(width: 10),
                if (compact)
                  Text(
                    labels[index],
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: labelFontSize,
                      fontWeight: FontWeight.w700,
                      color: isActive
                          ? Colors.white
                          : isDone
                              ? AppConstants.textPrimary
                              : inactiveText,
                    ),
                  )
                else
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Step $stepNumber',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: stepFontSize,
                          fontWeight: FontWeight.w700,
                          color: isActive
                              ? Colors.white.withValues(alpha: 0.88)
                              : isDone
                                  ? AppConstants.success
                                  : inactiveStepText,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        labels[index],
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: labelFontSize,
                          fontWeight: FontWeight.w700,
                          color: isActive
                              ? Colors.white
                              : isDone
                                  ? AppConstants.textPrimary
                                  : inactiveText,
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
