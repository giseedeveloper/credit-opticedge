import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/kyc/kyc_wizard_ui.dart';
import '../../../widgets/common/glass_card.dart';
import '../../../widgets/kyc/phone_number_field.dart';

class Step3ContactScreen extends ConsumerStatefulWidget {
  const Step3ContactScreen({super.key});

  @override
  ConsumerState<Step3ContactScreen> createState() => _Step3State();
}

class _Step3State extends ConsumerState<Step3ContactScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _phone;
  late TextEditingController _altPhone;
  late TextEditingController _email;
  String _phoneCountry = 'TZ';
  String _altPhoneCountry = 'TZ';

  @override
  void initState() {
    super.initState();
    final state = ref.read(kycProvider);
    _phone = TextEditingController(text: state.phone);
    _altPhone = TextEditingController(text: state.altPhone);
    _email = TextEditingController(text: state.email);
    _phoneCountry = state.phoneCountry;
    _altPhoneCountry = state.altPhoneCountry;
  }

  @override
  void dispose() {
    for (final controller in [_phone, _altPhone, _email]) {
      controller.dispose();
    }
    super.dispose();
  }

  void _save() {
    ref.read(kycProvider.notifier).update(
          (state) => state.copyWith(
            phone: _phone.text.trim(),
            phoneCountry: _phoneCountry,
            altPhone: _altPhone.text.trim(),
            altPhoneCountry: _altPhoneCountry,
            email: _email.text.trim(),
            // Branch + location now derived server-side (dealer context).
            branchId: '',
            landmark: '',
            region: '',
            district: '',
          ),
        );
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    _save();
    await ref.read(kycProvider.notifier).submitStep3();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final countriesAsync = ref.watch(phoneCountriesProvider);
    final completionCount = [
      state.phone.isNotEmpty,
    ].where((item) => item).length;

    return SingleChildScrollView(
      padding: KycWizardUi.pagePadding,
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader('Mawasiliano', ''),
            const SizedBox(height: 12),
            _contactHero(completionCount: completionCount)
                .animate()
                .fadeIn(duration: 260.ms)
                .slideY(begin: 0.08, end: 0),
            const SizedBox(height: 10),
            _card(
              title: '1. Simu',
              subtitle: '',
              child: countriesAsync.when(
                loading: () =>
                    const LinearProgressIndicator(color: AppConstants.primary),
                error: (_, __) => const Text(
                  'Failed to load phone countries',
                  style: TextStyle(color: AppConstants.error),
                ),
                data: (countries) => Column(
                  children: [
                    PhoneNumberField(
                      label: 'Primary Phone Number',
                      required: true,
                      controller: _phone,
                      countries: countries,
                      selectedCountry: _phoneCountry,
                      onCountryChanged: (value) {
                        if (value == null) {
                          return;
                        }

                        setState(() {
                          _phoneCountry = value;
                        });
                      },
                    ),
                    const SizedBox(height: 14),
                    PhoneNumberField(
                      label: 'Alternative Phone',
                      controller: _altPhone,
                      countries: countries,
                      selectedCountry: _altPhoneCountry,
                      onCountryChanged: (value) {
                        if (value == null) {
                          return;
                        }

                        setState(() {
                          _altPhoneCountry = value;
                        });
                      },
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'Email Address',
                        hintText: 'email@example.com',
                        prefixIcon: Icon(Icons.email_outlined, size: 18),
                      ),
                    ),
                  ],
                ),
              ),
            ).animate().fadeIn(delay: 60.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 20),
            AppButton(
              compact: true,
              label: 'Save & Continue',
              width: double.infinity,
              isLoading: state.isSubmitting,
              icon: Icons.arrow_forward_rounded,
              onPressed: _next,
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _sectionHeader(String title, String subtitle) => KycSectionHeader(title: title, subtitle: subtitle);

  Widget _contactHero({required int completionCount}) {
    return Container(
      padding: KycWizardUi.cardPadding,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF153D61), Color(0xFF2A6C9B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: KycWizardUi.cardRadius,
        boxShadow: [
          BoxShadow(
            color: AppConstants.heroEnd.withValues(alpha: 0.14),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.phone_in_talk_outlined, color: Colors.white, size: 18),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Maendeleo',
                  style: TextStyle(
                    fontSize: KycWizardUi.sectionTitleSize,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            '$completionCount/1',
            style: TextStyle(
              fontSize: KycWizardUi.labelSize,
              color: Colors.white.withValues(alpha: 0.82),
            ),
          ),
        ],
      ),
    );
  }

  Widget _card({
    required String title,
    required String subtitle,
    required Widget child,
  }) {
    return GlassCard.surface(
      context,
      padding: KycWizardUi.cardPadding,
      borderRadius: KycWizardUi.cardRadius,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: KycWizardUi.cardTitleSize,
              fontWeight: FontWeight.w800,
              color: Theme.of(context).textTheme.bodyLarge?.color,
            ),
          ),
          if (subtitle.isNotEmpty) ...[
            const SizedBox(height: 3),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: KycWizardUi.labelSize,
                height: 1.35,
                color: Theme.of(context).textTheme.bodyMedium?.color,
              ),
            ),
          ],
          const SizedBox(height: KycWizardUi.innerGap),
          child,
        ],
      ),
    );
  }

  // Branch & location UI removed.
}
