import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/models/customer_model.dart';

void main() {
  test('CustomerDetail parses draft resume metadata and string payment values',
      () {
    final detail = CustomerDetail.fromJson({
      'id': 'cust-1',
      'full_name': 'Asha Moshi',
      'first_name': 'Asha',
      'last_name': 'Moshi',
      'phone': '+255712345678',
      'branch': {'id': 'branch-1', 'name': 'Kariakoo Branch'},
      'vendor': {'id': 'vendor-1', 'name': 'Sinza Dealer'},
      'device': {
        'brand_id': 'brand-1',
        'phone_model_id': 'model-1',
        'inventory_unit_id': 'unit-1',
      },
      'income': const {},
      'nok': const {},
      'consent': const {},
      'phone_metadata': const {},
      'photos': const {},
      'kyc_status': 'draft',
      'registered_at': '2026-04-12 10:00:00',
      'can_resume_draft': true,
      'resume_step': '5',
      'payment': {
        'status': 'completed',
        'amount': '2000.00',
        'is_completed': true,
      },
    });

    expect(detail.vendor?['name'], 'Sinza Dealer');
    expect(detail.canResumeDraft, isTrue);
    expect(detail.resumeStep, 5);
    expect(detail.payment?.amount, 2000);
  });
}
