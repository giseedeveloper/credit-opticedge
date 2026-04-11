import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/photo_picker_tile.dart';

class Step2IdentityScreen extends ConsumerStatefulWidget {
  const Step2IdentityScreen({super.key});
  @override
  ConsumerState<Step2IdentityScreen> createState() => _Step2State();
}

class _Step2State extends ConsumerState<Step2IdentityScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _first, _middle, _last, _nida, _dob;

  final _idTypes = ['nida', 'voters_id', 'passport', 'driving_license'];
  final _genders = ['male', 'female'];

  @override
  void initState() {
    super.initState();
    final s = ref.read(kycProvider);
    _first = TextEditingController(text: s.firstName);
    _middle = TextEditingController(text: s.middleName);
    _last = TextEditingController(text: s.lastName);
    _nida = TextEditingController(text: s.nidaNumber);
    _dob = TextEditingController(text: s.dateOfBirth);
  }

  @override
  void dispose() {
    for (final c in [_first, _middle, _last, _nida, _dob]) {
      c.dispose();
    }
    super.dispose();
  }

  void _save() {
    ref.read(kycProvider.notifier).update((s) => s.copyWith(
          firstName: _first.text.trim(),
          middleName: _middle.text.trim(),
          lastName: _last.text.trim(),
          nidaNumber: _nida.text.trim(),
          dateOfBirth: _dob.text.trim(),
        ));
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) return;
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
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader('Personal Identity', 'Customer\'s personal details and ID'),
            const SizedBox(height: 20),

            _field(_first, 'First Name', required: true),
            const SizedBox(height: 14),
            _field(_middle, 'Middle Name', optional: true),
            const SizedBox(height: 14),
            _field(_last, 'Last Name', required: true),
            const SizedBox(height: 14),

            _label('Gender'),
            const SizedBox(height: 8),
            Row(
              children: _genders.map((g) {
                final selected = state.gender == g;
                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: GestureDetector(
                      onTap: () => ref
                          .read(kycProvider.notifier)
                          .update((s) => s.copyWith(gender: g)),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        decoration: BoxDecoration(
                          color: selected
                              ? AppConstants.primarySurface
                              : AppConstants.borderLight,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: selected
                                ? AppConstants.primary
                                : AppConstants.border,
                            width: selected ? 1.5 : 1,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              g == 'male'
                                  ? Icons.male_rounded
                                  : Icons.female_rounded,
                              color: selected
                                  ? AppConstants.primary
                                  : AppConstants.textSecondary,
                              size: 18,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              g[0].toUpperCase() + g.substring(1),
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: selected
                                    ? FontWeight.w600
                                    : FontWeight.w400,
                                color: selected
                                    ? AppConstants.primary
                                    : AppConstants.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 14),

            _label('Date of Birth', optional: true),
            const SizedBox(height: 6),
            GestureDetector(
              onTap: _pickDate,
              child: AbsorbPointer(
                child: TextFormField(
                  controller: _dob,
                  decoration: const InputDecoration(
                    hintText: 'YYYY-MM-DD',
                    suffixIcon: Icon(Icons.calendar_today_rounded,
                        size: 18, color: AppConstants.textSecondary),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 14),

            _label('ID Type'),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _idTypes.map((t) {
                final selected = state.idType == t;
                final label = t.replaceAll('_', ' ').toUpperCase();
                return ChoiceChip(
                  label: Text(label),
                  selected: selected,
                  onSelected: (_) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(idType: t)),
                  selectedColor: AppConstants.primarySurface,
                  labelStyle: TextStyle(
                    fontSize: 11,
                    color: selected
                        ? AppConstants.primary
                        : AppConstants.textSecondary,
                    fontWeight:
                        selected ? FontWeight.w600 : FontWeight.w400,
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
            const SizedBox(height: 14),

            _field(_nida, 'NIDA / ID Number', required: true),
            const SizedBox(height: 24),

            _sectionHeader('Photos', 'Clear photos required for verification'),
            const SizedBox(height: 12),
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
              childAspectRatio: 1.5,
              children: [
                PhotoPickerTile(
                  label: 'ID Front',
                  required: true,
                  file: state.idFrontPhoto,
                  onPicked: (f) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(idFrontPhoto: f)),
                ),
                PhotoPickerTile(
                  label: 'ID Back',
                  required: true,
                  file: state.idBackPhoto,
                  onPicked: (f) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(idBackPhoto: f)),
                ),
                PhotoPickerTile(
                  label: 'Headshot Photo',
                  required: true,
                  file: state.headshotPhoto,
                  onPicked: (f) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(headshotPhoto: f)),
                ),
                PhotoPickerTile(
                  label: 'Client + FO Photo',
                  file: state.clientFoPhoto,
                  onPicked: (f) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(clientFoPhoto: f)),
                ),
              ],
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
            padding:
                const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
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

  Widget _field(TextEditingController ctrl, String label,
      {bool required = false, bool optional = false}) =>
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _label(label, optional: optional || !required),
        const SizedBox(height: 6),
        TextFormField(
          controller: ctrl,
          decoration: InputDecoration(hintText: label),
          validator: required
              ? (v) => (v == null || v.trim().isEmpty) ? 'Required' : null
              : null,
        ),
      ]);
}
