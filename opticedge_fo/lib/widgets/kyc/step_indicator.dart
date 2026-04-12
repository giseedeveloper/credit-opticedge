import 'package:flutter/material.dart';

import '../../config/constants.dart';

class StepIndicator extends StatelessWidget {
  final int totalSteps;
  final int currentStep;
  final List<String> labels;

  const StepIndicator({
    super.key,
    required this.totalSteps,
    required this.currentStep,
    required this.labels,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 52,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: totalSteps,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final stepNumber = index + 1;
          final isDone = currentStep > stepNumber;
          final isActive = currentStep == stepNumber;

          return AnimatedContainer(
            duration: const Duration(milliseconds: 260),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(
              gradient: isActive
                  ? const LinearGradient(
                      colors: [
                        AppConstants.primary,
                        AppConstants.primaryDark,
                      ],
                    )
                  : null,
              color: isDone
                  ? AppConstants.successSurface
                  : Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: isActive
                    ? Colors.white.withValues(alpha: 0.18)
                    : isDone
                        ? AppConstants.success.withValues(alpha: 0.24)
                        : Colors.white.withValues(alpha: 0.12),
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
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: isActive
                        ? Colors.white.withValues(alpha: 0.16)
                        : isDone
                            ? AppConstants.success
                            : Colors.white.withValues(alpha: 0.08),
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: isDone
                        ? const Icon(Icons.check_rounded,
                            size: 14, color: Colors.white)
                        : Text(
                            '$stepNumber',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: isActive
                                  ? Colors.white
                                  : Colors.white.withValues(alpha: 0.82),
                            ),
                          ),
                  ),
                ),
                const SizedBox(width: 10),
                Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Step $stepNumber',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: isActive
                            ? Colors.white.withValues(alpha: 0.88)
                            : isDone
                                ? AppConstants.success
                                : Colors.white.withValues(alpha: 0.68),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      labels[index],
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: isActive
                            ? Colors.white
                            : isDone
                                ? AppConstants.textPrimary
                                : Colors.white.withValues(alpha: 0.96),
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
