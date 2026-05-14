import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../config/constants.dart';
import '../../config/design_tokens.dart';

/// Full-width entry for live face verification (modern KYC pattern — not a small grid tile).
class FaceVerificationHeroCard extends StatelessWidget {
  final String customerId;
  final String? idFrontUrl;
  final bool verified;
  final double? matchScore;
  final Future<void> Function()? onScannerClosed;

  const FaceVerificationHeroCard({
    super.key,
    required this.customerId,
    this.idFrontUrl,
    this.verified = false,
    this.matchScore,
    this.onScannerClosed,
  });

  bool get _hasIdFront => idFrontUrl != null && idFrontUrl!.trim().isNotEmpty;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: _hasIdFront
            ? () async {
                await context.push(
                  '/kyc/face-scanner/$customerId?id_front_url=${Uri.encodeComponent(idFrontUrl ?? '')}',
                );
                if (context.mounted) {
                  await onScannerClosed?.call();
                }
              }
            : null,
        borderRadius: BorderRadius.circular(22),
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: verified
                  ? [
                      AppConstants.success.withValues(alpha: isDark ? 0.22 : 0.12),
                      DesignTokens.statBlueBgDark.withValues(alpha: 0.35),
                    ]
                  : _hasIdFront
                      ? [
                          AppConstants.primary.withValues(alpha: isDark ? 0.28 : 0.14),
                          const Color(0xFF103454).withValues(alpha: isDark ? 0.9 : 0.85),
                        ]
                      : [
                          AppConstants.surfaceMuted,
                          AppConstants.surfaceMuted,
                        ],
            ),
            border: Border.all(
              color: verified
                  ? AppConstants.success.withValues(alpha: 0.55)
                  : _hasIdFront
                      ? AppConstants.primary.withValues(alpha: 0.45)
                      : AppConstants.border,
              width: 1.4,
            ),
            boxShadow: _hasIdFront && !verified
                ? [
                    BoxShadow(
                      color: AppConstants.primary.withValues(alpha: 0.18),
                      blurRadius: 22,
                      offset: const Offset(0, 10),
                    ),
                  ]
                : null,
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(18, 16, 18, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.2),
                        ),
                      ),
                      child: Icon(
                        verified
                            ? Icons.verified_rounded
                            : Icons.face_retouching_natural_rounded,
                        color: verified ? AppConstants.success : Colors.white,
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            verified
                                ? 'Uthibitisho wa uso — umekamilika'
                                : 'Skani ya uso (live)',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: Colors.white,
                              letterSpacing: -0.2,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            verified
                                ? 'Alama ya ulinganisho: ${((matchScore ?? 0) * 100).round()}%'
                                : _hasIdFront
                                    ? 'Blink → geuza kichwa → picha kiotomatiki. Hakikisha mwanga mzuri.'
                                    : 'Pakia picha ya mbele ya kitambulisho kwanza, kisha rudi hapa.',
                            style: TextStyle(
                              fontSize: 13,
                              height: 1.35,
                              fontWeight: FontWeight.w600,
                              color: Colors.white.withValues(alpha: 0.86),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Icon(
                      verified
                          ? Icons.check_circle_rounded
                          : _hasIdFront
                              ? Icons.arrow_forward_ios_rounded
                              : Icons.lock_outline_rounded,
                      color: Colors.white.withValues(
                        alpha: _hasIdFront ? 0.95 : 0.45,
                      ),
                      size: 18,
                    ),
                  ],
                ),
                if (verified && matchScore != null) ...[
                  const SizedBox(height: 12),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(99),
                    child: LinearProgressIndicator(
                      value: matchScore!.clamp(0.0, 1.0),
                      minHeight: 6,
                      backgroundColor:
                          Colors.white.withValues(alpha: 0.12),
                      valueColor: const AlwaysStoppedAnimation<Color>(
                        AppConstants.success,
                      ),
                    ),
                  ),
                ],
                if (_hasIdFront && !verified) ...[
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      _miniChip(Icons.visibility_rounded, 'Blink'),
                      const SizedBox(width: 8),
                      _miniChip(Icons.sync_alt_rounded, 'Geuza'),
                      const SizedBox(width: 8),
                      _miniChip(Icons.auto_awesome_rounded, 'Auto'),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _miniChip(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.14)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: Colors.white.withValues(alpha: 0.9)),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Colors.white.withValues(alpha: 0.88),
            ),
          ),
        ],
      ),
    );
  }
}
