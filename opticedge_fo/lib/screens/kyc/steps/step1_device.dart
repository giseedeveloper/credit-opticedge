import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/photo_picker_tile.dart';

class Step1DeviceScreen extends ConsumerStatefulWidget {
  const Step1DeviceScreen({super.key});
  @override
  ConsumerState<Step1DeviceScreen> createState() => _Step1State();
}

class _Step1State extends ConsumerState<Step1DeviceScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _specs, _imei1, _imei2, _serial, _cash, _deposit;

  final _repaymentOptions = ['daily', 'weekly', 'bi-weekly', 'monthly'];

  @override
  void initState() {
    super.initState();
    final s = ref.read(kycProvider);
    _specs = TextEditingController(text: s.deviceSpecs);
    _imei1 = TextEditingController(text: s.imeiNumber);
    _imei2 = TextEditingController(text: s.imei2);
    _serial = TextEditingController(text: s.serialNumber);
    _cash = TextEditingController(text: s.cashPrice);
    _deposit = TextEditingController(text: s.depositAmount);
  }

  @override
  void dispose() {
    for (final c in [_specs, _imei1, _imei2, _serial, _cash, _deposit]) {
      c.dispose();
    }
    super.dispose();
  }

  void _save() {
    ref.read(kycProvider.notifier).update((s) => s.copyWith(
          deviceSpecs: _specs.text.trim(),
          imeiNumber: _imei1.text.trim(),
          imei2: _imei2.text.trim(),
          serialNumber: _serial.text.trim(),
          cashPrice: _cash.text.trim(),
          depositAmount: _deposit.text.trim(),
        ));
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) return;
    _save();
    await ref.read(kycProvider.notifier).submitStep1();
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
            _sectionHeader('Device Information',
                'Enter device specs and IMEI numbers'),
            const SizedBox(height: 20),

            _field(_specs, 'Device Specs / Model',
                hint: 'e.g. Samsung Galaxy A15 4G 128GB', required: true),
            const SizedBox(height: 14),
            _field(_imei1, 'IMEI Number 1',
                hint: '15-digit IMEI', required: true,
                keyboard: TextInputType.number),
            const SizedBox(height: 14),
            _field(_imei2, 'IMEI Number 2',
                hint: 'Optional second IMEI',
                keyboard: TextInputType.number),
            const SizedBox(height: 14),
            _field(_serial, 'Serial Number', hint: 'Device serial number'),
            const SizedBox(height: 14),
            _field(_cash, 'Cash Price (TZS)',
                required: true, keyboard: TextInputType.number),
            const SizedBox(height: 14),
            _field(_deposit, 'Deposit Amount (TZS)',
                required: true, keyboard: TextInputType.number),
            const SizedBox(height: 14),

            _label('Preferred Repayment Cycle'),
            const SizedBox(height: 8),
            _repaymentChips(state),

            const SizedBox(height: 24),
            _sectionHeader('Device Photos', 'Capture clear photos of the device'),
            const SizedBox(height: 12),
            GridView.count(
              crossAxisCount: 3,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
              childAspectRatio: 1,
              children: [
                PhotoPickerTile(
                  label: 'IMEI Photo',
                  required: false,
                  file: state.imeiPhoto,
                  onPicked: (f) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(imeiPhoto: f)),
                ),
                PhotoPickerTile(
                  label: 'Box Photo',
                  required: false,
                  file: state.deviceBoxPhoto,
                  onPicked: (f) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(deviceBoxPhoto: f)),
                ),
                PhotoPickerTile(
                  label: 'Device Photo',
                  required: false,
                  file: state.devicePhoto,
                  onPicked: (f) => ref
                      .read(kycProvider.notifier)
                      .update((s) => s.copyWith(devicePhoto: f)),
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

  Widget _repaymentChips(KycDraftState state) {
    return Wrap(
      spacing: 8,
      children: _repaymentOptions.map((opt) {
        final selected = state.preferredRepayment == opt;
        return ChoiceChip(
          label: Text(opt[0].toUpperCase() + opt.substring(1)),
          selected: selected,
          onSelected: (_) => ref
              .read(kycProvider.notifier)
              .update((s) => s.copyWith(preferredRepayment: opt)),
          selectedColor: AppConstants.primarySurface,
          labelStyle: TextStyle(
            color: selected ? AppConstants.primary : AppConstants.textSecondary,
            fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
            fontSize: 12,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
            side: BorderSide(
                color: selected ? AppConstants.primary : AppConstants.border),
          ),
        );
      }).toList(),
    );
  }

  Widget _sectionHeader(String title, String subtitle) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(title,
          style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: AppConstants.textPrimary)),
      const SizedBox(height: 2),
      Text(subtitle,
          style: const TextStyle(
              fontSize: 12, color: AppConstants.textSecondary)),
    ]);
  }

  Widget _label(String text, {bool optional = false}) {
    return Row(children: [
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
  }

  Widget _field(TextEditingController ctrl, String label,
      {String? hint,
      bool required = false,
      TextInputType keyboard = TextInputType.text}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(label, optional: !required),
        const SizedBox(height: 6),
        TextFormField(
          controller: ctrl,
          keyboardType: keyboard,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(
                fontSize: 13, color: AppConstants.textHint),
          ),
          validator: required
              ? (v) => (v == null || v.trim().isEmpty) ? 'Required' : null
              : null,
        ),
      ],
    );
  }
}
