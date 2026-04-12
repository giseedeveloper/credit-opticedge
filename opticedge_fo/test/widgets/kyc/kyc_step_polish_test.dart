import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/models/dashboard_model.dart';
import 'package:opticedge_fo/core/models/kyc_flow_model.dart';
import 'package:opticedge_fo/core/providers/kyc_provider.dart';
import 'package:opticedge_fo/screens/kyc/steps/step1_device.dart';
import 'package:opticedge_fo/screens/kyc/steps/step2_identity.dart';
import 'package:opticedge_fo/screens/kyc/steps/step3_contact.dart';
import 'package:opticedge_fo/screens/kyc/steps/step7_submit.dart';

class _TestKycNotifier extends KycNotifier {
  _TestKycNotifier(KycDraftState initial) : super() {
    state = initial;
  }

  @override
  Future<void> loadFinalContext() async {}
}

void main() {
  testWidgets('Step 1 shows scan experience hero and linked stock snapshot',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          kycProvider.overrideWith(
            (ref) => _TestKycNotifier(
              const KycDraftState(
                brandId: 'brand-1',
                phoneModelId: 'model-1',
                inventoryUnitId: 'unit-1',
                deviceSpecs: 'Samsung Galaxy A15 128GB',
                imeiNumber: '123456789012345',
                serialNumber: 'SN-001',
                cashPrice: '420000',
                depositAmount: '120000',
              ),
            ),
          ),
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
        ],
        child: const MaterialApp(
          home: Scaffold(body: Step1DeviceScreen()),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    expect(find.text('Device Scan Experience'), findsOneWidget);
    expect(find.text('Stock-linked handset is ready'), findsOneWidget);
    expect(find.text('Scan lane'), findsOneWidget);
  });

  testWidgets('Step 2 renders identity confidence and evidence guidance',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: MaterialApp(
          home: Scaffold(body: Step2IdentityScreen()),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    expect(find.text('Identity Confidence'), findsOneWidget);
    expect(find.text('3. Capture verification evidence'), findsOneWidget);
    expect(find.text('Use bright light'), findsOneWidget);
  });

  testWidgets('Step 3 renders routing health and branch accountability copy',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
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
              BranchModel(id: '1', name: 'Kariakoo Branch'),
            ],
          ),
        ],
        child: const MaterialApp(
          home: Scaffold(body: Step3ContactScreen()),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    expect(find.text('Contact routing health'), findsOneWidget);
    expect(find.text('2. Service branch and customer routing'), findsOneWidget);
    expect(find.text('Location summary'), findsOneWidget);
  });

  testWidgets('Step 7 renders final mile flow and agreement preview action',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          kycProvider.overrideWith(
            (ref) => _TestKycNotifier(
              const KycDraftState(
                phone: '0678165524',
                depositAmount: '2000',
                paymentContext: KycPaymentContext(
                  status: 'completed',
                  amount: 2000,
                  phone: '255678165524',
                  reference: 'REF-2000',
                  isCompleted: true,
                ),
                agreementContext: KycAgreementContext(
                  activeDocument: KycDocumentOption(
                    id: 'doc-1',
                    title: 'OpticEdge Device Agreement',
                    url:
                        'https://credit.opticedgeafrica.net/storage/agreement.pdf',
                    mimeType: 'application/pdf',
                    originalName: 'agreement.pdf',
                  ),
                ),
              ),
            ),
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
        ],
        child: const MaterialApp(
          home: Scaffold(body: Step7SubmitScreen()),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    expect(find.text('Final Mile Flow'), findsOneWidget);
    expect(find.text('Agreement preview'), findsWidgets);
    expect(find.text('View Agreement Preview'), findsOneWidget);
  });
}
