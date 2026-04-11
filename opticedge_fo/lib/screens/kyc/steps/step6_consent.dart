import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';

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
          _sectionHeader('Customer Consent',
              'Read each item carefully to the customer and confirm their acceptance'),
          const SizedBox(height: 24),

          // Consent items
          _consentItem(
            icon: Icons.gavel_rounded,
            title: 'Terms & Conditions',
            description:
                'The customer agrees to the loan terms, repayment schedule, and all applicable conditions as explained by the field officer.',
            value: state.termsAccepted,
            onChanged: (v) => ref
                .read(kycProvider.notifier)
                .update((s) => s.copyWith(termsAccepted: v ?? false)),
          ),
          const SizedBox(height: 12),

          _consentItem(
            icon: Icons.shield_outlined,
            title: 'Data Privacy Consent',
            description:
                'The customer consents to collection, storage and processing of their personal data for credit assessment, KYC verification, and related services.',
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
            description:
                'The customer agrees to be contacted via phone calls and SMS for loan updates, reminders, and communication related to their application.',
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
                    ? AppConstants.success.withOpacity(0.3)
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
                        ? 'All consent items accepted. Ready to proceed.'
                        : 'Customer must accept all 3 consent items above.',
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
    return GestureDetector(
      onTap: () => onChanged(!value),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: value
              ? AppConstants.primarySurface
              : AppConstants.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: value
                ? AppConstants.primary.withOpacity(0.4)
                : AppConstants.border,
            width: value ? 1.5 : 1,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: value
                    ? AppConstants.primary
                    : AppConstants.borderLight,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon,
                  color: value ? Colors.white : AppConstants.textSecondary,
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
                          : AppConstants.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppConstants.textSecondary,
                      height: 1.4,
                    ),
                  ),
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
      ),
    );
  }

  Widget _sectionHeader(String title, String subtitle) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title,
              style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: AppConstants.textPrimary)),
          const SizedBox(height: 2),
          Text(subtitle,
              style: const TextStyle(
                  fontSize: 12, color: AppConstants.textSecondary)),
        ],
      );
}
