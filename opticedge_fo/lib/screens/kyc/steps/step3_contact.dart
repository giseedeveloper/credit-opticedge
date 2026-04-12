import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/kyc/phone_number_field.dart';

class Step3ContactScreen extends ConsumerStatefulWidget {
  const Step3ContactScreen({super.key});
  @override
  ConsumerState<Step3ContactScreen> createState() => _Step3State();
}

class _Step3State extends ConsumerState<Step3ContactScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _phone, _altPhone, _email, _address, _landmark;

  String? _selectedBranch;
  String? _selectedRegion;
  String? _selectedDistrict;
  String _phoneCountry = 'TZ';
  String _altPhoneCountry = 'TZ';

  final _regions = [
    'Dar es Salaam',
    'Arusha',
    'Dodoma',
    'Mwanza',
    'Kilimanjaro',
    'Morogoro',
    'Mbeya',
    'Tanga',
    'Zanzibar',
    'Mtwara',
    'Lindi',
    'Ruvuma',
    'Iringa',
    'Tabora',
    'Shinyanga',
    'Singida',
    'Kagera',
    'Rukwa',
    'Kigoma',
    'Pwani',
  ];

  @override
  void initState() {
    super.initState();
    final s = ref.read(kycProvider);
    _phone = TextEditingController(text: s.phone);
    _altPhone = TextEditingController(text: s.altPhone);
    _email = TextEditingController(text: s.email);
    _address = TextEditingController(text: s.address);
    _landmark = TextEditingController(text: s.landmark);
    _selectedBranch = s.branchId.isNotEmpty ? s.branchId : null;
    _selectedRegion = s.region.isNotEmpty ? s.region : null;
    _selectedDistrict = s.district.isNotEmpty ? s.district : null;
    _phoneCountry = s.phoneCountry;
    _altPhoneCountry = s.altPhoneCountry;
  }

  @override
  void dispose() {
    for (final c in [_phone, _altPhone, _email, _address, _landmark]) {
      c.dispose();
    }
    super.dispose();
  }

  void _save() {
    ref.read(kycProvider.notifier).update((s) => s.copyWith(
          phone: _phone.text.trim(),
          phoneCountry: _phoneCountry,
          altPhone: _altPhone.text.trim(),
          altPhoneCountry: _altPhoneCountry,
          email: _email.text.trim(),
          branchId: _selectedBranch ?? '',
          address: _address.text.trim(),
          landmark: _landmark.text.trim(),
          region: _selectedRegion ?? '',
          district: _selectedDistrict ?? '',
        ));
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedBranch == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Please select a branch'),
          backgroundColor: AppConstants.error));
      return;
    }
    _save();
    await ref.read(kycProvider.notifier).submitStep3();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final branchesAsync = ref.watch(branchesProvider);
    final countriesAsync = ref.watch(phoneCountriesProvider);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader('Contact & Location',
                'Let us confirm the best numbers and branch for this customer.'),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppConstants.primarySurface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: AppConstants.primary.withValues(alpha: 0.12),
                ),
              ),
              child: const Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    Icons.record_voice_over_outlined,
                    color: AppConstants.primary,
                    size: 20,
                  ),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Helpful script: “Nisaidie namba unayotumia mara nyingi zaidi na branch utakayotembelea kwa urahisi. Hii itatusaidia kukutumia updates sahihi.”',
                      style: TextStyle(
                        fontSize: 12,
                        height: 1.5,
                        color: AppConstants.textSecondary,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            countriesAsync.when(
              loading: () => const LinearProgressIndicator(
                color: AppConstants.primary,
              ),
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
                    helperText:
                        'This becomes the main number for payment reminders and follow-up calls.',
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
                    helperText:
                        'Use a trusted backup number if the main line is sometimes unreachable.',
                    onCountryChanged: (value) {
                      if (value == null) {
                        return;
                      }

                      setState(() {
                        _altPhoneCountry = value;
                      });
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            _label('Email Address', optional: true),
            const SizedBox(height: 6),
            TextFormField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                hintText: 'email@example.com',
                prefixIcon: Icon(Icons.email_outlined, size: 18),
              ),
            ),
            const SizedBox(height: 14),
            _label('Branch'),
            const SizedBox(height: 6),
            branchesAsync.when(
              loading: () =>
                  const LinearProgressIndicator(color: AppConstants.primary),
              error: (_, __) => const Text('Failed to load branches',
                  style: TextStyle(color: AppConstants.error)),
              data: (branches) => DropdownButtonFormField<String>(
                isExpanded: true,
                initialValue: _selectedBranch,
                decoration: const InputDecoration(
                  hintText: 'Choose the branch serving this customer',
                  prefixIcon: Icon(Icons.location_city_outlined, size: 18),
                ),
                items: branches
                    .map((b) => DropdownMenuItem(
                          value: b.id,
                          child: Text(b.name,
                              style: const TextStyle(fontSize: 14)),
                        ))
                    .toList(),
                onChanged: (v) => setState(() => _selectedBranch = v),
              ),
            ),
            const SizedBox(height: 14),
            _label('Region', optional: true),
            const SizedBox(height: 6),
            DropdownButtonFormField<String>(
              isExpanded: true,
              initialValue: _selectedRegion,
              decoration: const InputDecoration(
                hintText: 'Select region',
                prefixIcon: Icon(Icons.map_outlined, size: 18),
              ),
              items: _regions
                  .map((r) => DropdownMenuItem(
                        value: r,
                        child: Text(r, style: const TextStyle(fontSize: 14)),
                      ))
                  .toList(),
              onChanged: (v) => setState(() => _selectedRegion = v),
            ),
            const SizedBox(height: 14),
            _label('District', optional: true),
            const SizedBox(height: 6),
            TextFormField(
              initialValue: _selectedDistrict ?? '',
              onChanged: (v) => setState(() => _selectedDistrict = v),
              decoration: const InputDecoration(hintText: 'Enter district'),
            ),
            const SizedBox(height: 14),
            _label('Address', optional: true),
            const SizedBox(height: 6),
            TextFormField(
              controller: _address,
              maxLines: 2,
              decoration:
                  const InputDecoration(hintText: 'Street, plot number...'),
            ),
            const SizedBox(height: 14),
            _label('Landmark', optional: true),
            const SizedBox(height: 6),
            TextFormField(
              controller: _landmark,
              decoration:
                  const InputDecoration(hintText: 'Near mosque, school...'),
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

  Widget _label(String text, {bool optional = false}) => Row(children: [
        Text(text,
            style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: AppConstants.textPrimary)),
        if (optional) ...[
          const SizedBox(width: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
                color: AppConstants.borderLight,
                borderRadius: BorderRadius.circular(4)),
            child: const Text('Optional',
                style: TextStyle(
                    fontSize: 9,
                    color: AppConstants.textHint,
                    fontWeight: FontWeight.w500)),
          ),
        ],
      ]);
}
