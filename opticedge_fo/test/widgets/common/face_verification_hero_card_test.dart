import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/widgets/common/face_verification_hero_card.dart';

void main() {
  testWidgets('FaceVerificationHeroCard shows live scan title when not verified',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: Padding(
            padding: EdgeInsets.all(16),
            child: FaceVerificationHeroCard(
              customerId: 'cust-1',
              idFrontUrl: '/tmp/id-front.jpg',
              verified: false,
            ),
          ),
        ),
      ),
    );

    expect(find.text('Skani ya uso (live)'), findsOneWidget);
    expect(find.textContaining('Blink'), findsWidgets);
  });

  testWidgets('FaceVerificationHeroCard shows completed copy when verified',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: FaceVerificationHeroCard(
            customerId: 'cust-1',
            idFrontUrl: '/tmp/id-front.jpg',
            verified: true,
            matchScore: 0.91,
          ),
        ),
      ),
    );

    expect(find.textContaining('umekamilika'), findsOneWidget);
    expect(find.textContaining('91%'), findsOneWidget);
  });
}
