import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/photo_picker_tile.dart';

class Step4IncomeScreen extends ConsumerStatefulWidget {
  const Step4IncomeScreen({super.key});
  @override
  ConsumerState<Step4IncomeScreen> createState() => _Step4State();
}

class _Step4State extends ConsumerState<Step4IncomeScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _occupation,
      _income,
      _duration;

  final _occupationChips = [
    'Salaried',
    'Self Employed',
    'Driver',
    'Farmer',
    'Teacher',
    'Other',
  ];

  @override
  void initState() {
    super.initState();
    final s = ref.read(kycProvider);
    _occupation = TextEditingController(text: s.occupation);
    _income = TextEditingController(text: s.monthlyIncome);
    _duration = TextEditingController(text: s.durationAtWork);
  }

  @override
  void dispose() {
    for (final c in [
      _occupation,
      _income,
      _duration
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  void _save() {
    ref.read(kycProvider.notifier).update((s) => s.copyWith(
          occupation: _occupation.text.trim(),
          monthlyIncome: _income.text.trim(),
          durationAtWork: _duration.text.trim(),
        ));
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) return;
    _save();
    await ref.read(kycProvider.notifier).submitStep4();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader('Kazi na kipato', ''),
            const SizedBox(height: 20),
            _label('Occupation Type', optional: true),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: _occupationChips.map((chip) {
                final selected = state.occupation == chip;
                return ChoiceChip(
                  label: Text(chip),
                  selected: selected,
                  onSelected: (_) {
                    ref
                        .read(kycProvider.notifier)
                        .update((s) => s.copyWith(occupation: chip));
                    _occupation.text = chip;
                  },
                  selectedColor: AppConstants.primarySurface,
                  labelStyle: TextStyle(
                    fontSize: 12,
                    color: selected
                        ? AppConstants.primary
                        : AppConstants.textSecondary,
                    fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                    side: BorderSide(
                        color: selected
                            ? AppConstants.primary
                            : AppConstants.border),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 12),
            _field(_occupation, 'Or type occupation manually', optional: true),
            const SizedBox(height: 14),
            _field(_income, 'Monthly Income (TZS)',
                required: true, keyboard: TextInputType.number),
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppConstants.surfaceMuted,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppConstants.border),
              ),
              child: Row(
                children: [
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Politically Exposed Person (PEP)',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: AppConstants.textPrimary,
                          ),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'Washa kama mteja ni PEP.',
                          style: TextStyle(
                            fontSize: 12,
                            height: 1.4,
                            color: AppConstants.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Switch(
                    value: state.isPep,
                    onChanged: (v) => ref
                        .read(kycProvider.notifier)
                        .update((s) => s.copyWith(isPep: v)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            _field(_duration, 'Duration at Work',
                optional: true, hint: 'e.g. 2 years'),
            const SizedBox(height: 24),
            _sectionHeader('Picha ya biashara', ''),
            const SizedBox(height: 12),
            SizedBox(
              width: 120,
              height: 100,
              child: PhotoPickerTile(
                label: 'Business Photo',
                file: state.businessPhoto,
                onPicked: (f) => ref
                    .read(kycProvider.notifier)
                    .update((s) => s.copyWith(businessPhoto: f)),
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
          if (subtitle.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(subtitle,
                style: const TextStyle(
                    fontSize: 12, color: AppConstants.textSecondary)),
          ],
        ],
      );

  Widget _label(String text, {bool optional = false}) => Wrap(
        spacing: 6,
        runSpacing: 4,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          Text(text,
              style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                  color: AppConstants.textPrimary)),
          if (optional)
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
      );

  Widget _field(TextEditingController ctrl, String label,
          {bool required = false,
          bool optional = false,
          String? hint,
          TextInputType keyboard = TextInputType.text}) =>
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _label(label, optional: optional || !required),
        const SizedBox(height: 6),
        TextFormField(
          controller: ctrl,
          keyboardType: keyboard,
          decoration: InputDecoration(hintText: hint ?? label),
          validator: required
              ? (v) => (v == null || v.trim().isEmpty) ? 'Required' : null
              : null,
        ),
      ]);
}
