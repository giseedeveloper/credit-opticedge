import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../common/glass_card.dart';
import '../common/app_button.dart';

class PreHandoverChecklistPanel extends StatelessWidget {
  const PreHandoverChecklistPanel({
    super.key,
    required this.isComplete,
    required this.deviceUnboxed,
    required this.deviceBootVerified,
    required this.mdmLockConfirmed,
    required this.isSubmitting,
    required this.onUnboxedChanged,
    required this.onBootChanged,
    required this.onMdmChanged,
    required this.onSubmit,
    this.mdmLockStatus,
    this.inventoryMdmId,
  });

  final bool isComplete;
  final bool deviceUnboxed;
  final bool deviceBootVerified;
  final bool mdmLockConfirmed;
  final bool isSubmitting;
  final ValueChanged<bool> onUnboxedChanged;
  final ValueChanged<bool> onBootChanged;
  final ValueChanged<bool> onMdmChanged;
  final VoidCallback onSubmit;
  final String? mdmLockStatus;
  final String? inventoryMdmId;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    if (isComplete) {
      return GlassCard.surface(
        context,
        borderRadius: BorderRadius.circular(18),
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: AppConstants.success.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.fact_check_outlined,
                color: AppConstants.success,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Pre-handover checklist complete',
                    style: GoogleFonts.plusJakartaSans(
                      fontWeight: FontWeight.w800,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Unbox, boot, na MDM lock zimethibitishwa.${mdmLockStatus != null ? ' MDM: $mdmLockStatus.' : ''}',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      height: 1.35,
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.62)
                          : AppConstants.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(18),
      padding: EdgeInsets.zero,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
            decoration: BoxDecoration(
              color: DesignTokens.statAmber.withValues(
                alpha: isDark ? 0.14 : 0.08,
              ),
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(18),
              ),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.inventory_2_outlined,
                  color: DesignTokens.statAmber,
                  size: 22,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Pre-handover checklist',
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w800,
                          fontSize: 14,
                          color: DesignTokens.statAmber,
                        ),
                      ),
                      Text(
                        'Thibitisha hatua hizi mbele ya mteja kabla ya release.',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 12,
                          height: 1.35,
                          color: isDark
                              ? Colors.white.withValues(alpha: 0.62)
                              : AppConstants.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 4, 8, 12),
            child: Column(
              children: [
                _CheckStep(
                  value: deviceUnboxed,
                  enabled: !isSubmitting,
                  title: 'Device imefunguliwa (unbox)',
                  subtitle: 'Fungua box mbele ya mteja na thibitisha kifaa.',
                  color: AppConstants.primary,
                  onChanged: onUnboxedChanged,
                ),
                _CheckStep(
                  value: deviceBootVerified,
                  enabled: !isSubmitting,
                  title: 'Device imeboot vizuri',
                  subtitle: 'Washa simu na angalia screen ya kawaida.',
                  color: DesignTokens.statBlue,
                  onChanged: onBootChanged,
                ),
                _CheckStep(
                  value: mdmLockConfirmed,
                  enabled: !isSubmitting,
                  title: 'MDM lock imewekwa',
                  subtitle: (inventoryMdmId?.isNotEmpty ?? false)
                      ? 'MDM ID: $inventoryMdmId'
                      : 'Hakuna MDM ID kwenye stock — lock itaruka mpaka MDM iunganishwe.',
                  color: DesignTokens.statViolet,
                  onChanged: onMdmChanged,
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
            child: AppButton(
              label: 'Thibitisha pre-handover checklist',
              icon: Icons.verified_outlined,
              isLoading: isSubmitting,
              outlined: true,
              width: double.infinity,
              onPressed: isSubmitting ? null : onSubmit,
            ),
          ),
        ],
      ),
    );
  }
}

class _CheckStep extends StatelessWidget {
  const _CheckStep({
    required this.value,
    required this.enabled,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.onChanged,
  });

  final bool value;
  final bool enabled;
  final String title;
  final String subtitle;
  final Color color;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: enabled ? () => onChanged(!value) : null,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                width: 26,
                height: 26,
                decoration: BoxDecoration(
                  color: value
                      ? color
                      : color.withValues(alpha: isDark ? 0.12 : 0.08),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: value
                        ? color
                        : color.withValues(alpha: 0.35),
                  ),
                ),
                child: value
                    ? const Icon(Icons.check_rounded, size: 18, color: Colors.white)
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        height: 1.35,
                        color: isDark
                            ? Colors.white.withValues(alpha: 0.55)
                            : AppConstants.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
