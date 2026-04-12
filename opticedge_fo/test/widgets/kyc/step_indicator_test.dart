import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/widgets/kyc/step_indicator.dart';

void main() {
  testWidgets('Step indicator renders step labels and active state',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: StepIndicator(
            totalSteps: 3,
            currentStep: 2,
            labels: ['Device', 'Identity', 'Submit'],
          ),
        ),
      ),
    );

    expect(find.text('Device'), findsOneWidget);
    expect(find.text('Identity'), findsOneWidget);
    expect(find.text('Submit'), findsOneWidget);
    expect(find.text('Step 2'), findsOneWidget);
    expect(find.byIcon(Icons.check_rounded), findsOneWidget);
  });
}
