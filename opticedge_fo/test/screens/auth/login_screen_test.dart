import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/config/constants.dart';
import 'package:opticedge_fo/screens/auth/login_screen.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  tearDown(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  testWidgets('Login screen shows welcome copy and sign in CTA',
      (WidgetTester tester) async {
    FlutterSecureStorage.setMockInitialValues({});

    await tester.pumpWidget(
      const ProviderScope(
        child: MaterialApp(
          home: LoginScreen(),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('Welcome back'), findsOneWidget);
    expect(find.text(AppConstants.appName), findsOneWidget);
    expect(find.text('Fast'), findsOneWidget);
    expect(find.text('Secure'), findsOneWidget);
    expect(find.text('Verified'), findsOneWidget);
    expect(find.text('Sign In'), findsOneWidget);
  });
}
