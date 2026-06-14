import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/app_icon_assets.dart';
import '../../../config/constants.dart';
import '../../../config/design_tokens.dart';
import '../../../core/models/kyc_flow_model.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../core/utils/device_scan.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/app_color_icon.dart';
import '../../../widgets/common/glass_card.dart';
import '../../../widgets/common/photo_picker_tile.dart';
import '../../../widgets/kyc/kyc_wizard_ui.dart';

class Step1DeviceScreen extends ConsumerStatefulWidget {
  const Step1DeviceScreen({super.key});

  @override
  ConsumerState<Step1DeviceScreen> createState() => _Step1State();
}

class _Step1State extends ConsumerState<Step1DeviceScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _imei1;
  late final TextEditingController _imei2;
  late final TextEditingController _cash;
  late final TextEditingController _deposit;
  bool _scanningImei = false;

  final _repaymentOptions = const ['daily', 'weekly', 'bi-weekly', 'monthly'];

  @override
  void initState() {
    super.initState();
    final state = ref.read(kycProvider);
    _imei1 = TextEditingController(text: state.imeiNumber);
    _imei2 = TextEditingController(text: state.imei2);
    _cash = TextEditingController(text: state.cashPrice);
    _deposit = TextEditingController(text: state.depositAmount);
  }

  @override
  void dispose() {
    for (final controller in [
      _imei1,
      _imei2,
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
            cashPrice: _cash.text.trim(),
            depositAmount: _deposit.text.trim(),
          ),
        );
  }

  Future<void> _tryAutofillImeiFromImage(File file) async {
    if (_scanningImei) return;

    setState(() => _scanningImei = true);
    try {
      final hints = await DeviceScan.extractHints(file);
      if (!mounted) return;

      final message =
          await ref.read(kycProvider.notifier).applyDeviceScanHints(hints);
      if (!mounted) {
        return;
      }
      _syncControllers(ref.read(kycProvider));

      if (message != null && message.isNotEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: AppConstants.success,
          ),
        );
      } else if (hints.imei?.imei.isNotEmpty == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              (hints.imei?.imei2 ?? '').isNotEmpty
                  ? 'IMEI 1 & IMEI 2 auto-filled (${hints.imei!.source}).'
                  : 'IMEI 1 auto-filled (${hints.imei!.source}).',
            ),
            backgroundColor: AppConstants.success,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Scan finished. Enter IMEI manually if it was not detected.',
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
    final loanPreviewAsync = ref.watch(kycLoanPreviewProvider);
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
    ].whereType<Object>().length;
    final pricingLocked = state.phoneModelId.trim().isNotEmpty;
    final identifierCount = [
      state.phoneModelId.isNotEmpty || state.deviceSpecs.isNotEmpty,
      state.imeiNumber.isNotEmpty,
      state.cashPrice.isNotEmpty,
      state.depositAmount.isNotEmpty,
    ].where((item) => item).length;

    return SingleChildScrollView(
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      padding: KycWizardUi.pagePadding,
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _scanOverviewCard(
              identifierCount: identifierCount,
              attachedPhotos: attachedPhotos,
              requiredPhotos: 2,
            ).animate().fadeIn(duration: 260.ms).slideY(begin: 0.08, end: 0),
            const SizedBox(height: 12),
            GlassCard.surface(
              context,
              padding: KycWizardUi.cardPadding,
              borderRadius: KycWizardUi.cardRadius,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Picha (Scan)',
                    style: TextStyle(
                      fontSize: KycWizardUi.cardTitleSize,
                      fontWeight: FontWeight.w800,
                      color: Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  const SizedBox(height: 10),
                  GridView.count(
                    crossAxisCount: 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisSpacing: 8,
                    mainAxisSpacing: 8,
                    childAspectRatio: 1.08,
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
            const SizedBox(height: 10),
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
              data: (brands) {
                return GlassCard.surface(
                  context,
                  padding: KycWizardUi.cardPadding,
                  borderRadius: KycWizardUi.cardRadius,
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
                      if (brands.isEmpty) ...[
                        Text(
                          'No brands found from server. Check API / connectivity then retry.',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: Theme.of(context).textTheme.bodyMedium?.color,
                            height: 1.35,
                          ),
                        ),
                        const SizedBox(height: 10),
                        OutlinedButton.icon(
                          onPressed: () => ref.refresh(deviceBrandsProvider),
                          icon: const Icon(Icons.refresh_rounded, size: 18),
                          label: const Text('Retry'),
                        ),
                      ] else ...[
                        DropdownButtonFormField<String>(
                          key: ValueKey<String?>(
                            state.brandId.isEmpty ? null : state.brandId,
                          ),
                          isExpanded: true,
                          initialValue:
                              state.brandId.isEmpty ? null : state.brandId,
                          decoration: const InputDecoration(
                            labelText: 'Model / Brand (Tecno, Samsung, iPhone...)',
                            prefixIcon: Icon(Icons.category_outlined, size: 18),
                          ),
                          items: brands
                              .where((b) => b.id.trim().isNotEmpty)
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
                          data: (models) {
                            if (state.brandId.trim().isNotEmpty &&
                                models.isEmpty) {
                              return Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'No models found for this brand.',
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: Theme.of(context)
                                          .textTheme
                                          .bodyMedium
                                          ?.color,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  OutlinedButton.icon(
                                    onPressed: () => ref.refresh(
                                      deviceModelsProvider((
                                        brandId: state.brandId,
                                        preferredRepayment:
                                            state.preferredRepayment,
                                      )),
                                    ),
                                    icon: const Icon(Icons.refresh_rounded,
                                        size: 18),
                                    label: const Text('Retry models'),
                                  ),
                                ],
                              );
                            }

                            return DropdownButtonFormField<String>(
                              isExpanded: true,
                              initialValue: state.phoneModelId.isEmpty
                                  ? null
                                  : state.phoneModelId,
                              decoration: const InputDecoration(
                                labelText: 'Device Specs / Model',
                                prefixIcon:
                                    Icon(Icons.smartphone_outlined, size: 18),
                              ),
                              items: models
                                  .where((m) => m.id.trim().isNotEmpty)
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
                                final model = models
                                    .cast<DeviceModelOption?>()
                                    .firstWhere(
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
                            );
                          },
                        ),
                        // Stock linking UI removed (FO selects brand/model only).
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
                                option == 'bi-weekly'
                                    ? 'Bi-weekly'
                                    : _sentence(option),
                              ),
                              selected: selected,
                              onSelected: (_) =>
                                  ref.read(kycProvider.notifier).update(
                                        (current) => current.copyWith(
                                          preferredRepayment: option,
                                        ),
                                      ),
                              selectedColor: AppConstants.primarySurface,
                              side: BorderSide(
                                color: selected
                                    ? AppConstants.primary
                                    : Theme.of(context).brightness ==
                                            Brightness.dark
                                        ? DesignTokens.darkBorder
                                        : AppConstants.border,
                              ),
                              labelStyle: TextStyle(
                                color: selected
                                    ? AppConstants.primary
                                    : Theme.of(context)
                                        .textTheme
                                        .bodyMedium
                                        ?.color,
                                fontWeight: selected
                                    ? FontWeight.w800
                                    : FontWeight.w600,
                              ),
                            );
                          }).toList(),
                        ),
                      ],
                    ],
                  ),
                );
              },
            ).animate().fadeIn(delay: 70.ms).slideY(begin: 0.06, end: 0),
            const SizedBox(height: 10),
            GlassCard.surface(
              context,
              padding: KycWizardUi.cardPadding,
              borderRadius: KycWizardUi.cardRadius,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Manual (Mkono)',
                    style: TextStyle(
                      fontSize: KycWizardUi.cardTitleSize,
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
                  _field(
                    _imei2,
                    'IMEI 2',
                    hint: '15 digits (dual SIM)',
                    keyboard: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _field(
                          _cash,
                          'Device Price (TZS)',
                          required: true,
                          keyboard: TextInputType.number,
                          readOnly: pricingLocked,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _field(
                          _deposit,
                          'Starting Deposit (TZS)',
                          required: true,
                          keyboard: TextInputType.number,
                          readOnly: pricingLocked,
                        ),
                      ),
                    ],
                  ),
                  if (pricingLocked) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Device price and deposit are locked from the selected model.',
                      style: TextStyle(
                        fontSize: 11,
                        height: 1.4,
                        color: Theme.of(context).textTheme.bodyMedium?.color,
                      ),
                    ),
                  ],
                ],
              ),
            ).animate().fadeIn(delay: 130.ms).slideY(begin: 0.06, end: 0),
            loanPreviewAsync.when(
              data: (preview) {
                if (preview == null) {
                  return const SizedBox.shrink();
                }

                final installment = preview['installment_amount'];
                final totalPayable = preview['total_payable'];
                final financed = preview['financed_principal'];
                final count = preview['installment_count'];
                final frequency =
                    preview['repayment_frequency']?.toString() ?? 'weekly';

                return Padding(
                  padding: const EdgeInsets.only(top: 10),
                  child: GlassCard.surface(
                    context,
                    padding: KycWizardUi.cardPadding,
                    borderRadius: KycWizardUi.cardRadius,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Loan Preview',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w800,
                            color: Theme.of(context).textTheme.bodyLarge?.color,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: _previewMetric(
                                'Financed',
                                'TZS ${_formatAmount(financed)}',
                              ),
                            ),
                            Expanded(
                              child: _previewMetric(
                                '${_sentence(frequency)} pay',
                                'TZS ${_formatAmount(installment)}',
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Expanded(
                              child: _previewMetric(
                                'Total payable',
                                'TZS ${_formatAmount(totalPayable)}',
                              ),
                            ),
                            Expanded(
                              child: _previewMetric(
                                'Payments',
                                '${count ?? '-'} × ${_sentence(frequency)}',
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
              loading: () => const SizedBox.shrink(),
              error: (_, __) => const SizedBox.shrink(),
            ),
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

  Widget _scanOverviewCard({
    required int identifierCount,
    required int attachedPhotos,
    required int requiredPhotos,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF103454), Color(0xFF1F5A88)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
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
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Center(
                  child: AppColorIcon(
                    assetName: AppIconAssets.device,
                    size: 18,
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
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Picha $requiredPhotos/$requiredPhotos · identifiers · bei/deposit',
                      style: TextStyle(
                        fontSize: 11,
                        height: 1.45,
                        color: Colors.white.withValues(alpha: 0.82),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
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
                  value: '$attachedPhotos/$requiredPhotos',
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
              fontSize: KycWizardUi.sectionTitleSize,
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
    bool readOnly = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(label),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          keyboardType: keyboard,
          readOnly: readOnly,
          decoration: InputDecoration(
            hintText: hint,
            filled: readOnly,
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

    if (value == 'biweekly') {
      return 'Bi-weekly';
    }

    return value[0].toUpperCase() + value.substring(1);
  }

  String _formatAmount(Object? value) {
    final parsed = value is num
        ? value.toDouble()
        : double.tryParse(value?.toString() ?? '') ?? 0;

    return parsed.round().toString().replaceAllMapped(
          RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
          (match) => '${match[1]},',
        );
  }

  Widget _previewMetric(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            color: AppConstants.textSecondary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            color: AppConstants.textPrimary,
          ),
        ),
      ],
    );
  }

  // Stock linking UI removed.
}
