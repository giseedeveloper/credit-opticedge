import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:opticedge_fo/core/models/dashboard_model.dart';
import 'package:opticedge_fo/core/models/kyc_flow_model.dart';
import 'package:opticedge_fo/core/providers/kyc_provider.dart';
import 'package:opticedge_fo/screens/kyc/kyc_wizard_screen.dart';

class _BootstrapDraftNotifier extends KycNotifier {
  @override
  Future<bool> loadExistingDraft(String customerId) async {
    state = const KycDraftState(
      customerId: 'draft-001',
      currentStep: 4,
      brandId: 'brand-1',
      phoneModelId: 'model-1',
      inventoryUnitId: 'unit-1',
      phone: '0712345678',
      phoneCountry: 'TZ',
      branchId: 'branch-1',
      monthlyIncome: '650000',
      nokName: 'Asha J',
      nokPhone: '0755000000',
      nokPhoneCountry: 'TZ',
    );

    return true;
  }

  @override
  Future<void> loadFinalContext() async {}
}

void main() {
  testWidgets('draft bootstrap waits for the page view before jumping steps',
      (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 2160);
    tester.view.devicePixelRatio = 3.0;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          kycProvider.overrideWith((ref) => _BootstrapDraftNotifier()),
          deviceBrandsProvider.overrideWith(
            (ref) async => const [
              DeviceBrandOption(id: 'brand-1', name: 'Samsung'),
            ],
          ),
          deviceModelsProvider('brand-1').overrideWith(
            (ref) async => const [
              DeviceModelOption(
                id: 'model-1',
                brandId: 'brand-1',
                brandName: 'Samsung',
                name: 'Galaxy A15',
                retailPrice: 420000,
                deviceSpecs: 'Samsung Galaxy A15 128GB',
              ),
            ],
          ),
          inventoryUnitsProvider((phoneModelId: 'model-1', search: ''))
              .overrideWith(
            (ref) async => const [
              InventoryUnitOption(
                id: 'unit-1',
                phoneModelId: 'model-1',
                brandName: 'Samsung',
                modelName: 'Galaxy A15',
                deviceSpecs: 'Samsung Galaxy A15 128GB',
                recommendedCashPrice: 420000,
                imei1: '123456789012345',
                serialNumber: 'SN-001',
                status: 'available',
              ),
            ],
          ),
          phoneCountriesProvider.overrideWith(
            (ref) async => const [
              PhoneCountryOption(
                iso: 'TZ',
                name: 'Tanzania',
                dialCode: '+255',
                flag: 'TZ',
              ),
            ],
          ),
          branchesProvider.overrideWith(
            (ref) async => const [
              BranchModel(id: 'branch-1', name: 'Kariakoo Branch'),
            ],
          ),
        ],
        child: const MaterialApp(
          home: KycWizardScreen(draftCustomerId: 'draft-001'),
        ),
      ),
    );

    await tester.pump();
    await tester.pumpAndSettle(const Duration(milliseconds: 800));

    expect(tester.takeException(), isNull);
    expect(find.text('Understand repayment ability'), findsOneWidget);

    await tester.pumpWidget(const SizedBox.shrink());
    await tester.pumpAndSettle(const Duration(milliseconds: 400));
  });
}
