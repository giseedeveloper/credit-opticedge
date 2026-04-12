import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/constants.dart';
import '../../../core/providers/auth_provider.dart';
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
  late TextEditingController _phone;
  late TextEditingController _altPhone;
  late TextEditingController _email;
  late TextEditingController _address;
  late TextEditingController _landmark;

  String? _selectedBranch;
  String? _selectedRegion;
  String? _selectedDistrict;
  String _phoneCountry = 'TZ';
  String _altPhoneCountry = 'TZ';

  final _regions = const [
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
    final state = ref.read(kycProvider);
    _phone = TextEditingController(text: state.phone);
    _altPhone = TextEditingController(text: state.altPhone);
    _email = TextEditingController(text: state.email);
    _address = TextEditingController(text: state.address);
    _landmark = TextEditingController(text: state.landmark);
    final signedInBranchId = ref.read(authProvider).user?.branch?.id;
    _selectedBranch = state.branchId.isNotEmpty
        ? state.branchId
        : (signedInBranchId?.isNotEmpty == true ? signedInBranchId : null);
    _selectedRegion = state.region.isNotEmpty ? state.region : null;
    _selectedDistrict = state.district.isNotEmpty ? state.district : null;
    _phoneCountry = state.phoneCountry;
    _altPhoneCountry = state.altPhoneCountry;
  }

  @override
  void dispose() {
    for (final controller in [_phone, _altPhone, _email, _address, _landmark]) {
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
            branchId: _selectedBranch ?? '',
            address: _address.text.trim(),
            landmark: _landmark.text.trim(),
            region: _selectedRegion ?? '',
            district: _selectedDistrict ?? '',
          ),
        );
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (_selectedBranch == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select a branch'),
          backgroundColor: AppConstants.error,
        ),
      );
      return;
    }

    _save();
    await ref.read(kycProvider.notifier).submitStep3();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final signedInUser = ref.watch(authProvider).user;
    final branchesAsync = ref.watch(branchesProvider);
    final countriesAsync = ref.watch(phoneCountriesProvider);
    final completionCount = [
      state.phone.isNotEmpty,
      state.branchId.isNotEmpty,
      state.region.isNotEmpty,
      state.district.isNotEmpty,
      state.address.isNotEmpty,
    ].where((item) => item).length;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader(
              'Contact & Location',
              'Hakikisha tunabaki na namba sahihi, branch sahihi, na maelezo ya eneo yanayomfanya customer afuatiliwe kwa urahisi.',
            ),
            const SizedBox(height: 18),
            _contactHero(completionCount: completionCount)
                .animate()
                .fadeIn(duration: 260.ms)
                .slideY(begin: 0.08, end: 0),
            const SizedBox(height: 16),
            _scriptCard(),
            const SizedBox(height: 16),
            _card(
              title: '1. Best phone numbers to reach the customer',
              subtitle:
                  'Primary number hutumika kwa reminders, payment prompt, na follow-up za baadae.',
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
                      helperText:
                          'This becomes the main number for reminders, payment prompts, and status updates.',
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
                          'Use a trusted backup line if the main line is sometimes switched off.',
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
                    const SizedBox(height: 12),
                    const Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        _MetaChip(
                          icon: Icons.notifications_active_outlined,
                          label: 'Payment reminders',
                        ),
                        _MetaChip(
                          icon: Icons.call_outlined,
                          label: 'Follow-up calls',
                        ),
                        _MetaChip(
                          icon: Icons.sms_outlined,
                          label: 'SMS updates',
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ).animate().fadeIn(delay: 60.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 16),
            _card(
              title: '2. Service branch and customer routing',
              subtitle:
                  'Branch unayochagua hapa ndiyo itabeba ownership ya file, collections, na visits za baadae.',
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  branchesAsync.when(
                    loading: () => const LinearProgressIndicator(
                      color: AppConstants.primary,
                    ),
                    error: (_, __) => const Text(
                      'Failed to load branches',
                      style: TextStyle(color: AppConstants.error),
                    ),
                    data: (branches) => DropdownButtonFormField<String>(
                      isExpanded: true,
                      initialValue: _selectedBranch,
                      decoration: const InputDecoration(
                        labelText: 'Branch',
                        hintText: 'Choose the branch serving this customer',
                        prefixIcon:
                            Icon(Icons.location_city_outlined, size: 18),
                      ),
                      items: branches
                          .map(
                            (branch) => DropdownMenuItem<String>(
                              value: branch.id,
                              child: Text(branch.name),
                            ),
                          )
                          .toList(),
                      onChanged: (value) {
                        setState(() {
                          _selectedBranch = value;
                        });
                      },
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [
                          AppConstants.warningSurface,
                          AppConstants.surfaceRaised
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(
                        color: AppConstants.warning.withValues(alpha: 0.12),
                      ),
                    ),
                    child: const Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(
                          Icons.account_tree_outlined,
                          color: AppConstants.warning,
                          size: 18,
                        ),
                        SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            'Tip: chagua branch ambayo customer ataitumia kwa urahisi. Hii ndiyo branch itakayohusishwa na registration, review, na asset release ya baadae.',
                            style: TextStyle(
                              fontSize: 12,
                              height: 1.45,
                              color: AppConstants.textSecondary,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (signedInUser?.branch?.name != null) ...[
                    const SizedBox(height: 12),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppConstants.surfaceMuted,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: AppConstants.border),
                      ),
                      child: Text(
                        'Signed-in branch default: ${signedInUser!.branch!.name}. If the handset came from vendor stock, the system will keep the linked store context automatically.',
                        style: const TextStyle(
                          fontSize: 12,
                          height: 1.45,
                          color: AppConstants.textSecondary,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ).animate().fadeIn(delay: 120.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 16),
            _card(
              title: '3. Location context',
              subtitle:
                  'Hapa tunakusanya maelezo ya mahali pa kumpata customer bila kumchosha kwa maswali mengi sana.',
              child: Column(
                children: [
                  DropdownButtonFormField<String>(
                    isExpanded: true,
                    initialValue: _selectedRegion,
                    decoration: const InputDecoration(
                      labelText: 'Region',
                      hintText: 'Select region',
                      prefixIcon: Icon(Icons.map_outlined, size: 18),
                    ),
                    items: _regions
                        .map(
                          (region) => DropdownMenuItem<String>(
                            value: region,
                            child: Text(region),
                          ),
                        )
                        .toList(),
                    onChanged: (value) {
                      setState(() {
                        _selectedRegion = value;
                      });
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    initialValue: _selectedDistrict ?? '',
                    onChanged: (value) {
                      setState(() {
                        _selectedDistrict = value;
                      });
                    },
                    decoration: const InputDecoration(
                      labelText: 'District',
                      hintText: 'Enter district',
                      prefixIcon: Icon(Icons.place_outlined, size: 18),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _address,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Address',
                      hintText: 'Street, plot number, or area details',
                      prefixIcon: Icon(Icons.home_work_outlined, size: 18),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _landmark,
                    decoration: const InputDecoration(
                      labelText: 'Landmark',
                      hintText: 'Near mosque, school, petrol station...',
                      prefixIcon: Icon(Icons.pin_drop_outlined, size: 18),
                    ),
                  ),
                  const SizedBox(height: 12),
                  _locationPreviewCard(
                    branchLabel: _selectedBranch,
                    region: _selectedRegion,
                    district: _selectedDistrict,
                    address: _address.text.trim(),
                    landmark: _landmark.text.trim(),
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 180.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 30),
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

  Widget _sectionHeader(String title, String subtitle) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: AppConstants.textPrimary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: const TextStyle(
            fontSize: 12,
            height: 1.5,
            color: AppConstants.textSecondary,
          ),
        ),
      ],
    );
  }

  Widget _contactHero({required int completionCount}) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF153D61), Color(0xFF2A6C9B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppConstants.heroEnd.withValues(alpha: 0.18),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.phone_in_talk_outlined, color: Colors.white, size: 20),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Contact routing health',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            '$completionCount of 5 contact signals ready',
            style: TextStyle(
              fontSize: 12,
              color: Colors.white.withValues(alpha: 0.82),
            ),
          ),
          const SizedBox(height: 14),
          const Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _HeroTag(
                icon: Icons.phone_android_outlined,
                label: 'Primary line',
              ),
              _HeroTag(
                icon: Icons.account_balance_outlined,
                label: 'Branch ownership',
              ),
              _HeroTag(
                icon: Icons.place_outlined,
                label: 'Field location',
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _scriptCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppConstants.primarySurface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.primary.withValues(alpha: 0.12)),
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
              'Helpful script: “Nisaidie namba unayotumia mara nyingi zaidi na branch utakayotembelea kwa urahisi. Hii itatusaidia kukutumia updates sahihi bila kukupigia namba zisizo sahihi.”',
              style: TextStyle(
                fontSize: 12,
                height: 1.5,
                color: AppConstants.textSecondary,
              ),
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
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppConstants.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppConstants.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: const TextStyle(
              fontSize: 12,
              height: 1.45,
              color: AppConstants.textSecondary,
            ),
          ),
          const SizedBox(height: 16),
          child,
        ],
      ),
    );
  }

  Widget _locationPreviewCard({
    required String? branchLabel,
    required String? region,
    required String? district,
    required String address,
    required String landmark,
  }) {
    final rows = <String>[
      if (branchLabel != null && branchLabel.isNotEmpty) 'Branch linked',
      if (region != null && region.isNotEmpty) region,
      if (district != null && district.isNotEmpty) district,
      if (address.isNotEmpty) address,
      if (landmark.isNotEmpty) 'Near $landmark',
    ];

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppConstants.surfaceMuted,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.map_rounded,
              color: AppConstants.info,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Location summary',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: AppConstants.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  rows.isEmpty
                      ? 'As you fill in branch and location details, a quick summary will appear here for FO confidence.'
                      : rows.join(' • '),
                  style: const TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _HeroTag extends StatelessWidget {
  const _HeroTag({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.14)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: Colors.white),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

class _MetaChip extends StatelessWidget {
  const _MetaChip({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: AppConstants.surfaceMuted,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppConstants.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppConstants.primary),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: AppConstants.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}
