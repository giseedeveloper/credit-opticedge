import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/providers/kyc_provider.dart';

void main() {
  group('KycNotifier stage 2 validation', () {
    test('blocks customer verification before device setup creates a draft',
        () async {
      final notifier = KycNotifier();

      final saved = await notifier.submitStage2();

      expect(saved, isFalse);
      expect(notifier.state.error, contains('Start with device setup'));
      expect(notifier.state.isSubmitting, isFalse);
    });

    test(
        'blocks customer verification when required evidence photos are absent',
        () async {
      final notifier = KycNotifier()
        ..update((state) => state.copyWith(customerId: 'customer-123'));

      final saved = await notifier.submitStage2();

      expect(saved, isFalse);
      expect(notifier.state.error, contains('ID front photo'));
      expect(notifier.state.error, contains('ID back photo'));
      expect(notifier.state.error, contains('Headshot'));
      expect(notifier.state.isSubmitting, isFalse);
    });
  });
}
