import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/models/customer_model.dart';
import 'package:opticedge_fo/core/providers/customer_provider.dart';
import 'package:opticedge_fo/screens/customers/customer_detail_screen.dart';

void main() {
  testWidgets('draft customer detail shows resume action and vendor context',
      (WidgetTester tester) async {
    const detail = CustomerDetail(
      id: 'cust-1',
      fullName: 'Asha Moshi',
      firstName: 'Asha',
      lastName: 'Moshi',
      phone: '+255712345678',
      registeredAt: '2026-04-12 10:00:00',
      branch: {'id': 'branch-1', 'name': 'Kariakoo Branch'},
      vendor: {'id': 'vendor-1', 'name': 'Sinza Dealer'},
      kycStatus: 'draft',
      canResumeDraft: true,
      resumeStep: 5,
    );

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          customerDetailProvider('cust-1').overrideWith((ref) async => detail),
        ],
        child: const MaterialApp(
          home: CustomerDetailScreen(customerId: 'cust-1'),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 100));

    expect(find.text('Resume Draft'), findsOneWidget);
    expect(find.text('Vendor / Store'), findsOneWidget);
    expect(find.text('Sinza Dealer'), findsOneWidget);
  });
}
