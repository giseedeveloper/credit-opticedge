import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/widgets/common/app_logo.dart';

void main() {
  testWidgets('AppLogo renders brand asset', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: AppLogo(size: 80),
        ),
      ),
    );

    expect(find.byType(AppLogo), findsOneWidget);
    expect(find.byType(Image), findsOneWidget);
  });
}
