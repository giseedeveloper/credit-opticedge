import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_customer/core/models/loan_model.dart';
import 'package:opticedge_customer/core/providers/loan_provider.dart';
import 'package:opticedge_customer/core/providers/payment_provider.dart';
import 'package:opticedge_customer/screens/payment/pay_screen.dart';
import 'package:opticedge_customer/screens/schedule/schedule_screen.dart';

class _FakeLoanNotifier extends LoanNotifier {
  _FakeLoanNotifier(LoanState initial) : super() {
    state = initial;
  }

  @override
  Future<void> load() async {}
}

class _FakeScheduleNotifier extends ScheduleNotifier {
  _FakeScheduleNotifier(ScheduleState initial) : super() {
    state = initial;
  }

  @override
  Future<void> load() async {}
}

class _FakePaymentNotifier extends PaymentNotifier {
  _FakePaymentNotifier(PaymentState initial) : super() {
    state = initial;
  }

  @override
  Future<bool> requestPayment({required double amount, String? phone}) async {
    return false;
  }
}

void main() {
  const releaseContext = LoanReleaseContext(
    assetReleaseStatus: 'released',
    assetReleasedAt: '2026-04-13 08:15:00',
    cashPrice: 560000,
    depositAmount: 500,
    preferredRepayment: 'weekly',
    inventoryUnitId: 'unit-123',
  );

  testWidgets('schedule screen shows pending disbursement state clearly', (
    tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          scheduleProvider.overrideWith(
            (ref) => _FakeScheduleNotifier(
              const ScheduleState(
                portalState: 'released_pending_disbursement',
                statusMessage:
                    'Your device has been released, but your loan account is still being prepared.',
                releaseContext: releaseContext,
              ),
            ),
          ),
        ],
        child: const MaterialApp(home: ScheduleScreen()),
      ),
    );

    await tester.pump();

    expect(find.text('Ratiba Inaandaliwa'), findsOneWidget);
    expect(
      find.textContaining('loan account is still being prepared'),
      findsOneWidget,
    );
    expect(find.text('Kila wiki'), findsOneWidget);
  });

  testWidgets(
    'pay screen shows pending disbursement state instead of payment form',
    (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            loanProvider.overrideWith(
              (ref) => _FakeLoanNotifier(
                const LoanState(
                  portalState: 'released_pending_disbursement',
                  statusMessage:
                      'Your device has been released, but your loan account is still being prepared.',
                  releaseContext: releaseContext,
                ),
              ),
            ),
            paymentProvider.overrideWith(
              (ref) => _FakePaymentNotifier(const PaymentState()),
            ),
          ],
          child: const MaterialApp(home: PayScreen()),
        ),
      );

      await tester.pump();

      expect(find.text('Akaunti ya Malipo Inaandaliwa'), findsOneWidget);
      expect(find.text('Tuma Malipo'), findsNothing);
      expect(find.text('Kila wiki'), findsOneWidget);
    },
  );
}
