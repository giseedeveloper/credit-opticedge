import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/widgets/kyc/signature_pad.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('SignaturePadController exports a PNG data URL after drawing', () async {
    final controller = SignaturePadController();

    controller.startStroke(const Offset(10, 10));
    controller.appendStrokePoint(const Offset(40, 18));
    controller.appendStrokePoint(const Offset(90, 34));
    controller.endStroke();

    final dataUrl = await controller.exportAsDataUrl();

    expect(controller.hasSignature, isTrue);
    expect(dataUrl, isNotNull);
    expect(dataUrl, startsWith('data:image/png;base64,'));
  });
}
