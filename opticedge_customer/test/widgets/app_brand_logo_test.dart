import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_customer/widgets/common/app_brand_logo.dart';

void main() {
  testWidgets('AppBrandLogo renders brand image', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: AppBrandLogo(size: 48),
        ),
      ),
    );

    expect(find.byType(AppBrandLogo), findsOneWidget);
    expect(find.byType(Image), findsOneWidget);
  });
}
