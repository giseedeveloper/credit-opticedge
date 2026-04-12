import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../config/constants.dart';
import '../../../core/api/api_client.dart';
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

    FocusManager.instance.primaryFocus?.unfocus();
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
    final deviceSetupCount = [
      state.brandId.isNotEmpty,
      state.phoneModelId.isNotEmpty,
      state.inventoryUnitId.isNotEmpty,
    ].where((item) => item).length;
    final identifierCount = [
      state.deviceSpecs.isNotEmpty,
      state.imeiNumber.isNotEmpty,
      state.serialNumber.isNotEmpty,
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
            _sectionHeader(
              'Device & Offer Setup',
              'Choose the exact handset, confirm the starting payment, then capture the supporting photos clearly.',
            ),
            const SizedBox(height: 18),
            _introCard(),
            const SizedBox(height: 18),
            _scanOverviewCard(
              deviceSetupCount: deviceSetupCount,
              identifierCount: identifierCount,
              attachedPhotos: attachedPhotos,
              hasLinkedInventory: hasLinkedInventory,
            ).animate().fadeIn(duration: 260.ms).slideY(begin: 0.08, end: 0),
            const SizedBox(height: 18),
            brandsAsync.when(
              loading: () => const LinearProgressIndicator(
                color: AppConstants.primary,
              ),
              error: (error, _) => _asyncError(
                title: 'Failed to load device brands.',
                message: ApiClient.instance.parseError(error),
                onRetry: () => ref.invalidate(deviceBrandsProvider),
              ),
              data: (brands) => _selectionCard(
                title: '1. Pick the handset from stock',
                subtitle:
                    'A linked stock unit keeps price, IMEI, and release tracking aligned.',
                child: Column(
                  children: [
                    if (brands.isEmpty)
                      _emptyScopeHint(
                        title: 'No brands available',
                        body:
                            'Brands listed here come from inventory available to your account (branch/vendor scope). '
                            'If this list is empty, there is no qualifying stock yet—add or receive units in the system, '
                            'or ask a supervisor to confirm your branch/vendor assignment.',
                        onRefresh: () => ref.invalidate(deviceBrandsProvider),
                      )
                    else
                      DropdownButtonFormField<String>(
                        key: ValueKey<String?>(
                          state.brandId.isEmpty ? null : state.brandId,
                        ),
                        isExpanded: true,
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
                    if (brands.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      modelsAsync.when(
                        loading: () => const LinearProgressIndicator(
                          color: AppConstants.primary,
                        ),
                        error: (error, _) => _asyncError(
                          title: 'Failed to load device models.',
                          message: ApiClient.instance.parseError(error),
                          onRetry: () => ref.invalidate(
                            deviceModelsProvider(state.brandId),
                          ),
                        ),
                        data: (models) => DropdownButtonFormField<String>(
                          isExpanded: true,
                          initialValue: state.phoneModelId.isEmpty
                              ? null
                              : state.phoneModelId,
                          decoration: const InputDecoration(
                            labelText: 'Brand / Model',
                            prefixIcon:
                                Icon(Icons.smartphone_outlined, size: 18),
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
                        error: (error, _) => _asyncError(
                          title: 'Failed to load stock units.',
                          message: ApiClient.instance.parseError(error),
                          onRetry: () => ref.invalidate(
                            inventoryUnitsProvider(
                              (
                                phoneModelId: state.phoneModelId,
                                search: state.inventorySearch,
                              ),
                            ),
                          ),
                        ),
                        data: (units) => DropdownButtonFormField<String>(
                          isExpanded: true,
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
                    ],
                    if (hasLinkedInventory) ...[
                      const SizedBox(height: 14),
                      _linkedInventorySnapshot(state)
                          .animate()
                          .fadeIn()
                          .slideY(begin: 0.12, end: 0),
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
                  _scanLane(attachedPhotos: attachedPhotos, state: state),
                  const SizedBox(height: 14),
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

  Widget _scanOverviewCard({
    required int deviceSetupCount,
    required int identifierCount,
    required int attachedPhotos,
    required bool hasLinkedInventory,
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
                child: const Icon(
                  Icons.qr_code_scanner_rounded,
                  color: Colors.white,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Device Scan Experience',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      hasLinkedInventory
                          ? 'Handset is linked to stock. You can now verify stickers and supporting photos with confidence.'
                          : 'Start with the exact handset from stock, then confirm identifiers and capture the scan evidence.',
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
                  label: 'Handset Setup',
                  value: '$deviceSetupCount/3',
                  icon: Icons.inventory_2_outlined,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _overviewMetric(
                  label: 'Identifiers',
                  value: '$identifierCount/5',
                  icon: Icons.confirmation_number_outlined,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _overviewMetric(
                  label: 'Photo Evidence',
                  value: '$attachedPhotos/3',
                  icon: Icons.photo_camera_back_outlined,
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
    required IconData icon,
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
          Icon(icon, size: 18, color: Colors.white),
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

  Widget _linkedInventorySnapshot(KycDraftState state) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppConstants.successSurface, AppConstants.surfaceRaised],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: AppConstants.success.withValues(alpha: 0.16),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.verified_outlined,
                  color: AppConstants.success,
                ),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Stock-linked handset is ready',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: AppConstants.textPrimary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Text(
            'Price, IMEI, and future asset release are now tied to one inventory unit so approval stays clean.',
            style: TextStyle(
              fontSize: 12,
              height: 1.45,
              color: AppConstants.textSecondary,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _snapshotChip(
                icon: Icons.smartphone_outlined,
                label: state.deviceSpecs.isNotEmpty
                    ? state.deviceSpecs
                    : 'Device linked',
              ),
              if (state.imeiNumber.isNotEmpty)
                _snapshotChip(
                  icon: Icons.qr_code_2_rounded,
                  label: state.imeiNumber,
                ),
              if (state.serialNumber.isNotEmpty)
                _snapshotChip(
                  icon: Icons.tag_outlined,
                  label: state.serialNumber,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _snapshotChip({
    required IconData icon,
    required String label,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppConstants.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppConstants.success),
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

  Widget _scanLane({
    required int attachedPhotos,
    required KycDraftState state,
  }) {
    final steps = [
      (
        title: 'Sticker',
        hint: 'IMEI lines visible',
        icon: Icons.qr_code_scanner_rounded,
        done: state.imeiPhoto != null,
      ),
      (
        title: 'Box',
        hint: 'Retail label captured',
        icon: Icons.inventory_2_outlined,
        done: state.deviceBoxPhoto != null,
      ),
      (
        title: 'Device',
        hint: 'Body condition shown',
        icon: Icons.smartphone_outlined,
        done: state.devicePhoto != null,
      ),
    ];

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppConstants.surfaceMuted,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text(
                'Scan lane',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: AppConstants.textPrimary,
                ),
              ),
              const Spacer(),
              Text(
                '$attachedPhotos/3 ready',
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: AppConstants.textSecondary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              for (var index = 0; index < steps.length; index++) ...[
                Expanded(
                  child: _scanStage(
                    title: steps[index].title,
                    hint: steps[index].hint,
                    icon: steps[index].icon,
                    done: steps[index].done,
                  ),
                ),
                if (index < steps.length - 1)
                  Container(
                    width: 18,
                    height: 2,
                    margin: const EdgeInsets.symmetric(horizontal: 6),
                    color: steps[index].done
                        ? AppConstants.success
                        : AppConstants.border,
                  ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _scanStage({
    required String title,
    required String hint,
    required IconData icon,
    required bool done,
  }) {
    return AnimatedContainer(
      duration: 220.ms,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: done ? AppConstants.successSurface : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: done ? AppConstants.success : AppConstants.border,
          width: done ? 1.4 : 1,
        ),
      ),
      child: Column(
        children: [
          Icon(
            done ? Icons.check_circle_rounded : icon,
            size: 18,
            color: done ? AppConstants.success : AppConstants.textSecondary,
          ),
          const SizedBox(height: 8),
          Text(
            title,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w800,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            hint,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 10,
              height: 1.35,
              color: AppConstants.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _emptyScopeHint({
    required String title,
    required String body,
    required VoidCallback onRefresh,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppConstants.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                Icons.inventory_2_outlined,
                color: AppConstants.warning.withValues(alpha: 0.9),
                size: 20,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: AppConstants.textPrimary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            body,
            style: const TextStyle(
              fontSize: 12,
              height: 1.45,
              color: AppConstants.textSecondary,
            ),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: onRefresh,
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: const Text('Refresh'),
          ),
        ],
      ),
    );
  }

  Widget _asyncError({
    required String title,
    required String message,
    required VoidCallback onRetry,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.red.shade100),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: AppConstants.error,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            message,
            style: const TextStyle(color: AppConstants.textSecondary),
          ),
          if (kDebugMode) ...[
            const SizedBox(height: 8),
            Text(
              'API: ${ApiClient.instance.activeBaseUrl}',
              style: const TextStyle(
                color: AppConstants.textHint,
                fontSize: 12,
              ),
            ),
          ],
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: const Text('Retry'),
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
