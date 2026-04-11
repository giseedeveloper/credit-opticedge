import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/constants.dart';
import '../../../core/models/kyc_flow_model.dart';
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
  late final TextEditingController _inventorySearch;
  late final TextEditingController _specs;
  late final TextEditingController _imei1;
  late final TextEditingController _imei2;
  late final TextEditingController _serial;
  late final TextEditingController _cash;
  late final TextEditingController _deposit;
  late final TextEditingController _storeOfferNotes;

  final _repaymentOptions = const ['weekly', 'bi-weekly', 'monthly'];

  @override
  void initState() {
    super.initState();
    final state = ref.read(kycProvider);
    _inventorySearch = TextEditingController(text: state.inventorySearch);
    _specs = TextEditingController(text: state.deviceSpecs);
    _imei1 = TextEditingController(text: state.imeiNumber);
    _imei2 = TextEditingController(text: state.imei2);
    _serial = TextEditingController(text: state.serialNumber);
    _cash = TextEditingController(text: state.cashPrice);
    _deposit = TextEditingController(text: state.depositAmount);
    _storeOfferNotes = TextEditingController(text: state.storeOfferNotes);
  }

  @override
  void dispose() {
    for (final controller in [
      _inventorySearch,
      _specs,
      _imei1,
      _imei2,
      _serial,
      _cash,
      _deposit,
      _storeOfferNotes,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  void _syncControllers(KycDraftState state) {
    final values = <TextEditingController, String>{
      _inventorySearch: state.inventorySearch,
      _specs: state.deviceSpecs,
      _imei1: state.imeiNumber,
      _imei2: state.imei2,
      _serial: state.serialNumber,
      _cash: state.cashPrice,
      _deposit: state.depositAmount,
      _storeOfferNotes: state.storeOfferNotes,
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
            inventorySearch: _inventorySearch.text.trim(),
            deviceSpecs: _specs.text.trim(),
            imeiNumber: _imei1.text.trim(),
            imei2: _imei2.text.trim(),
            serialNumber: _serial.text.trim(),
            cashPrice: _cash.text.trim(),
            depositAmount: _deposit.text.trim(),
            storeOfferNotes: _storeOfferNotes.text.trim(),
          ),
        );
  }

  Future<void> _next() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    _save();
    await ref.read(kycProvider.notifier).submitStep1();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final brandsAsync = ref.watch(deviceBrandsProvider);
    final modelsAsync = ref.watch(deviceModelsProvider(state.brandId));
    final unitsAsync = ref.watch(
      inventoryUnitsProvider(
        (
          phoneModelId: state.phoneModelId,
          search: state.inventorySearch,
        ),
      ),
    );

    _syncControllers(state);

    final hasLinkedInventory = state.inventoryUnitId.isNotEmpty;
    final attachedPhotos = [
      state.imeiPhoto,
      state.deviceBoxPhoto,
      state.devicePhoto,
    ].whereType<Object>().length;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionHeader(
              'Device & Offer Setup',
              'Choose the exact handset, confirm the starting payment, then capture the supporting photos clearly.',
            ),
            const SizedBox(height: 18),
            _introCard(),
            const SizedBox(height: 18),
            brandsAsync.when(
              loading: () => const LinearProgressIndicator(
                color: AppConstants.primary,
              ),
              error: (_, __) => const Text(
                'Failed to load device brands.',
                style: TextStyle(color: AppConstants.error),
              ),
              data: (brands) => _selectionCard(
                title: '1. Pick the handset from stock',
                subtitle:
                    'A linked stock unit keeps price, IMEI, and release tracking aligned.',
                child: Column(
                  children: [
                    DropdownButtonFormField<String>(
                      initialValue:
                          state.brandId.isEmpty ? null : state.brandId,
                      decoration: const InputDecoration(
                        labelText: 'Brand',
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
                      onChanged: (value) {
                        ref
                            .read(kycProvider.notifier)
                            .selectBrand(value?.trim() ?? '');
                      },
                      validator: (value) {
                        if ((value ?? '').trim().isEmpty) {
                          return 'Select a brand';
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
                        'Failed to load device models.',
                        style: TextStyle(color: AppConstants.error),
                      ),
                      data: (models) => DropdownButtonFormField<String>(
                        initialValue: state.phoneModelId.isEmpty
                            ? null
                            : state.phoneModelId,
                        decoration: const InputDecoration(
                          labelText: 'Brand / Model',
                          prefixIcon: Icon(Icons.smartphone_outlined, size: 18),
                        ),
                        items: models
                            .map(
                              (model) => DropdownMenuItem<String>(
                                value: model.id,
                                child: Text(
                                  '${model.brandName} ${model.name}',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            )
                            .toList(),
                        onChanged: (value) {
                          final model =
                              models.cast<DeviceModelOption?>().firstWhere(
                                    (item) => item?.id == value,
                                    orElse: () => null,
                                  );
                          ref.read(kycProvider.notifier).selectModel(model);
                        },
                        validator: (value) {
                          if ((value ?? '').trim().isEmpty) {
                            return 'Select a device model';
                          }

                          return null;
                        },
                      ),
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _inventorySearch,
                      onChanged: (value) {
                        ref.read(kycProvider.notifier).update(
                              (current) => current.copyWith(
                                inventorySearch: value,
                              ),
                            );
                      },
                      decoration: const InputDecoration(
                        labelText: 'Search stock unit',
                        hintText: 'Search by IMEI or serial number',
                        prefixIcon: Icon(Icons.search_rounded, size: 18),
                      ),
                    ),
                    const SizedBox(height: 14),
                    unitsAsync.when(
                      loading: () => const LinearProgressIndicator(
                        color: AppConstants.primary,
                      ),
                      error: (_, __) => const Text(
                        'Failed to load stock units.',
                        style: TextStyle(color: AppConstants.error),
                      ),
                      data: (units) => DropdownButtonFormField<String>(
                        initialValue: state.inventoryUnitId.isEmpty
                            ? null
                            : state.inventoryUnitId,
                        decoration: const InputDecoration(
                          labelText: 'Available Inventory Unit',
                          prefixIcon:
                              Icon(Icons.inventory_2_outlined, size: 18),
                        ),
                        items: units
                            .map(
                              (unit) => DropdownMenuItem<String>(
                                value: unit.id,
                                child: Text(
                                  '${unit.title} • ${unit.subtitle}',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            )
                            .toList(),
                        onChanged: (value) {
                          final unit =
                              units.cast<InventoryUnitOption?>().firstWhere(
                                    (item) => item?.id == value,
                                    orElse: () => null,
                                  );
                          ref
                              .read(kycProvider.notifier)
                              .selectInventoryUnit(unit);
                        },
                        validator: (value) {
                          if ((value ?? '').trim().isEmpty) {
                            return 'Choose the stock unit to release later';
                          }

                          return null;
                        },
                      ),
                    ),
                    if (hasLinkedInventory) ...[
                      const SizedBox(height: 14),
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF0FDF4),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: AppConstants.success.withValues(alpha: 0.16),
                          ),
                        ),
                        child: const Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(
                              Icons.verified_outlined,
                              color: AppConstants.success,
                              size: 18,
                            ),
                            SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                'Great. Price and identifiers are now tied to the selected stock unit so later approval and asset release stay clean.',
                                style: TextStyle(
                                  fontSize: 12,
                                  height: 1.5,
                                  color: AppConstants.textSecondary,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ).animate().fadeIn().slideY(begin: 0.12, end: 0),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            _selectionCard(
              title: '2. Confirm device identifiers',
              subtitle:
                  'These stay editable so the officer can verify what the customer is actually taking.',
              child: Column(
                children: [
                  _field(
                    _specs,
                    'Device Specs',
                    hint: 'e.g. Samsung Galaxy A15 4G 128GB',
                    required: true,
                    readOnly: hasLinkedInventory,
                  ),
                  const SizedBox(height: 12),
                  _field(
                    _imei1,
                    'IMEI 1',
                    hint: '15-digit IMEI',
                    required: true,
                    readOnly: hasLinkedInventory,
                    keyboard: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  _field(
                    _imei2,
                    'IMEI 2',
                    hint: 'Optional secondary IMEI',
                    readOnly: hasLinkedInventory,
                    keyboard: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  _field(
                    _serial,
                    'Serial Number',
                    hint: 'Enter serial number if available',
                    readOnly: hasLinkedInventory,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _field(
                          _cash,
                          'Cash Price (TZS)',
                          required: true,
                          readOnly: hasLinkedInventory,
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
                  const SizedBox(height: 14),
                  _label('Preferred Repayment Cycle'),
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
                        onSelected: (_) {
                          ref.read(kycProvider.notifier).update(
                                (current) => current.copyWith(
                                  preferredRepayment: option,
                                ),
                              );
                        },
                        selectedColor: AppConstants.primarySurface,
                        side: BorderSide(
                          color: selected
                              ? AppConstants.primary
                              : AppConstants.border,
                        ),
                        labelStyle: TextStyle(
                          color: selected
                              ? AppConstants.primary
                              : AppConstants.textSecondary,
                          fontWeight:
                              selected ? FontWeight.w700 : FontWeight.w500,
                        ),
                      );
                    }).toList(),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            _selectionCard(
              title: '3. Store offers and free extras',
              subtitle:
                  'Capture any free items or notes so approval and handover stay transparent.',
              child: Column(
                children: [
                  _offerTile(
                    icon: Icons.shield_outlined,
                    title: 'Screen Protector',
                    subtitle:
                        'Mark this if the shop is giving it as a free extra.',
                    selected: state.includeScreenProtector,
                    onTap: () {
                      ref.read(kycProvider.notifier).update(
                            (current) => current.copyWith(
                              includeScreenProtector:
                                  !current.includeScreenProtector,
                            ),
                          );
                    },
                  ),
                  const SizedBox(height: 10),
                  _offerTile(
                    icon: Icons.phone_android_outlined,
                    title: 'Phone Cover',
                    subtitle:
                        'Useful when the device leaves with a complimentary cover.',
                    selected: state.includePhoneCover,
                    onTap: () {
                      ref.read(kycProvider.notifier).update(
                            (current) => current.copyWith(
                              includePhoneCover: !current.includePhoneCover,
                            ),
                          );
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _storeOfferNotes,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      labelText: 'Store Offer Notes',
                      hintText:
                          'Add any extra promise, discount note, charger, or customer-specific offer.',
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            _selectionCard(
              title: '4. Capture supporting photos',
              subtitle:
                  'Take clear close-up shots of the IMEI sticker, box, and device body so review is fast and clean.',
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFFFFF7ED), Color(0xFFFFFBEB)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: AppConstants.warning.withValues(alpha: 0.14),
                      ),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(
                            Icons.document_scanner_outlined,
                            color: AppConstants.warning,
                          ),
                        )
                            .animate(
                              onPlay: (controller) => controller.repeat(),
                            )
                            .shimmer(
                              duration: 1600.ms,
                              color: Colors.white.withValues(alpha: 0.35),
                            ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                attachedPhotos == 0
                                    ? 'No photo captured yet'
                                    : '$attachedPhotos of 3 photos attached',
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: AppConstants.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 4),
                              const Text(
                                'Keep the sticker flat, avoid glare, and make sure the serial/IMEI lines are fully visible.',
                                style: TextStyle(
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
                        file: state.imeiPhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .setPhoto('imei', file),
                      ),
                      PhotoPickerTile(
                        label: 'Device Box',
                        file: state.deviceBoxPhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .setPhoto('device_box', file),
                      ),
                      PhotoPickerTile(
                        label: 'Device Body',
                        file: state.devicePhoto,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .setPhoto('device', file),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 30),
            AppButton(
              label: 'Save Device Step',
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

  Widget _introCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFFFFBEB), Color(0xFFFFF7ED)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: AppConstants.primary.withValues(alpha: 0.12),
        ),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            Icons.campaign_outlined,
            color: AppConstants.primary,
            size: 20,
          ),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              'Helpful script: “Tuchague simu halisi unayochukua leo, tuhakikishe bei ya kuanzia, kisha nipige picha za sticker na box ili maombi yako yaende haraka bila kurudiwa.”',
              style: TextStyle(
                fontSize: 12,
                height: 1.55,
                color: AppConstants.textSecondary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _selectionCard({
    required String title,
    required String subtitle,
    required Widget child,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppConstants.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 18,
            offset: const Offset(0, 8),
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
              fontWeight: FontWeight.w700,
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

  Widget _offerTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: AnimatedContainer(
        duration: 220.ms,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color:
              selected ? AppConstants.primarySurface : AppConstants.borderLight,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected ? AppConstants.primary : AppConstants.border,
            width: selected ? 1.4 : 1,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
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
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppConstants.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      fontSize: 11,
                      height: 1.45,
                      color: AppConstants.textSecondary,
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

  Widget _label(String text) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: AppConstants.textPrimary,
      ),
    );
  }

  Widget _field(
    TextEditingController controller,
    String label, {
    String? hint,
    bool required = false,
    bool readOnly = false,
    TextInputType keyboard = TextInputType.text,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(label),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          readOnly: readOnly,
          keyboardType: keyboard,
          decoration: InputDecoration(
            hintText: hint,
            suffixIcon: readOnly
                ? const Icon(Icons.lock_outline_rounded, size: 18)
                : null,
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
}
