import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/utils/face_match_reason_text.dart';

void main() {
  test('maps id_front NIDA blur reason to Swahili guidance', () {
    final text = localizedFaceMatchReason('id_front:image_blurry');
    expect(text, contains('NIDA'));
    expect(text, contains('blur'));
  });

  test('maps id_front no face on right side', () {
    final text = localizedFaceMatchReason('id_front:no_face_detected');
    expect(text, contains('kulia'));
  });

  test('maps match low similarity', () {
    final text = localizedFaceMatchReason('match:low_similarity');
    expect(text, contains('haulingani'));
  });

  test('maps headshot too dark', () {
    final text = localizedFaceMatchReason('headshot:image_too_dark');
    expect(text, contains('giza'));
  });
}
