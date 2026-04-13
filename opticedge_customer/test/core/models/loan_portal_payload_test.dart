import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_customer/core/models/loan_model.dart';
import 'package:opticedge_customer/core/models/schedule_model.dart';

void main() {
  test('loan portal payload parses released pending disbursement context', () {
    final payload = LoanPortalPayload.fromJson({
      'portal_state': 'released_pending_disbursement',
      'portal_message':
          'Your device has been released, but your loan account is still being prepared.',
      'loan': null,
      'release': {
        'asset_release_status': 'released',
        'asset_released_at': '2026-04-13 08:15:00',
        'cash_price': 560000,
        'deposit_amount': 500,
        'preferred_repayment': 'weekly',
        'inventory_unit_id': 'unit-123',
      },
    });

    expect(payload.isReleasedPendingDisbursement, isTrue);
    expect(payload.loan, isNull);
    expect(payload.release?.preferredRepayment, 'weekly');
    expect(payload.release?.cashPrice, 560000);
  });

  test(
    'schedule portal payload parses wrapper response with schedule data',
    () {
      final payload = SchedulePortalPayload.fromJson({
        'portal_state': 'loan_active',
        'portal_message': 'Repayment schedule retrieved.',
        'release': {
          'asset_release_status': 'released',
          'asset_released_at': '2026-04-13 08:15:00',
          'cash_price': 560000,
          'deposit_amount': 500,
          'preferred_repayment': 'weekly',
          'inventory_unit_id': 'unit-123',
        },
        'schedule': {
          'loan_id': 'loan-123',
          'loan_number': 'LN-123',
          'total_installments': 2,
          'paid_installments': 1,
          'next_due': {
            'id': 'schedule-1',
            'installment_number': 2,
            'amount_due': 14500,
            'principal_component': 10000,
            'interest_component': 4500,
            'penalty_component': 0,
            'amount_paid': 0,
            'balance_remaining': 100000,
            'due_date': '2026-04-20',
            'paid_at': null,
            'status': 'pending',
            'days_overdue': 0,
          },
          'schedule': [
            {
              'id': 'schedule-1',
              'installment_number': 2,
              'amount_due': 14500,
              'principal_component': 10000,
              'interest_component': 4500,
              'penalty_component': 0,
              'amount_paid': 0,
              'balance_remaining': 100000,
              'due_date': '2026-04-20',
              'paid_at': null,
              'status': 'pending',
              'days_overdue': 0,
            },
          ],
        },
      });

      expect(payload.isLoanActive, isTrue);
      expect(payload.schedule?.loanNumber, 'LN-123');
      expect(payload.schedule?.schedule, hasLength(1));
      expect(payload.schedule?.nextDue?.amountDue, 14500);
    },
  );
}
