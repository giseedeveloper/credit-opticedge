import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/providers/kyc_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('KycNotifier step 2 validation', () {
    test('blocks identity step before device setup creates a draft', () async {
      final notifier = KycNotifier();

      final saved = await notifier.submitStep2();

      expect(saved, isFalse);
      expect(notifier.state.error, isNotNull);
      expect(notifier.state.isSubmitting, isFalse);
    });

    test('blocks identity step when face match is not passed', () async {
      final notifier = KycNotifier()
        ..update(
          (state) => state.copyWith(customerId: 'customer-123'),
        );

      final saved = await notifier.submitStep2();

      expect(saved, isFalse);
      expect(notifier.state.error, contains('Thibitisha uso'));
      expect(notifier.state.isSubmitting, isFalse);
    });
  });

  group('KycNotifier retry routing', () {
    test('retryLastFailedSubmission is false when no pending step', () async {
      final notifier = KycNotifier();

      final saved = await notifier.retryLastFailedSubmission();

      expect(saved, isFalse);
    });
  });
}
