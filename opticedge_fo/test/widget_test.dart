import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/widgets/kyc/signature_pad.dart';

void main() {
  testWidgets('Signature pad renders its capture prompt',
      (WidgetTester tester) async {
    final controller = SignaturePadController();

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: SignaturePad(
            controller: controller,
          ),
        ),
      ),
    );

    expect(find.text('Sign here with your finger'), findsOneWidget);
    expect(find.byType(SignaturePad), findsOneWidget);
  });
}
