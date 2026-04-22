import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/glass_card.dart';
import '../../../widgets/kyc/phone_number_field.dart';

class Step5NokScreen extends ConsumerStatefulWidget {
  const Step5NokScreen({super.key});
  @override
  ConsumerState<Step5NokScreen> createState() => _Step5State();
}

class _Step5State extends ConsumerState<Step5NokScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nokName, _nokPhone, _nokRel;
  late TextEditingController _nok2Name, _nok2Phone, _nok2Rel;
  bool _showNok2 = false;
  String _nokPhoneCountry = 'TZ';
  String _nok2PhoneCountry = 'TZ';

  final _relationships = [
    'Spouse',
    'Parent',
    'Sibling',
    'Friend',
    'Relative',
    'Other',
  ];

  @override
  void initState() {
    super.initState();
    final s = ref.read(kycProvider);
    _nokName = TextEditingController(text: s.nokName);
    _nokPhone = TextEditingController(text: s.nokPhone);
    _nokRel = TextEditingController(text: s.nokRelationship);
    _nok2Name = TextEditingController(text: s.nok2Name);
    _nok2Phone = TextEditingController(text: s.nok2Phone);
    _nok2Rel = TextEditingController(text: s.nok2Relationship);
    _showNok2 = s.nok2Name.isNotEmpty;
    _nokPhoneCountry = s.nokPhoneCountry;
    _nok2PhoneCountry = s.nok2PhoneCountry;
  }

  @override
  void dispose() {
    for (final c in [
      _nokName,
      _nokPhone,
      _nokRel,
      _nok2Name,
      _nok2Phone,
      _nok2Rel
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  void _save() {
    ref.read(kycProvider.notifier).update((s) => s.copyWith(
          nokName: _nokName.text.trim(),
          nokPhone: _nokPhone.text.trim(),
          nokPhoneCountry: _nokPhoneCountry,
          nokRelationship: _nokRel.text.trim(),
          nok2Name: _nok2Name.text.trim(),
          nok2Phone: _nok2Phone.text.trim(),
          nok2PhoneCountry: _nok2PhoneCountry,
          nok2Relationship: _nok2Rel.text.trim(),
        ));
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) return;
    _save();
    await ref.read(kycProvider.notifier).submitStep5();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final countriesAsync = ref.watch(phoneCountriesProvider);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader('Mtu wa karibu', ''),
            const SizedBox(height: 20),
            countriesAsync.when(
              loading: () => const LinearProgressIndicator(
                color: AppConstants.primary,
              ),
              error: (_, __) => const Text(
                'Failed to load phone countries',
                style: TextStyle(color: AppConstants.error),
              ),
              data: (countries) => _nokCard(
                title: 'Primary Next of Kin',
                nameCtrl: _nokName,
                phoneCtrl: _nokPhone,
                relCtrl: _nokRel,
                required: true,
                countries: countries,
                selectedCountry: _nokPhoneCountry,
                onCountryChanged: (value) {
                  if (value == null) {
                    return;
                  }

                  setState(() {
                    _nokPhoneCountry = value;
                  });
                },
              ),
            ),
            const SizedBox(height: 20),
            if (!_showNok2)
              OutlinedButton.icon(
                onPressed: () => setState(() => _showNok2 = true),
                icon: const Icon(Icons.add_circle_outline_rounded, size: 18),
                label: const Text('Add Second Next of Kin'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppConstants.primary,
                  side: const BorderSide(color: AppConstants.primary, width: 1),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                  padding:
                      const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                ),
              ),
            if (_showNok2) ...[
              countriesAsync.when(
                loading: () => const LinearProgressIndicator(
                  color: AppConstants.primary,
                ),
                error: (_, __) => const Text(
                  'Failed to load phone countries',
                  style: TextStyle(color: AppConstants.error),
                ),
                data: (countries) => _nokCard(
                  title: 'Second Next of Kin',
                  nameCtrl: _nok2Name,
                  phoneCtrl: _nok2Phone,
                  relCtrl: _nok2Rel,
                  required: false,
                  countries: countries,
                  selectedCountry: _nok2PhoneCountry,
                  onCountryChanged: (value) {
                    if (value == null) {
                      return;
                    }

                    setState(() {
                      _nok2PhoneCountry = value;
                    });
                  },
                  onRemove: () {
                    setState(() => _showNok2 = false);
                    _nok2Name.clear();
                    _nok2Phone.clear();
                    _nok2Rel.clear();
                  },
                ),
              ),
            ],
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

  Widget _nokCard({
    required String title,
    required TextEditingController nameCtrl,
    required TextEditingController phoneCtrl,
    required TextEditingController relCtrl,
    required List countries,
    required String selectedCountry,
    required ValueChanged<String?> onCountryChanged,
    required bool required,
    VoidCallback? onRemove,
  }) {
    return GlassCard(
      tint: Colors.white,
      borderRadius: BorderRadius.circular(18),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Row(children: [
                  Container(
                    width: 30,
                    height: 30,
                    decoration: BoxDecoration(
                      color: AppConstants.primarySurface,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.person_outline_rounded,
                        color: AppConstants.primary, size: 16),
                  ),
                  const SizedBox(width: 8),
                  Flexible(
                    child: Text(title,
                        style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: AppConstants.textPrimary),
                        overflow: TextOverflow.ellipsis),
                  ),
                ]),
              ),
              if (onRemove != null)
                GestureDetector(
                  onTap: onRemove,
                  child: const Icon(Icons.remove_circle_outline_rounded,
                      color: AppConstants.error, size: 20),
                ),
            ],
          ),
          const SizedBox(height: 14),
          _field(nameCtrl, 'Full Name', required: required),
          const SizedBox(height: 12),
          PhoneNumberField(
            label: 'Phone Number',
            required: required,
            controller: phoneCtrl,
            countries: countries.cast(),
            selectedCountry: selectedCountry,
            helperText: null,
            onCountryChanged: onCountryChanged,
          ),
          const SizedBox(height: 12),
          _label('Relationship', optional: !required),
          const SizedBox(height: 8),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: _relationships.map((r) {
              final selected = relCtrl.text == r;
              return GestureDetector(
                onTap: () {
                  setState(() => relCtrl.text = r);
                },
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: selected
                        ? AppConstants.primarySurface
                        : AppConstants.borderLight,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color:
                          selected ? AppConstants.primary : AppConstants.border,
                      width: selected ? 1.5 : 1,
                    ),
                  ),
                  child: Text(
                    r,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
                      color: selected
                          ? AppConstants.primary
                          : AppConstants.textSecondary,
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 10),
          TextFormField(
            controller: relCtrl,
            decoration:
                const InputDecoration(hintText: 'Or type relationship...'),
          ),
        ],
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

  Widget _field(TextEditingController ctrl, String label,
          {bool required = false,
          TextInputType keyboard = TextInputType.text}) =>
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _label(label, optional: !required),
        const SizedBox(height: 6),
        TextFormField(
          controller: ctrl,
          keyboardType: keyboard,
          decoration: InputDecoration(hintText: label),
          validator: required
              ? (v) => (v == null || v.trim().isEmpty) ? 'Required' : null
              : null,
        ),
      ]);
}
