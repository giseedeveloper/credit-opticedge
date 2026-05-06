import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../config/constants.dart';

/// Special tile for face scanning verification
/// Opens the FaceScannerScreen for live face capture and verification
class FaceScannerTile extends StatelessWidget {
  final String label;
  final bool required;
  final String customerId;
  final String? idFrontUrl;
  final bool verified;
  final double? matchScore;
  final VoidCallback? onRetry;

  const FaceScannerTile({
    super.key,
    required this.label,
    this.required = false,
    required this.customerId,
    this.idFrontUrl,
    this.verified = false,
    this.matchScore,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    final hasIdFront = idFrontUrl != null && idFrontUrl!.isNotEmpty;

    return InkWell(
      onTap: hasIdFront
          ? () => context.push(
                '/kyc/face-scanner/$customerId?id_front_url=${Uri.encodeComponent(idFrontUrl ?? '')}',
              )
          : null,
      borderRadius: BorderRadius.circular(18),
      child: AnimatedContainer(
        duration: 220.ms,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: verified
              ? AppConstants.successSurface
              : hasIdFront
                  ? AppConstants.primarySurface
                  : AppConstants.surfaceMuted,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: verified
                ? AppConstants.success
                : hasIdFront
                    ? AppConstants.primary
                    : AppConstants.border,
            width: 1.4,
          ),
        ),
        child: Column(
          children: [
            Row(
              children: [
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: AppConstants.primary.withValues(alpha: 0.12),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Icon(
                    verified
                        ? Icons.verified_user_rounded
                        : Icons.face_retouching_natural_rounded,
                    size: 28,
                    color: verified
                        ? AppConstants.success
                        : hasIdFront
                            ? AppConstants.primary
                            : AppConstants.textHint,
                  ),
                )
                    .animate(target: hasIdFront && !verified ? 1 : 0)
                    .scale(
                      begin: const Offset(1, 1),
                      end: const Offset(1.05, 1.05),
                      duration: 800.ms,
                      curve: Curves.easeInOut,
                    )
                    .then()
                    .scale(
                      begin: const Offset(1.05, 1.05),
                      end: const Offset(1, 1),
                      duration: 800.ms,
                      curve: Curves.easeInOut,
                    ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            label,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w800,
                              color: AppConstants.textPrimary,
                            ),
                          ),
                          if (required) ...[
                            const SizedBox(width: 4),
                            const Text(
                              '*',
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w700,
                                color: AppConstants.error,
                              ),
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        verified
                            ? 'Verified · ${((matchScore ?? 0) * 100).round()}% match'
                            : hasIdFront
                                ? 'Skani uso — ID itahifadhiwa kiotomatiki inapohitajika'
                                : 'Upload ID front kwanza',
                        style: TextStyle(
                          fontSize: 11,
                          height: 1.4,
                          color: verified
                              ? AppConstants.success
                              : hasIdFront
                                  ? AppConstants.textSecondary
                                  : AppConstants.textHint,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(
                  verified
                      ? Icons.check_circle_rounded
                      : hasIdFront
                          ? Icons.arrow_forward_ios_rounded
                          : Icons.lock_outline_rounded,
                  size: 18,
                  color: verified
                      ? AppConstants.success
                      : hasIdFront
                          ? AppConstants.primary
                          : AppConstants.textHint,
                ),
              ],
            ),
            if (verified && matchScore != null) ...[
              const SizedBox(height: 12),
              ClipRRect(
                borderRadius: BorderRadius.circular(99),
                child: LinearProgressIndicator(
                  value: matchScore!,
                  minHeight: 6,
                  backgroundColor: AppConstants.success.withValues(alpha: 0.15),
                  valueColor: const AlwaysStoppedAnimation<Color>(
                    AppConstants.success,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
