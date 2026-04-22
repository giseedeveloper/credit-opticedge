import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/app_icon_assets.dart';
import '../../../config/constants.dart';
import '../../../config/design_tokens.dart';
import '../../../core/models/kyc_flow_model.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../core/utils/imei_scan.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/app_color_icon.dart';
import '../../../widgets/common/glass_card.dart';
import '../../../widgets/common/photo_picker_tile.dart';

class Step1DeviceScreen extends ConsumerStatefulWidget {
  const Step1DeviceScreen({super.key});

  @override
  ConsumerState<Step1DeviceScreen> createState() => _Step1State();
}

class _Step1State extends ConsumerState<Step1DeviceScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _imei1;
  late final TextEditingController _imei2;
  late final TextEditingController _serial;
  late final TextEditingController _cash;
  late final TextEditingController _deposit;
  bool _scanningImei = false;

  final _repaymentOptions = const ['weekly', 'bi-weekly', 'monthly'];

  @override
  void initState() {
    super.initState();
    final state = ref.read(kycProvider);
    _imei1 = TextEditingController(text: state.imeiNumber);
    _imei2 = TextEditingController(text: state.imei2);
    _serial = TextEditingController(text: state.serialNumber);
    _cash = TextEditingController(text: state.cashPrice);
    _deposit = TextEditingController(text: state.depositAmount);
  }

  @override
  void dispose() {
    for (final controller in [
      _imei1,
      _imei2,
      _serial,
      _cash,
      _deposit,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  void _syncControllers(KycDraftState state) {
    final values = <TextEditingController, String>{
      _imei1: state.imeiNumber,
      _imei2: state.imei2,
      _serial: state.serialNumber,
      _cash: state.cashPrice,
      _deposit: state.depositAmount,
    };

    for (final entry in values.entries) {
      if (entry.key.text != entry.value) {
        entry.key.value = entry.key.value.copyWith(
          text: entry.value,
          selection: TextSelection.collapsed(offset: entry.value.length),
        );
      }
    }
  }

  void _save() {
    ref.read(kycProvider.notifier).update(
          (state) => state.copyWith(
            imeiNumber: _imei1.text.trim(),
            imei2: _imei2.text.trim(),
            serialNumber: _serial.text.trim(),
            cashPrice: _cash.text.trim(),
            depositAmount: _deposit.text.trim(),
          ),
        );
  }

  Future<void> _tryAutofillImeiFromImage(File file) async {
    if (_scanningImei) return;
    final current = ref.read(kycProvider);
    if (current.imeiNumber.trim().isNotEmpty) {
      return; // don't override a manual entry
    }

    setState(() => _scanningImei = true);
    try {
      final result = await ImeiScan.extractImei(file);
      if (!mounted) return;

      if (result?.imei != null && result!.imei.isNotEmpty) {
        ref.read(kycProvider.notifier).update(
              (state) => state.copyWith(imeiNumber: result.imei),
            );
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'IMEI 1 auto-filled (${result.source}).',
            ),
            backgroundColor: AppConstants.success,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Scan failed. Please type IMEI 1 manually (ensure photo is clear).',
            ),
            backgroundColor: AppConstants.warning,
          ),
        );
      }
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Scan error. Please type IMEI 1 manually.'),
          backgroundColor: AppConstants.warning,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _scanningImei = false);
      }
    }
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final state = ref.read(kycProvider);
    final missingPhotos = <String>[
      if (state.imeiPhoto == null) 'IMEI sticker',
      if (state.deviceBoxPhoto == null) 'Device box',
      if (state.devicePhoto == null) 'Device body',
    ];
    if (missingPhotos.isNotEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Attach ${missingPhotos.join(', ')} photo(s) before continuing.',
          ),
          backgroundColor: AppConstants.error,
        ),
      );
      return;
    }

    // Keep API-compatible defaults even when UI hides loan terms.
    // The backend currently validates these fields strictly.
    ref.read(kycProvider.notifier).update((current) {
      return current.copyWith(
        loanInterestType:
            current.loanInterestType.isNotEmpty ? current.loanInterestType : 'flat',
        loanInterestRate:
            current.loanInterestRate.isNotEmpty ? current.loanInterestRate : '0',
        loanDurationWeeks:
            current.loanDurationWeeks.isNotEmpty ? current.loanDurationWeeks : '52',
        loanGracePeriodDays: current.loanGracePeriodDays.isNotEmpty
            ? current.loanGracePeriodDays
            : '0',
      );
    });

    FocusManager.instance.primaryFocus?.unfocus();
    _save();
    await ref.read(kycProvider.notifier).submitStep1();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final brandsAsync = ref.watch(deviceBrandsProvider);
    final modelsAsync = ref.watch(
      deviceModelsProvider(
        (
          brandId: state.brandId,
          preferredRepayment: state.preferredRepayment,
        ),
      ),
    );

    _syncControllers(state);

    final attachedPhotos = [
      state.imeiPhoto,
      state.deviceBoxPhoto,
      state.devicePhoto,
    ].whereType<Object>().length;
    final identifierCount = [
      state.phoneModelId.isNotEmpty || state.deviceSpecs.isNotEmpty,
      state.imeiNumber.isNotEmpty,
      state.cashPrice.isNotEmpty,
      state.depositAmount.isNotEmpty,
    ].where((item) => item).length;

    return SingleChildScrollView(
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _scanOverviewCard(
              identifierCount: identifierCount,
              attachedPhotos: attachedPhotos,
            ).animate().fadeIn(duration: 260.ms).slideY(begin: 0.08, end: 0),
            const SizedBox(height: 18),
            GlassCard.surface(
              context,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Picha (Scan)',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  const SizedBox(height: 14),
                  GridView.count(
                    crossAxisCount: 3,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                    childAspectRatio: 0.9,
                    children: [
                      PhotoPickerTile(
                        label: 'IMEI Sticker',
                        required: true,
                        file: state.imeiPhoto,
                        onPicked: (file) async {
                          ref.read(kycProvider.notifier).setPhoto('imei', file);
                          if (file != null) {
                            await _tryAutofillImeiFromImage(file);
                          }
                        },
                      ),
                      PhotoPickerTile(
                        label: 'Device Box',
                        required: true,
                        file: state.deviceBoxPhoto,
                        onPicked: (file) async {
                          ref
                              .read(kycProvider.notifier)
                              .setPhoto('device_box', file);
                          if (file != null) {
                            await _tryAutofillImeiFromImage(file);
                          }
                        },
                      ),
                      PhotoPickerTile(
                        label: 'Device Body',
                        required: true,
                        file: state.devicePhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .setPhoto('device', file),
                      ),
                    ],
                  ),
                  if (_scanningImei) ...[
                    const SizedBox(height: 12),
                    const LinearProgressIndicator(
                      color: AppConstants.primary,
                      minHeight: 2,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Scanning IMEI…',
                      style: TextStyle(
                        fontSize: 11,
                        color: Theme.of(context).textTheme.bodyMedium?.color,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ],
              ),
            ).animate().fadeIn(delay: 40.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 16),
            brandsAsync.when(
              loading: () =>
                  const LinearProgressIndicator(color: AppConstants.primary),
              error: (_, __) => const GlassCard(
                tint: AppConstants.errorSurface,
                borderColor: AppConstants.error,
                child: Text(
                  'Failed to load brands. Please retry.',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: AppConstants.error,
                  ),
                ),
              ),
              data: (brands) => GlassCard.surface(
                context,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Selection (Kuchagua)',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: Theme.of(context).textTheme.bodyLarge?.color,
                      ),
                    ),
                    const SizedBox(height: 10),
                    DropdownButtonFormField<String>(
                      key: ValueKey<String?>(
                        state.brandId.isEmpty ? null : state.brandId,
                      ),
                      isExpanded: true,
                      initialValue: state.brandId.isEmpty ? null : state.brandId,
                      decoration: const InputDecoration(
                        labelText: 'Model / Brand (Tecno, Samsung, iPhone...)',
                        prefixIcon: Icon(Icons.category_outlined, size: 18),
                      ),
                      items: brands
                          .map(
                            (brand) => DropdownMenuItem<String>(
                              value: brand.id,
                              child: Text(brand.name),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => ref
                          .read(kycProvider.notifier)
                          .selectBrand(value?.trim() ?? ''),
                      validator: (value) {
                        if ((value ?? '').trim().isEmpty) {
                          return 'Chagua model / brand';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 14),
                    modelsAsync.when(
                      loading: () => const LinearProgressIndicator(
                        color: AppConstants.primary,
                      ),
                      error: (_, __) => const Text(
                        'Failed to load phone specs.',
                        style: TextStyle(color: AppConstants.error),
                      ),
                      data: (models) => DropdownButtonFormField<String>(
                        isExpanded: true,
                        initialValue:
                            state.phoneModelId.isEmpty ? null : state.phoneModelId,
                        decoration: const InputDecoration(
                          labelText: 'Device Specs / Model',
                          prefixIcon: Icon(Icons.smartphone_outlined, size: 18),
                        ),
                        items: models
                            .map(
                              (model) => DropdownMenuItem<String>(
                                value: model.id,
                                child: Text(
                                  model.deviceSpecs.isNotEmpty
                                      ? model.deviceSpecs
                                      : '${model.brandName} ${model.name}',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            )
                            .toList(),
                        onChanged: (value) {
                          final model = models.cast<DeviceModelOption?>().firstWhere(
                                (item) => item?.id == value,
                                orElse: () => null,
                              );
                          ref.read(kycProvider.notifier).selectModel(model);
                        },
                        validator: (value) {
                          if ((value ?? '').trim().isEmpty) {
                            return 'Chagua device specs/model';
                          }
                          return null;
                        },
                      ),
                    ),
                    if (state.phoneModelId.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      _inventoryFromStockSection(context, ref, state),
                    ],
                    const SizedBox(height: 14),
                    Text(
                      'Repayment Cycle',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: Theme.of(context).textTheme.bodyLarge?.color,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: _repaymentOptions.map((option) {
                        final selected = state.preferredRepayment == option;

                        return ChoiceChip(
                          label: Text(
                            option == 'bi-weekly' ? 'Bi-weekly' : _sentence(option),
                          ),
                          selected: selected,
                          onSelected: (_) => ref.read(kycProvider.notifier).update(
                                (current) => current.copyWith(
                                  preferredRepayment: option,
                                ),
                              ),
                          selectedColor: AppConstants.primarySurface,
                          side: BorderSide(
                            color: selected
                                ? AppConstants.primary
                                : Theme.of(context).brightness == Brightness.dark
                                    ? DesignTokens.darkBorder
                                    : AppConstants.border,
                          ),
                          labelStyle: TextStyle(
                            color: selected
                                ? AppConstants.primary
                                : Theme.of(context).textTheme.bodyMedium?.color,
                            fontWeight:
                                selected ? FontWeight.w800 : FontWeight.w600,
                          ),
                        );
                      }).toList(),
                    ),
                  ],
                ),
              ),
            ).animate().fadeIn(delay: 70.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 16),
            GlassCard.surface(
              context,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Store Extras',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  const SizedBox(height: 10),
                  _offerTile(
                    icon: Icons.shield_outlined,
                    title: 'Screen Protector',
                    subtitle: 'Washa kama mteja amepewa.',
                    selected: state.includeScreenProtector,
                    onTap: () => ref.read(kycProvider.notifier).update(
                          (current) => current.copyWith(
                            includeScreenProtector: !current.includeScreenProtector,
                          ),
                        ),
                  ),
                  const SizedBox(height: 10),
                  _offerTile(
                    icon: Icons.phone_android_outlined,
                    title: 'Phone Cover',
                    subtitle: 'Washa kama mteja amepewa.',
                    selected: state.includePhoneCover,
                    onTap: () => ref.read(kycProvider.notifier).update(
                          (current) => current.copyWith(
                            includePhoneCover: !current.includePhoneCover,
                          ),
                        ),
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 100.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 16),
            GlassCard.surface(
              context,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Manual (Mkono)',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                      color: Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _field(
                    _imei1,
                    'IMEI 1',
                    hint: '15 digits',
                    required: true,
                    keyboard: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _field(
                          _imei2,
                          'IMEI 2',
                          hint: '15 digits (dual SIM)',
                          keyboard: TextInputType.number,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _field(
                          _serial,
                          'Serial Number',
                          hint: 'S/N',
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _field(
                          _cash,
                          'Cash Price (TZS)',
                          required: true,
                          keyboard: TextInputType.number,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _field(
                          _deposit,
                          'Starting Deposit (TZS)',
                          required: true,
                          keyboard: TextInputType.number,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 130.ms).slideY(begin: 0.06, end: 0),
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

  Widget _scanOverviewCard({
    required int identifierCount,
    required int attachedPhotos,
  }) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF103454), Color(0xFF1F5A88)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppConstants.heroEnd.withValues(alpha: 0.2),
            blurRadius: 24,
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
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Center(
                  child: AppColorIcon(
                    assetName: AppIconAssets.device,
                    size: 22,
                    tintColor: Colors.white,
                    semanticsLabel: 'Device scan',
                  ),
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
                      'Picha 3/3 · identifiers · bei/deposit',
                      style: TextStyle(
                        fontSize: 12,
                        height: 1.45,
                        color: Colors.white.withValues(alpha: 0.82),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _overviewMetric(
                  label: 'Identifiers',
                  value: '$identifierCount/4',
                  iconAsset: AppIconAssets.identity,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _overviewMetric(
                  label: 'Photo Evidence',
                  value: '$attachedPhotos/3',
                  iconAsset: AppIconAssets.checklist,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _overviewMetric({
    required String label,
    required String value,
    required String iconAsset,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppColorIcon(
            assetName: iconAsset,
            size: 18,
            tintColor: Colors.white,
            semanticsLabel: label,
          ),
          const SizedBox(height: 10),
          Text(
            value,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: Colors.white.withValues(alpha: 0.78),
            ),
          ),
        ],
      ),
    );
  }

  Widget _offerTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required bool selected,
    required VoidCallback onTap,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final mutedSurface = isDark
        ? DesignTokens.darkBorder.withValues(alpha: 0.35)
        : AppConstants.borderLight;
    final iconPlate =
        isDark ? DesignTokens.darkSurfaceElevated : Colors.white;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: AnimatedContainer(
        duration: 220.ms,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: selected ? AppConstants.primarySurface : mutedSurface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected
                ? AppConstants.primary
                : (isDark ? DesignTokens.darkBorder : AppConstants.border),
            width: selected ? 1.4 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: iconPlate,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                icon,
                color: selected
                    ? AppConstants.primary
                    : theme.textTheme.bodyMedium?.color,
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
                      fontWeight: FontWeight.w700,
                      color: theme.textTheme.bodyLarge?.color,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 11,
                      height: 1.45,
                      color: theme.textTheme.bodyMedium?.color,
                    ),
                  ),
                ],
              ),
            ),
            Switch(
              value: selected,
              onChanged: (_) => onTap(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _label(String text) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: Theme.of(context).textTheme.bodyLarge?.color,
      ),
    );
  }

  Widget _field(
    TextEditingController controller,
    String label, {
    String? hint,
    bool required = false,
    TextInputType keyboard = TextInputType.text,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(label),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          keyboardType: keyboard,
          decoration: InputDecoration(
            hintText: hint,
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

  String _sentence(String value) {
    if (value.isEmpty) {
      return value;
    }

    return value[0].toUpperCase() + value.substring(1);
  }

  /// Links Step 1 to an existing [InventoryUnit] so the API sends
  /// `inventory_unit_id` and skips "register new IMEI" validation.
  Widget _inventoryFromStockSection(
    BuildContext context,
    WidgetRef ref,
    KycDraftState state,
  ) {
    final unitsAsync = ref.watch(
      inventoryUnitsProvider((
        phoneModelId: state.phoneModelId,
        search: '',
        preferredRepayment: state.preferredRepayment,
      )),
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Stock duukani (pendekezwa)',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: Theme.of(context).textTheme.bodyLarge?.color,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          'Ikiwa IMEI tayari iko kwenye mfumo, chagua kifaa hapa ili kuunganisha stock '
          '— vinginevyo utaona "IMEI already exists in inventory".',
          style: TextStyle(
            fontSize: 11,
            height: 1.45,
            color: Theme.of(context).textTheme.bodyMedium?.color,
          ),
        ),
        const SizedBox(height: 10),
        unitsAsync.when(
          loading: () => const LinearProgressIndicator(
            color: AppConstants.primary,
            minHeight: 2,
          ),
          error: (_, __) => const Text(
            'Imeshindwa kupakia stock.',
            style: TextStyle(color: AppConstants.error, fontSize: 12),
          ),
          data: (units) {
            if (state.inventoryUnitId.isNotEmpty &&
                !units.any((u) => u.id == state.inventoryUnitId)) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (!context.mounted) return;
                ref.read(kycProvider.notifier).selectInventoryUnit(null);
              });
            }

            if (units.isEmpty) {
              return Text(
                'Hakuna vitu vya stock vilivyopatikana kwa model hii katika tawi lako. '
                'Tumia Manual tu ikiwa kifaa hakijajumuishwa kwenye stock.',
                style: TextStyle(
                  fontSize: 12,
                  height: 1.45,
                  color: Theme.of(context).textTheme.bodyMedium?.color,
                ),
              );
            }

            final selectedId = state.inventoryUnitId.isNotEmpty &&
                    units.any((u) => u.id == state.inventoryUnitId)
                ? state.inventoryUnitId
                : null;

            return DropdownButtonFormField<String?>(
              key: ValueKey<String?>(selectedId),
              isExpanded: true,
              initialValue: selectedId,
              decoration: const InputDecoration(
                labelText: 'Chagua kifaa (IMEI) kutoka stock',
                prefixIcon: Icon(Icons.inventory_2_outlined, size: 18),
              ),
              items: [
                const DropdownMenuItem<String?>(
                  value: null,
                  child: Text(
                    'Sijasajili kutoka stock — andika IMEI mkono',
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                ...units.map(
                  (u) => DropdownMenuItem<String?>(
                    value: u.id,
                    child: Text(
                      u.subtitle,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
              ],
              onChanged: (id) {
                if (id == null) {
                  ref.read(kycProvider.notifier).selectInventoryUnit(null);
                } else {
                  final unit = units.firstWhere((u) => u.id == id);
                  ref.read(kycProvider.notifier).selectInventoryUnit(unit);
                }
              },
            );
          },
        ),
      ],
    );
  }
}
