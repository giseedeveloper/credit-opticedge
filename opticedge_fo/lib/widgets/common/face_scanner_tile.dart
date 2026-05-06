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

  /// Called after the face scanner route closes (pass, fail, review, or back).
  final Future<void> Function()? onScannerClosed;

  const FaceScannerTile({
    super.key,
    required this.label,
    this.required = false,
    required this.customerId,
    this.idFrontUrl,
    this.verified = false,
    this.matchScore,
    this.onRetry,
    this.onScannerClosed,
  });

  @override
  Widget build(BuildContext context) {
    final hasIdFront = idFrontUrl != null && idFrontUrl!.isNotEmpty;

    return ClipRRect(
      borderRadius: BorderRadius.circular(18),
      clipBehavior: Clip.hardEdge,
      child: InkWell(
        onTap: hasIdFront
            ? () async {
                await context.push(
                  '/kyc/face-scanner/$customerId?id_front_url=${Uri.encodeComponent(idFrontUrl ?? '')}',
                );
                if (context.mounted) {
                  await onScannerClosed?.call();
                }
              }
            : null,
        borderRadius: BorderRadius.circular(18),
        child: AnimatedContainer(
          duration: 220.ms,
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(10, 10, 8, 10),
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
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 48,
                    height: 48,
                    child: ClipRect(
                      child: Center(
                        child: Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(
                                color: AppConstants.primary
                                    .withValues(alpha: 0.12),
                                blurRadius: 10,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: Icon(
                            verified
                                ? Icons.verified_user_rounded
                                : Icons.face_retouching_natural_rounded,
                            size: 24,
                            color: verified
                                ? AppConstants.success
                                : hasIdFront
                                    ? AppConstants.primary
                                    : AppConstants.textHint,
                          ),
                        )
                            .animate(
                                target: hasIdFront && !verified ? 1 : 0)
                            .scale(
                              begin: const Offset(1, 1),
                              end: const Offset(1.04, 1.04),
                              duration: 800.ms,
                              curve: Curves.easeInOut,
                            )
                            .then()
                            .scale(
                              begin: const Offset(1.04, 1.04),
                              end: const Offset(1, 1),
                              duration: 800.ms,
                              curve: Curves.easeInOut,
                            ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text.rich(
                          TextSpan(
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: AppConstants.textPrimary,
                            ),
                            children: [
                              TextSpan(text: label),
                              if (required)
                                const TextSpan(
                                  text: ' *',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: AppConstants.error,
                                  ),
                                ),
                            ],
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          softWrap: false,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          verified
                              ? 'Umepitisha · ${((matchScore ?? 0) * 100).round()}%'
                              : hasIdFront
                                  ? 'Skani uso — ID itahifadhiwa inapohitajika'
                                  : 'Upload ID front kwanza',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 9.5,
                            height: 1.2,
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
                  SizedBox(
                    width: 22,
                    child: Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Icon(
                        verified
                            ? Icons.check_circle_rounded
                            : hasIdFront
                                ? Icons.chevron_right_rounded
                                : Icons.lock_outline_rounded,
                        size: 20,
                        color: verified
                            ? AppConstants.success
                            : hasIdFront
                                ? AppConstants.primary
                                : AppConstants.textHint,
                      ),
                    ),
                  ),
                ],
              ),
              if (verified && matchScore != null) ...[
                const SizedBox(height: 6),
                ClipRRect(
                  borderRadius: BorderRadius.circular(99),
                  child: LinearProgressIndicator(
                    value: matchScore!.clamp(0.0, 1.0),
                    minHeight: 4,
                    backgroundColor:
                        AppConstants.success.withValues(alpha: 0.15),
                    valueColor: const AlwaysStoppedAnimation<Color>(
                      AppConstants.success,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
