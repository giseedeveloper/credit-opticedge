import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/screens/dashboard/dashboard_screen.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  tearDown(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  testWidgets('Dashboard renders hero search and quick actions',
      (WidgetTester tester) async {
    FlutterSecureStorage.setMockInitialValues({});

    await tester.pumpWidget(
      const ProviderScope(
        child: MaterialApp(
          home: DashboardScreen(),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.byType(DashboardScreen), findsOneWidget);
    expect(find.text('Quick Actions'), findsOneWidget);
    expect(find.byIcon(Icons.search_rounded), findsWidgets);
  });
}
