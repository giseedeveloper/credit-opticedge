import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../config/constants.dart';
import '../../../config/design_tokens.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/glass_card.dart';

class Step6ConsentScreen extends ConsumerStatefulWidget {
  const Step6ConsentScreen({super.key});
  @override
  ConsumerState<Step6ConsentScreen> createState() => _Step6State();
}

class _Step6State extends ConsumerState<Step6ConsentScreen> {
  Future<void> _next() async {
    final state = ref.read(kycProvider);
    if (!state.termsAccepted ||
        !state.dataConsentAccepted ||
        !state.callConsentAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content:
              Text('Customer must accept all consent items to proceed.'),
          backgroundColor: AppConstants.error,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }
    await ref.read(kycProvider.notifier).submitStep6();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionHeader('Ridhaa', ''),
          const SizedBox(height: 24),

          // Consent items
          _consentItem(
            icon: Icons.gavel_rounded,
            title: 'Terms & Conditions',
            description: 'Makubaliano ya mkopo.',
            value: state.termsAccepted,
            onChanged: (v) => ref
                .read(kycProvider.notifier)
                .update((s) => s.copyWith(termsAccepted: v ?? false)),
          ),
          const SizedBox(height: 12),

          _consentItem(
            icon: Icons.shield_outlined,
            title: 'Data Privacy Consent',
            description: 'Matumizi ya data binafsi.',
            value: state.dataConsentAccepted,
            onChanged: (v) => ref
                .read(kycProvider.notifier)
                .update((s) =>
                    s.copyWith(dataConsentAccepted: v ?? false)),
          ),
          const SizedBox(height: 12),

          _consentItem(
            icon: Icons.phone_in_talk_outlined,
            title: 'Communication Consent',
            description: 'Simu na SMS.',
            value: state.callConsentAccepted,
            onChanged: (v) => ref
                .read(kycProvider.notifier)
                .update((s) =>
                    s.copyWith(callConsentAccepted: v ?? false)),
          ),

          const SizedBox(height: 24),

          // Summary indicator
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: _allAccepted(state)
                  ? const Color(0xFFECFDF5)
                  : AppConstants.borderLight,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: _allAccepted(state)
                    ? AppConstants.success.withValues(alpha: 0.3)
                    : AppConstants.border,
              ),
            ),
            child: Row(
              children: [
                Icon(
                  _allAccepted(state)
                      ? Icons.check_circle_rounded
                      : Icons.info_outline_rounded,
                  color: _allAccepted(state)
                      ? AppConstants.success
                      : AppConstants.textSecondary,
                  size: 20,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    _allAccepted(state)
                        ? 'Tayari.'
                        : 'Chagua vitatu vyote.',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: _allAccepted(state)
                          ? AppConstants.success
                          : AppConstants.textSecondary,
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 32),
          AppButton(
            label: 'Save & Continue',
            width: double.infinity,
            isLoading: state.isSubmitting,
            icon: Icons.arrow_forward_rounded,
            onPressed: _next,
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  bool _allAccepted(KycDraftState s) =>
      s.termsAccepted && s.dataConsentAccepted && s.callConsentAccepted;

  Widget _consentItem({
    required IconData icon,
    required String title,
    required String description,
    required bool value,
    required ValueChanged<bool?> onChanged,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final idleSurface = theme.cardTheme.color ?? theme.colorScheme.surface;
    return GestureDetector(
      onTap: () => onChanged(!value),
      child: GlassCard(
        tint: value ? AppConstants.primarySurface : idleSurface,
        borderRadius: BorderRadius.circular(18),
        borderColor: value
            ? AppConstants.primary
            : (isDark ? DesignTokens.darkBorder : AppConstants.border),
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: value
                    ? AppConstants.primary
                    : (isDark
                        ? DesignTokens.darkBorder.withValues(alpha: 0.35)
                        : AppConstants.borderLight),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon,
                  color: value
                      ? Colors.white
                      : theme.textTheme.bodyMedium?.color,
                  size: 18),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: value
                          ? AppConstants.primary
                          : theme.textTheme.bodyLarge?.color,
                    ),
                  ),
                  if (description.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      description,
                      style: TextStyle(
                        fontSize: 11,
                        color: theme.textTheme.bodyMedium?.color,
                        height: 1.4,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 8),
            Checkbox(
              value: value,
              onChanged: onChanged,
              activeColor: AppConstants.primary,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(4)),
              materialTapTargetSize:
                  MaterialTapTargetSize.shrinkWrap,
            ),
          ],
        ),
      ).animate().fadeIn(duration: 160.ms),
    );
  }

  Widget _sectionHeader(String title, String subtitle) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title,
              style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: Theme.of(context).textTheme.bodyLarge?.color)),
          if (subtitle.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(subtitle,
                style: TextStyle(
                    fontSize: 12,
                    color: Theme.of(context).textTheme.bodyMedium?.color)),
          ],
        ],
      );
}
