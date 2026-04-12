import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:opticedge_fo/config/app_icon_assets.dart';
import 'package:opticedge_fo/widgets/common/app_color_icon.dart';

void main() {
  testWidgets('AppColorIcon renders an SVG asset widget',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: AppColorIcon(
            assetName: AppIconAssets.dashboard,
            size: 28,
            semanticsLabel: 'Dashboard icon',
          ),
        ),
      ),
    );

    expect(find.byType(AppColorIcon), findsOneWidget);
    expect(find.byType(SvgPicture), findsOneWidget);
  });
}
