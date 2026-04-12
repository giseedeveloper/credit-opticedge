import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/core/utils/kyc_upload_limits.dart';

void main() {
  test('validateOptional allows null file', () {
    expect(KycUploadLimits.validateOptional(null, 'Photo'), isNull);
  });

  test('validateOptional rejects oversized files', () async {
    final dir = Directory.systemTemp.createTempSync('kyc_ul_');
    final f = File('${dir.path}/big.bin');
    await f.writeAsBytes(List.filled(KycUploadLimits.maxBytesPerPhoto + 1, 0));
    final msg = KycUploadLimits.validateOptional(f, 'ID photo');
    expect(msg, isNotNull);
    expect(msg, contains('ID photo'));
    await dir.delete(recursive: true);
  });
}
