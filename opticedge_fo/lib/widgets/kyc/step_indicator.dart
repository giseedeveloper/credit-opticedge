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
    return Column(
      children: [
        Row(
          children: List.generate(totalSteps * 2 - 1, (i) {
            if (i.isOdd) {
              final stepIndex = i ~/ 2;
              final isDone = currentStep > stepIndex + 1;
              return Expanded(
                child: Container(
                  height: 2,
                  color: isDone ? AppConstants.primary : AppConstants.border,
                ),
              );
            }
            final stepIndex = i ~/ 2;
            final isDone = currentStep > stepIndex + 1;
            final isActive = currentStep == stepIndex + 1;
            return AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              width: isActive ? 28 : 22,
              height: isActive ? 28 : 22,
              decoration: BoxDecoration(
                color: isDone
                    ? AppConstants.primary
                    : isActive
                        ? AppConstants.primary
                        : AppConstants.borderLight,
                shape: BoxShape.circle,
                border: Border.all(
                  color: isDone || isActive
                      ? AppConstants.primary
                      : AppConstants.border,
                  width: 1.5,
                ),
                boxShadow: isActive
                    ? [
                        BoxShadow(
                          color: AppConstants.primary.withOpacity(0.3),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        )
                      ]
                    : null,
              ),
              child: Center(
                child: isDone
                    ? const Icon(Icons.check, color: Colors.white, size: 12)
                    : Text(
                        '${stepIndex + 1}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: isActive
                              ? Colors.white
                              : AppConstants.textSecondary,
                        ),
                      ),
              ),
            );
          }),
        ),
        const SizedBox(height: 6),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: labels.asMap().entries.map((e) {
            final isActive = currentStep == e.key + 1;
            return Text(
              e.value,
              style: TextStyle(
                fontSize: 9,
                fontWeight:
                    isActive ? FontWeight.w600 : FontWeight.w400,
                color: isActive
                    ? AppConstants.primary
                    : AppConstants.textHint,
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}
