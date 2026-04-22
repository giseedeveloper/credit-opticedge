import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/constants.dart';
import '../../../config/design_tokens.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/glass_card.dart';
import '../../../widgets/common/photo_picker_tile.dart';

class Step2IdentityScreen extends ConsumerStatefulWidget {
  const Step2IdentityScreen({super.key});

  @override
  ConsumerState<Step2IdentityScreen> createState() => _Step2State();
}

class _Step2State extends ConsumerState<Step2IdentityScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _first;
  late TextEditingController _middle;
  late TextEditingController _last;
  late TextEditingController _nida;
  late TextEditingController _dob;

  final _idTypes = const ['nida', 'voters_id', 'passport', 'driving_license'];
  final _genders = const ['male', 'female'];

  @override
  void initState() {
    super.initState();
    final state = ref.read(kycProvider);
    _first = TextEditingController(text: state.firstName);
    _middle = TextEditingController(text: state.middleName);
    _last = TextEditingController(text: state.lastName);
    _nida = TextEditingController(text: state.nidaNumber);
    _dob = TextEditingController(text: state.dateOfBirth);
  }

  @override
  void dispose() {
    for (final controller in [_first, _middle, _last, _nida, _dob]) {
      controller.dispose();
    }
    super.dispose();
  }

  void _save() {
    ref.read(kycProvider.notifier).update(
          (state) => state.copyWith(
            firstName: _first.text.trim(),
            middleName: _middle.text.trim(),
            lastName: _last.text.trim(),
            nidaNumber: _nida.text.trim(),
            dateOfBirth: _dob.text.trim(),
          ),
        );
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    _save();
    await ref.read(kycProvider.notifier).submitStep2();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(now.year - 25),
      firstDate: DateTime(1940),
      lastDate: DateTime(now.year - 18),
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
      _dob.text =
          '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final capturedPhotos = [
      state.idFrontPhoto,
      state.idBackPhoto,
      state.headshotPhoto,
      state.clientFoPhoto,
    ].whereType<Object>().length;
    final completedSignals = [
      state.firstName.isNotEmpty,
      state.lastName.isNotEmpty,
      state.nidaNumber.isNotEmpty,
      state.dateOfBirth.isNotEmpty,
      state.idFrontPhoto != null,
      state.idBackPhoto != null,
      state.headshotPhoto != null,
      state.clientFoPhoto != null,
    ].where((item) => item).length;
    final completion = completedSignals / 8;
    final completionPercent = (completion * 100).round();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader('Utambulisho', ''),
            const SizedBox(height: 18),
            _progressHero(
              completionPercent: completionPercent,
              capturedPhotos: capturedPhotos,
              selectedIdType: state.idType,
            ).animate().fadeIn(duration: 260.ms).slideY(begin: 0.08, end: 0),
            const SizedBox(height: 16),
            _card(
              title: '1. Taarifa binafsi',
              subtitle: '',
              child: Column(
                children: [
                  _field(
                    _first,
                    'First Name',
                    icon: Icons.badge_outlined,
                    required: true,
                    hint: 'Enter the customer first name',
                  ),
                  const SizedBox(height: 12),
                  _field(
                    _middle,
                    'Middle Name',
                    icon: Icons.person_outline_rounded,
                    optional: true,
                    hint: 'Optional middle name',
                  ),
                  const SizedBox(height: 12),
                  _field(
                    _last,
                    'Last Name',
                    icon: Icons.badge_outlined,
                    required: true,
                    hint: 'Enter the customer surname',
                  ),
                  const SizedBox(height: 16),
                  _label('Gender'),
                  const SizedBox(height: 8),
                  Row(
                    children: _genders.map((gender) {
                      final selected = state.gender == gender;
                      final isMale = gender == 'male';

                      return Expanded(
                        child: Padding(
                          padding: EdgeInsets.only(
                            right: isMale ? 10 : 0,
                            left: isMale ? 0 : 0,
                          ),
                          child: _optionTile(
                            selected: selected,
                            title: isMale ? 'Male' : 'Female',
                            subtitle: '',
                            icon: isMale
                                ? Icons.male_rounded
                                : Icons.female_rounded,
                            onTap: () {
                              ref.read(kycProvider.notifier).update(
                                    (current) => current.copyWith(
                                      gender: gender,
                                    ),
                                  );
                            },
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 60.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 16),
            _card(
              title: '2. Kitambulisho',
              subtitle: '',
              child: Column(
                children: [
                  GestureDetector(
                    onTap: _pickDate,
                    child: AbsorbPointer(
                      child: TextFormField(
                        controller: _dob,
                        decoration: const InputDecoration(
                          labelText: 'Date of Birth',
                          hintText: 'YYYY-MM-DD',
                          prefixIcon:
                              Icon(Icons.calendar_today_rounded, size: 18),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  _label('ID Type'),
                  const SizedBox(height: 8),
                  GridView.count(
                    crossAxisCount: 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                    childAspectRatio: 1.55,
                    children: _idTypes.map((type) {
                      final selected = state.idType == type;

                      return _optionTile(
                        selected: selected,
                        title: _idTypeTitle(type),
                        subtitle: type == 'nida'
                            ? 'National identity'
                            : type == 'passport'
                                ? 'Travel document'
                                : type == 'voters_id'
                                    ? 'Voter registration'
                                    : 'Driver identity',
                        icon: _idTypeIcon(type),
                        compact: true,
                        onTap: () {
                          ref.read(kycProvider.notifier).update(
                                (current) => current.copyWith(idType: type),
                              );
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 14),
                  _field(
                    _nida,
                    'NIDA / ID Number',
                    icon: Icons.credit_card_outlined,
                    required: true,
                    hint: 'Enter the ID number exactly as shown',
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 120.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 16),
            _card(
              title: '3. Mapicha',
              subtitle: '',
              child: Column(
                children: [
                  _evidenceBanner(capturedPhotos: capturedPhotos),
                  const SizedBox(height: 14),
                  GridView.count(
                    crossAxisCount: 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                    childAspectRatio: 1.05,
                    children: [
                      PhotoPickerTile(
                        label: 'ID Front',
                        required: true,
                        file: state.idFrontPhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .update((current) =>
                                current.copyWith(idFrontPhoto: file)),
                      ),
                      PhotoPickerTile(
                        label: 'ID Back',
                        required: true,
                        file: state.idBackPhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .update((current) =>
                                current.copyWith(idBackPhoto: file)),
                      ),
                      PhotoPickerTile(
                        label: 'Headshot Photo',
                        required: true,
                        file: state.headshotPhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .update((current) =>
                                current.copyWith(headshotPhoto: file)),
                      ),
                      PhotoPickerTile(
                        label: 'Client + FO Photo',
                        file: state.clientFoPhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .update((current) =>
                                current.copyWith(clientFoPhoto: file)),
                      ),
                    ],
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
        if (subtitle.isNotEmpty) ...[
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
      ],
    );
  }

  Widget _progressHero({
    required int completionPercent,
    required int capturedPhotos,
    required String selectedIdType,
  }) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF103454), Color(0xFF1E5987)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppConstants.heroEnd.withValues(alpha: 0.22),
            blurRadius: 22,
            offset: const Offset(0, 12),
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
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(
                  Icons.verified_user_outlined,
                  color: Colors.white,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Maendeleo',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '$completionPercent% · picha $capturedPhotos/4',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.white.withValues(alpha: 0.82),
                      ),
                    ),
                  ],
                ),
              ),
              _heroChip(
                label: _idTypeTitle(selectedIdType),
                icon: Icons.badge_outlined,
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: LinearProgressIndicator(
              value: completionPercent / 100,
              minHeight: 10,
              backgroundColor: Colors.white.withValues(alpha: 0.12),
              valueColor: const AlwaysStoppedAnimation<Color>(
                AppConstants.primaryLight,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _heroChip({
    required String label,
    required IconData icon,
  }) {
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

  Widget _card({
    required String title,
    required String subtitle,
    required Widget child,
  }) {
    return GlassCard.surface(
      context,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: Theme.of(context).textTheme.bodyLarge?.color,
            ),
          ),
          if (subtitle.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: 12,
                height: 1.45,
                color: Theme.of(context).textTheme.bodyMedium?.color,
              ),
            ),
          ],
          const SizedBox(height: 16),
          child,
        ],
      ),
    );
  }

  Widget _evidenceBanner({required int capturedPhotos}) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDark
              ? [
                  DesignTokens.statBlueBgDark,
                  DesignTokens.darkSurfaceElevated,
                ]
              : const [
                  AppConstants.infoSurface,
                  AppConstants.surfaceRaised,
                ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
            color: AppConstants.info.withValues(alpha: isDark ? 0.22 : 0.14)),
      ),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: isDark
                  ? DesignTokens.darkSurface
                  : Colors.white,
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Icons.document_scanner_outlined,
              color: AppConstants.info,
            ),
          )
              .animate(onPlay: (controller) => controller.repeat(reverse: true))
              .shimmer(
                  duration: 1400.ms,
                  color: isDark
                      ? Colors.white24
                      : Colors.white54),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  capturedPhotos == 0
                      ? 'No evidence photo captured yet'
                      : '$capturedPhotos of 4 evidence items ready',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: Theme.of(context).textTheme.bodyLarge?.color,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Mbele, nyuma, sura, picha mteja + FO.',
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: Theme.of(context).textTheme.bodyMedium?.color,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _label(String text, {bool optional = false}) {
    return Row(
      children: [
        Text(
          text,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: AppConstants.textPrimary,
          ),
        ),
        if (optional) ...[
          const SizedBox(width: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: AppConstants.surfaceMuted,
              borderRadius: BorderRadius.circular(999),
            ),
            child: const Text(
              'Optional',
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: AppConstants.textHint,
              ),
            ),
          ),
        ],
      ],
    );
  }

  Widget _field(
    TextEditingController controller,
    String label, {
    required IconData icon,
    String? hint,
    bool required = false,
    bool optional = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(label, optional: optional),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: Icon(icon, size: 18),
          ),
          validator: required
              ? (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Required';
                  }

                  return null;
                }
              : null,
        ),
      ],
    );
  }

  Widget _optionTile({
    required bool selected,
    required String title,
    required String subtitle,
    required IconData icon,
    required VoidCallback onTap,
    bool compact = false,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: AnimatedContainer(
        duration: 220.ms,
        padding: EdgeInsets.all(compact ? 12 : 14),
        decoration: BoxDecoration(
          color: selected
              ? AppConstants.primarySurface
              : AppConstants.surfaceMuted,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: selected ? AppConstants.primary : AppConstants.border,
            width: selected ? 1.4 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: compact ? 40 : 44,
              height: compact ? 40 : 44,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                icon,
                color: selected
                    ? AppConstants.primary
                    : AppConstants.textSecondary,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: AppConstants.textPrimary,
                    ),
                  ),
                  if (subtitle.isNotEmpty) ...[
                    const SizedBox(height: 3),
                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontSize: 11,
                        height: 1.4,
                        color: AppConstants.textSecondary,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            Icon(
              selected
                  ? Icons.check_circle_rounded
                  : Icons.radio_button_unchecked_rounded,
              size: 18,
              color: selected ? AppConstants.primary : AppConstants.textHint,
            ),
          ],
        ),
      ),
    );
  }

  String _idTypeTitle(String value) {
    switch (value) {
      case 'voters_id':
        return 'Voter ID';
      case 'driving_license':
        return 'Driving License';
      case 'passport':
        return 'Passport';
      default:
        return 'NIDA';
    }
  }

  IconData _idTypeIcon(String value) {
    switch (value) {
      case 'voters_id':
        return Icons.how_to_vote_outlined;
      case 'driving_license':
        return Icons.directions_car_outlined;
      case 'passport':
        return Icons.flight_takeoff_outlined;
      default:
        return Icons.credit_card_outlined;
    }
  }
}
