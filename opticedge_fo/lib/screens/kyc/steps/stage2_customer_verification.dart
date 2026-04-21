import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/app_icon_assets.dart';
import '../../../config/constants.dart';
import '../../../core/models/dashboard_model.dart';
import '../../../core/models/kyc_flow_model.dart';
import '../../../core/providers/auth_provider.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/app_color_icon.dart';
import '../../../widgets/common/photo_picker_tile.dart';
import '../../../widgets/kyc/phone_number_field.dart';

class Stage2CustomerVerificationScreen extends ConsumerStatefulWidget {
  const Stage2CustomerVerificationScreen({super.key});

  @override
  ConsumerState<Stage2CustomerVerificationScreen> createState() =>
      _Stage2CustomerVerificationScreenState();
}

class _Stage2CustomerVerificationScreenState
    extends ConsumerState<Stage2CustomerVerificationScreen> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _firstName;
  late final TextEditingController _middleName;
  late final TextEditingController _lastName;
  late final TextEditingController _dateOfBirth;
  late final TextEditingController _nidaNumber;
  late final TextEditingController _phone;
  late final TextEditingController _altPhone;
  late final TextEditingController _email;
  late final TextEditingController _region;
  late final TextEditingController _district;
  late final TextEditingController _address;
  late final TextEditingController _landmark;
  late final TextEditingController _occupation;
  late final TextEditingController _employer;
  late final TextEditingController _workLocation;
  late final TextEditingController _monthlyIncome;
  late final TextEditingController _monthlyExpenses;
  late final TextEditingController _durationAtWork;
  late final TextEditingController _nokName;
  late final TextEditingController _nokPhone;
  late final TextEditingController _nok2Name;
  late final TextEditingController _nok2Phone;

  String _gender = 'male';
  String _idType = 'nida';
  String _phoneCountry = 'TZ';
  String _altPhoneCountry = 'TZ';
  String _selectedBranch = '';
  String _incomePaymentCycle = 'monthly';
  String _nokRelationship = '';
  String _nokPhoneCountry = 'TZ';
  String _nok2Relationship = '';
  String _nok2PhoneCountry = 'TZ';
  bool _termsAccepted = false;
  bool _dataConsentAccepted = false;
  bool _callConsentAccepted = false;

  static const _idTypes = [
    ('nida', 'NIDA'),
    ('passport', 'Passport'),
    ('driving_license', 'Driving'),
    ('voter_card', 'Voter'),
  ];

  static const _genders = [
    ('male', 'Male', Icons.male_rounded),
    ('female', 'Female', Icons.female_rounded),
    ('other', 'Other', Icons.person_outline_rounded),
  ];

  static const _cycles = [
    ('weekly', 'Weekly'),
    ('biweekly', 'Bi-weekly'),
    ('monthly', 'Monthly'),
    ('irregular', 'Irregular'),
  ];

  static const _relationships = [
    'Spouse',
    'Parent',
    'Sibling',
    'Child',
    'Relative',
    'Friend',
    'Colleague',
    'Guardian',
  ];

  @override
  void initState() {
    super.initState();
    final state = ref.read(kycProvider);
    final signedInBranchId = ref.read(authProvider).user?.branch?.id ?? '';

    _firstName = TextEditingController(text: state.firstName);
    _middleName = TextEditingController(text: state.middleName);
    _lastName = TextEditingController(text: state.lastName);
    _dateOfBirth = TextEditingController(text: state.dateOfBirth);
    _nidaNumber = TextEditingController(text: state.nidaNumber);
    _phone = TextEditingController(text: state.phone);
    _altPhone = TextEditingController(text: state.altPhone);
    _email = TextEditingController(text: state.email);
    _region = TextEditingController(text: state.region);
    _district = TextEditingController(text: state.district);
    _address = TextEditingController(text: state.address);
    _landmark = TextEditingController(text: state.landmark);
    _occupation = TextEditingController(text: state.occupation);
    _employer = TextEditingController(text: state.employer);
    _workLocation = TextEditingController(text: state.workLocation);
    _monthlyIncome = TextEditingController(text: state.monthlyIncome);
    _monthlyExpenses = TextEditingController(text: state.monthlyExpenses);
    _durationAtWork = TextEditingController(text: state.durationAtWork);
    _nokName = TextEditingController(text: state.nokName);
    _nokPhone = TextEditingController(text: state.nokPhone);
    _nok2Name = TextEditingController(text: state.nok2Name);
    _nok2Phone = TextEditingController(text: state.nok2Phone);

    _gender = state.gender.isNotEmpty ? state.gender : 'male';
    _idType = state.idType.isNotEmpty ? state.idType : 'nida';
    _phoneCountry = state.phoneCountry;
    _altPhoneCountry = state.altPhoneCountry;
    _selectedBranch =
        state.branchId.isNotEmpty ? state.branchId : signedInBranchId;
    _incomePaymentCycle = state.incomePaymentCycle.isNotEmpty
        ? state.incomePaymentCycle
        : 'monthly';
    _nokRelationship = state.nokRelationship;
    _nokPhoneCountry = state.nokPhoneCountry;
    _nok2Relationship = state.nok2Relationship;
    _nok2PhoneCountry = state.nok2PhoneCountry;
    _termsAccepted = state.termsAccepted;
    _dataConsentAccepted = state.dataConsentAccepted;
    _callConsentAccepted = state.callConsentAccepted;

    for (final controller in _trackedControllers) {
      controller.addListener(_refreshProgress);
    }
  }

  List<TextEditingController> get _trackedControllers => [
        _firstName,
        _middleName,
        _lastName,
        _dateOfBirth,
        _nidaNumber,
        _phone,
        _altPhone,
        _email,
        _region,
        _district,
        _address,
        _landmark,
        _occupation,
        _employer,
        _workLocation,
        _monthlyIncome,
        _monthlyExpenses,
        _durationAtWork,
        _nokName,
        _nokPhone,
        _nok2Name,
        _nok2Phone,
      ];

  @override
  void dispose() {
    for (final controller in _trackedControllers) {
      controller.removeListener(_refreshProgress);
      controller.dispose();
    }
    super.dispose();
  }

  void _refreshProgress() {
    if (mounted) {
      setState(() {});
    }
  }

  void _save() {
    ref.read(kycProvider.notifier).update(
          (state) => state.copyWith(
            firstName: _firstName.text.trim(),
            middleName: _middleName.text.trim(),
            lastName: _lastName.text.trim(),
            gender: _gender,
            dateOfBirth: _dateOfBirth.text.trim(),
            nidaNumber: _nidaNumber.text.trim(),
            idType: _idType,
            phone: _phone.text.trim(),
            phoneCountry: _phoneCountry,
            altPhone: _altPhone.text.trim(),
            altPhoneCountry: _altPhoneCountry,
            email: _email.text.trim(),
            branchId: _selectedBranch,
            address: _address.text.trim(),
            landmark: _landmark.text.trim(),
            region: _region.text.trim(),
            district: _district.text.trim(),
            occupation: _occupation.text.trim(),
            employer: _employer.text.trim(),
            workLocation: _workLocation.text.trim(),
            monthlyIncome: _monthlyIncome.text.trim(),
            monthlyExpenses: _monthlyExpenses.text.trim(),
            incomePaymentCycle: _incomePaymentCycle,
            durationAtWork: _durationAtWork.text.trim(),
            nokName: _nokName.text.trim(),
            nokPhone: _nokPhone.text.trim(),
            nokPhoneCountry: _nokPhoneCountry,
            nokRelationship: _nokRelationship,
            nok2Name: _nok2Name.text.trim(),
            nok2Phone: _nok2Phone.text.trim(),
            nok2PhoneCountry: _nok2PhoneCountry,
            nok2Relationship: _nok2Relationship,
            termsAccepted: _termsAccepted,
            dataConsentAccepted: _dataConsentAccepted,
            callConsentAccepted: _callConsentAccepted,
          ),
        );
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final state = ref.read(kycProvider);
    final missingPhotos = [
      if (state.idFrontPhoto == null) 'ID front',
      if (state.idBackPhoto == null) 'ID back',
      if (state.headshotPhoto == null) 'Headshot',
    ];
    if (missingPhotos.isNotEmpty) {
      _showError('Attach ${missingPhotos.join(', ')} photo before continuing.');
      return;
    }

    if (_selectedBranch.isEmpty) {
      _showError('Select the branch serving this customer.');
      return;
    }

    if (!_termsAccepted || !_dataConsentAccepted || !_callConsentAccepted) {
      _showError('Customer must accept all consent items to continue.');
      return;
    }

    _save();
    await ref.read(kycProvider.notifier).submitStage2();
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppConstants.error,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final initialYear = now.year - 25;
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(initialYear),
      firstDate: DateTime(1940),
      lastDate: DateTime(now.year - 18, now.month, now.day),
      builder: (_, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: const ColorScheme.light(
            primary: AppConstants.primary,
            onPrimary: Colors.white,
          ),
        ),
        child: child!,
      ),
    );

    if (picked != null) {
      _dateOfBirth.text =
          '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final countriesAsync = ref.watch(phoneCountriesProvider);
    final branchesAsync = ref.watch(branchesProvider);
    final completion = _completionProgress(state);

    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 26),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      child: Form(
        key: _formKey,
        autovalidateMode: AutovalidateMode.onUserInteraction,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _stageHero(completion)
                .animate()
                .fadeIn(duration: 260.ms)
                .slideY(begin: 0.05, end: 0),
            const SizedBox(height: 14),
            _sectionCard(
              icon: AppIconAssets.identity,
              title: 'Identity & Evidence',
              subtitle:
                  'Majina, NIDA na picha za uthibitisho ziwe sharp kabla ya kuendelea.',
              child: Column(
                children: [
                  _textField(
                    controller: _firstName,
                    label: 'First name',
                    icon: Icons.badge_outlined,
                    validator: _required('First name'),
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _middleName,
                    label: 'Middle name',
                    icon: Icons.person_outline_rounded,
                    optional: true,
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _lastName,
                    label: 'Last name',
                    icon: Icons.badge_outlined,
                    validator: _required('Last name'),
                  ),
                  const SizedBox(height: 14),
                  _choiceWrap(
                    label: 'Gender',
                    children: _genders.map((item) {
                      return _choicePill(
                        label: item.$2,
                        icon: item.$3,
                        selected: _gender == item.$1,
                        onTap: () => setState(() => _gender = item.$1),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 14),
                  GestureDetector(
                    onTap: _pickDate,
                    child: AbsorbPointer(
                      child: _textField(
                        controller: _dateOfBirth,
                        label: 'Date of birth',
                        hint: 'YYYY-MM-DD',
                        icon: Icons.calendar_today_rounded,
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  _choiceWrap(
                    label: 'ID type',
                    children: _idTypes.map((item) {
                      return _choicePill(
                        label: item.$2,
                        selected: _idType == item.$1,
                        onTap: () => setState(() => _idType = item.$1),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _nidaNumber,
                    label: 'NIDA / ID number',
                    hint: '20 digits for NIDA',
                    icon: Icons.credit_card_rounded,
                    keyboardType: TextInputType.number,
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                      LengthLimitingTextInputFormatter(20),
                    ],
                    validator: (value) {
                      final text = value?.trim() ?? '';
                      if (text.isEmpty) {
                        return 'NIDA / ID number is required';
                      }
                      if (text.length != 20) {
                        return 'NIDA number must be exactly 20 digits';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 14),
                  _photoGrid([
                    PhotoPickerTile(
                      label: 'ID Front',
                      required: true,
                      file: state.idFrontPhoto,
                      onPicked: (file) => ref
                          .read(kycProvider.notifier)
                          .setPhoto('id_front', file),
                    ),
                    PhotoPickerTile(
                      label: 'ID Back',
                      required: true,
                      file: state.idBackPhoto,
                      onPicked: (file) => ref
                          .read(kycProvider.notifier)
                          .setPhoto('id_back', file),
                    ),
                    PhotoPickerTile(
                      label: 'Headshot',
                      required: true,
                      file: state.headshotPhoto,
                      onPicked: (file) => ref
                          .read(kycProvider.notifier)
                          .setPhoto('headshot', file),
                    ),
                    PhotoPickerTile(
                      label: 'Client + FO',
                      file: state.clientFoPhoto,
                      onPicked: (file) => ref
                          .read(kycProvider.notifier)
                          .setPhoto('client_fo', file),
                    ),
                  ]),
                ],
              ),
            ).animate().fadeIn(delay: 50.ms).slideY(begin: 0.05, end: 0),
            const SizedBox(height: 14),
            _sectionCard(
              icon: AppIconAssets.contact,
              title: 'Contact & Branch',
              subtitle:
                  'Namba, branch na location ndio msingi wa reminders na follow-up.',
              child: Column(
                children: [
                  countriesAsync.when(
                    loading: () => const LinearProgressIndicator(
                      color: AppConstants.primary,
                    ),
                    error: (_, __) => const _InlineError(
                      message: 'Failed to load phone countries.',
                    ),
                    data: (countries) => _phoneFields(countries),
                  ),
                  const SizedBox(height: 14),
                  branchesAsync.when(
                    loading: () => const LinearProgressIndicator(
                      color: AppConstants.primary,
                    ),
                    error: (_, __) => const _InlineError(
                      message: 'Failed to load branches.',
                    ),
                    data: (branches) => _branchField(branches),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _textField(
                          controller: _region,
                          label: 'Region',
                          icon: Icons.map_outlined,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _textField(
                          controller: _district,
                          label: 'District',
                          icon: Icons.location_city_outlined,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _address,
                    label: 'Address',
                    icon: Icons.home_work_outlined,
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _landmark,
                    label: 'Landmark',
                    icon: Icons.place_outlined,
                    optional: true,
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 90.ms).slideY(begin: 0.05, end: 0),
            const SizedBox(height: 14),
            _sectionCard(
              icon: AppIconAssets.income,
              title: 'Income & Ability To Repay',
              subtitle:
                  'Capture repayment capacity kwa lugha nyepesi na ushahidi pale ulipo.',
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: _textField(
                          controller: _occupation,
                          label: 'Occupation',
                          icon: Icons.work_outline_rounded,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _textField(
                          controller: _employer,
                          label: 'Employer',
                          icon: Icons.business_center_outlined,
                          optional: true,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _workLocation,
                    label: 'Work location',
                    icon: Icons.storefront_outlined,
                    optional: true,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _moneyField(
                          controller: _monthlyIncome,
                          label: 'Monthly income',
                          required: true,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _moneyField(
                          controller: _monthlyExpenses,
                          label: 'Expenses',
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  _choiceWrap(
                    label: 'Income cycle',
                    children: _cycles.map((item) {
                      return _choicePill(
                        label: item.$2,
                        selected: _incomePaymentCycle == item.$1,
                        onTap: () => setState(
                          () => _incomePaymentCycle = item.$1,
                        ),
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _durationAtWork,
                    label: 'Duration at work/business',
                    hint: 'Example: 2 years',
                    icon: Icons.timeline_rounded,
                    optional: true,
                  ),
                  const SizedBox(height: 14),
                  _photoGrid([
                    PhotoPickerTile(
                      label: 'Business Photo',
                      file: state.businessPhoto,
                      onPicked: (file) => ref
                          .read(kycProvider.notifier)
                          .setPhoto('business', file),
                    ),
                  ]),
                ],
              ),
            ).animate().fadeIn(delay: 130.ms).slideY(begin: 0.05, end: 0),
            const SizedBox(height: 14),
            _sectionCard(
              icon: AppIconAssets.nok,
              title: 'Next Of Kin',
              subtitle:
                  'Chagua mtu wa kuaminika anayepatikana kirahisi endapo kuna follow-up.',
              child: countriesAsync.when(
                loading: () => const LinearProgressIndicator(
                  color: AppConstants.primary,
                ),
                error: (_, __) => const _InlineError(
                  message: 'Failed to load phone countries.',
                ),
                data: (countries) => Column(
                  children: [
                    _textField(
                      controller: _nokName,
                      label: 'Primary NOK name',
                      icon: Icons.person_pin_circle_outlined,
                      validator: _required('NOK name'),
                    ),
                    const SizedBox(height: 12),
                    PhoneNumberField(
                      label: 'Primary NOK phone',
                      required: true,
                      controller: _nokPhone,
                      countries: countries,
                      selectedCountry: _nokPhoneCountry,
                      onCountryChanged: (value) {
                        if (value != null) {
                          setState(() => _nokPhoneCountry = value);
                        }
                      },
                    ),
                    const SizedBox(height: 14),
                    _relationshipField(
                      label: 'Relationship',
                      value: _nokRelationship,
                      required: true,
                      onChanged: (value) {
                        setState(() => _nokRelationship = value ?? '');
                      },
                    ),
                    const SizedBox(height: 16),
                    _miniDivider('Optional backup NOK'),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _nok2Name,
                      label: 'Second NOK name',
                      icon: Icons.person_add_alt_rounded,
                      optional: true,
                    ),
                    const SizedBox(height: 12),
                    PhoneNumberField(
                      label: 'Second NOK phone',
                      controller: _nok2Phone,
                      countries: countries,
                      selectedCountry: _nok2PhoneCountry,
                      onCountryChanged: (value) {
                        if (value != null) {
                          setState(() => _nok2PhoneCountry = value);
                        }
                      },
                    ),
                    const SizedBox(height: 14),
                    _relationshipField(
                      label: 'Second NOK relationship',
                      value: _nok2Relationship,
                      onChanged: (value) {
                        setState(() => _nok2Relationship = value ?? '');
                      },
                    ),
                  ],
                ),
              ),
            ).animate().fadeIn(delay: 170.ms).slideY(begin: 0.05, end: 0),
            const SizedBox(height: 14),
            _sectionCard(
              icon: AppIconAssets.consent,
              title: 'Consent Confirmation',
              subtitle:
                  'Soma kwa customer kwa uwazi, kisha thibitisha kila consent kabla ya payment.',
              child: Column(
                children: [
                  _consentTile(
                    title: 'Terms & Conditions',
                    description:
                        'Customer ameelewa loan terms, repayment schedule na masharti muhimu.',
                    value: _termsAccepted,
                    onChanged: (value) =>
                        setState(() => _termsAccepted = value),
                  ),
                  const SizedBox(height: 10),
                  _consentTile(
                    title: 'Data Privacy Consent',
                    description:
                        'Customer anakubali taarifa zake kutumika kwa KYC, credit assessment na service follow-up.',
                    value: _dataConsentAccepted,
                    onChanged: (value) =>
                        setState(() => _dataConsentAccepted = value),
                  ),
                  const SizedBox(height: 10),
                  _consentTile(
                    title: 'Communication Consent',
                    description:
                        'Customer anakubali kupokea calls na SMS za reminders, payment na updates.',
                    value: _callConsentAccepted,
                    onChanged: (value) =>
                        setState(() => _callConsentAccepted = value),
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 210.ms).slideY(begin: 0.05, end: 0),
            const SizedBox(height: 18),
            AppButton(
              label: 'Save Customer & Continue',
              width: double.infinity,
              icon: Icons.arrow_forward_rounded,
              isLoading: state.isSubmitting,
              onPressed: _next,
            ),
            const SizedBox(height: 10),
            const Text(
              'Next: payment prompt, agreement preview, signatures, and device handover.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 12,
                height: 1.45,
                fontWeight: FontWeight.w600,
                color: AppConstants.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _stageHero(double progress) {
    final percent = (progress * 100).round();

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF10263F), Color(0xFF174D74)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0E2740).withValues(alpha: 0.18),
            blurRadius: 26,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: const Center(
                  child: AppColorIcon(
                    assetName: AppIconAssets.checklist,
                    size: 28,
                    semanticsLabel: 'Customer verification packet',
                  ),
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Stage 2 packet',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: AppConstants.kycWizardAccentLine,
                        letterSpacing: 1.4,
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Customer & Verification',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.45,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '$percent%',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w900,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 7,
              backgroundColor: Colors.white.withValues(alpha: 0.12),
              valueColor: const AlwaysStoppedAnimation<Color>(
                AppConstants.kycWizardAccentLine,
              ),
            ),
          ),
          const SizedBox(height: 14),
          const Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _HeroChip(label: 'Identity'),
              _HeroChip(label: 'Contact'),
              _HeroChip(label: 'Income'),
              _HeroChip(label: 'NOK'),
              _HeroChip(label: 'Consent'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _sectionCard({
    required String icon,
    required String title,
    required String subtitle,
    required Widget child,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: AppConstants.borderLight),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF132033).withValues(alpha: 0.05),
            blurRadius: 24,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: AppConstants.primarySurface,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Center(
                  child: AppColorIcon(
                    assetName: icon,
                    size: 24,
                    semanticsLabel: title,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.25,
                        color: AppConstants.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontSize: 12,
                        height: 1.45,
                        fontWeight: FontWeight.w600,
                        color: AppConstants.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          child,
        ],
      ),
    );
  }

  Widget _phoneFields(List<PhoneCountryOption> countries) {
    return Column(
      children: [
        PhoneNumberField(
          label: 'Primary phone',
          required: true,
          controller: _phone,
          countries: countries,
          selectedCountry: _phoneCountry,
          helperText: 'Used for payment prompts, reminders, and follow-up.',
          onCountryChanged: (value) {
            if (value != null) {
              setState(() => _phoneCountry = value);
            }
          },
        ),
        const SizedBox(height: 14),
        PhoneNumberField(
          label: 'Alternative phone',
          controller: _altPhone,
          countries: countries,
          selectedCountry: _altPhoneCountry,
          onCountryChanged: (value) {
            if (value != null) {
              setState(() => _altPhoneCountry = value);
            }
          },
        ),
        const SizedBox(height: 12),
        _textField(
          controller: _email,
          label: 'Email',
          hint: 'customer@example.com',
          icon: Icons.email_outlined,
          keyboardType: TextInputType.emailAddress,
          optional: true,
          validator: (value) {
            final text = value?.trim() ?? '';
            if (text.isEmpty) {
              return null;
            }
            if (!text.contains('@')) {
              return 'Enter a valid email';
            }
            return null;
          },
        ),
      ],
    );
  }

  Widget _branchField(List<BranchModel> branches) {
    final selectedValue = branches.any((branch) => branch.id == _selectedBranch)
        ? _selectedBranch
        : null;

    return DropdownButtonFormField<String>(
      initialValue: selectedValue,
      isExpanded: true,
      decoration: const InputDecoration(
        labelText: 'Serving branch',
        prefixIcon: Icon(Icons.storefront_outlined, size: 18),
      ),
      items: branches
          .map(
            (branch) => DropdownMenuItem<String>(
              value: branch.id,
              child: Text(branch.name),
            ),
          )
          .toList(),
      validator: (value) {
        if ((value ?? _selectedBranch).isEmpty) {
          return 'Branch is required';
        }
        return null;
      },
      onChanged: (value) {
        setState(() => _selectedBranch = value ?? '');
      },
    );
  }

  Widget _relationshipField({
    required String label,
    required String value,
    required ValueChanged<String?> onChanged,
    bool required = false,
  }) {
    final currentValue = _relationships
            .map((relationship) => relationship.toLowerCase())
            .contains(value)
        ? value
        : null;

    return DropdownButtonFormField<String>(
      initialValue: currentValue,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.link_rounded, size: 18),
      ),
      items: _relationships
          .map(
            (relationship) => DropdownMenuItem<String>(
              value: relationship.toLowerCase(),
              child: Text(relationship),
            ),
          )
          .toList(),
      validator: required
          ? (selected) {
              if ((selected ?? value).isEmpty) {
                return '$label is required';
              }
              return null;
            }
          : null,
      onChanged: onChanged,
    );
  }

  Widget _moneyField({
    required TextEditingController controller,
    required String label,
    bool required = false,
  }) {
    return _textField(
      controller: controller,
      label: label,
      icon: Icons.payments_outlined,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      inputFormatters: [
        FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
      ],
      validator: required ? _required(label) : null,
    );
  }

  Widget _textField({
    required TextEditingController controller,
    required String label,
    IconData? icon,
    String? hint,
    bool optional = false,
    TextInputType? keyboardType,
    List<TextInputFormatter>? inputFormatters,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      textInputAction: TextInputAction.next,
      decoration: InputDecoration(
        labelText: optional ? '$label (optional)' : label,
        hintText: hint,
        prefixIcon: icon == null ? null : Icon(icon, size: 18),
      ),
      validator: validator,
    );
  }

  Widget _choiceWrap({
    required String label,
    required List<Widget> children,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: AppConstants.textPrimary,
          ),
        ),
        const SizedBox(height: 8),
        Wrap(spacing: 8, runSpacing: 8, children: children),
      ],
    );
  }

  Widget _choicePill({
    required String label,
    required bool selected,
    required VoidCallback onTap,
    IconData? icon,
  }) {
    return FilterChip(
      selected: selected,
      label: Text(label),
      avatar: icon == null
          ? null
          : Icon(
              icon,
              size: 17,
              color: selected ? AppConstants.primary : AppConstants.textHint,
            ),
      onSelected: (_) => onTap(),
      selectedColor: AppConstants.primarySurface,
      checkmarkColor: AppConstants.primary,
      side: BorderSide(
        color: selected
            ? AppConstants.primary.withValues(alpha: 0.34)
            : AppConstants.border,
      ),
      labelStyle: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w800,
        color: selected ? AppConstants.primaryDark : AppConstants.textSecondary,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
    );
  }

  Widget _photoGrid(List<Widget> tiles) {
    return LayoutBuilder(
      builder: (context, constraints) {
        const columns = 2;
        const spacing = 10.0;
        final width =
            (constraints.maxWidth - spacing * (columns - 1)) / columns;

        return Wrap(
          spacing: spacing,
          runSpacing: spacing,
          children: tiles
              .map(
                (tile) => SizedBox(
                  width: tiles.length == 1 ? constraints.maxWidth : width,
                  child: tile,
                ),
              )
              .toList(),
        );
      },
    );
  }

  Widget _consentTile({
    required String title,
    required String description,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return InkWell(
      borderRadius: BorderRadius.circular(20),
      onTap: () => onChanged(!value),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 220),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color:
              value ? AppConstants.successSurface : AppConstants.surfaceMuted,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: value
                ? AppConstants.success.withValues(alpha: 0.34)
                : AppConstants.border,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 220),
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: value ? AppConstants.success : Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: value ? AppConstants.success : AppConstants.border,
                ),
              ),
              child: Icon(
                value ? Icons.check_rounded : Icons.shield_outlined,
                color: value ? Colors.white : AppConstants.textHint,
                size: 18,
              ),
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
                      fontWeight: FontWeight.w900,
                      color: value
                          ? AppConstants.success
                          : AppConstants.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: const TextStyle(
                      fontSize: 11.5,
                      height: 1.42,
                      fontWeight: FontWeight.w600,
                      color: AppConstants.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            Checkbox(
              value: value,
              activeColor: AppConstants.success,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(6),
              ),
              onChanged: (checked) => onChanged(checked ?? false),
            ),
          ],
        ),
      ),
    );
  }

  Widget _miniDivider(String label) {
    return Row(
      children: [
        const Expanded(child: Divider(color: AppConstants.borderLight)),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10),
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w800,
              color: AppConstants.textHint,
            ),
          ),
        ),
        const Expanded(child: Divider(color: AppConstants.borderLight)),
      ],
    );
  }

  String? Function(String?) _required(String label) {
    return (value) {
      if (value == null || value.trim().isEmpty) {
        return '$label is required';
      }
      return null;
    };
  }

  double _completionProgress(KycDraftState state) {
    final checks = [
      _firstName.text.trim().isNotEmpty,
      _lastName.text.trim().isNotEmpty,
      _nidaNumber.text.trim().length == 20,
      state.idFrontPhoto != null,
      state.idBackPhoto != null,
      state.headshotPhoto != null,
      _phone.text.trim().isNotEmpty,
      _selectedBranch.isNotEmpty,
      _monthlyIncome.text.trim().isNotEmpty,
      _nokName.text.trim().isNotEmpty,
      _nokPhone.text.trim().isNotEmpty,
      _nokRelationship.isNotEmpty,
      _termsAccepted,
      _dataConsentAccepted,
      _callConsentAccepted,
    ];

    return checks.where((item) => item).length / checks.length;
  }
}

class _HeroChip extends StatelessWidget {
  final String label;

  const _HeroChip({required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w800,
          color: Colors.white,
        ),
      ),
    );
  }
}

class _InlineError extends StatelessWidget {
  final String message;

  const _InlineError({required this.message});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppConstants.errorSurface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppConstants.error.withValues(alpha: 0.18)),
      ),
      child: Text(
        message,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: AppConstants.error,
        ),
      ),
    );
  }
}
