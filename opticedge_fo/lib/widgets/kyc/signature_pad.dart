import 'dart:convert';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';

import '../../config/constants.dart';

class SignaturePadController extends ChangeNotifier {
  final List<List<Offset>> _strokes = <List<Offset>>[];

  List<List<Offset>> get strokes => List<List<Offset>>.unmodifiable(_strokes);

  bool get hasSignature => _strokes.any((stroke) => stroke.isNotEmpty);

  void startStroke(Offset point) {
    _strokes.add([point]);
    notifyListeners();
  }

  void appendStrokePoint(Offset point) {
    if (_strokes.isEmpty) {
      startStroke(point);

      return;
    }

    _strokes.last.add(point);
    notifyListeners();
  }

  void clear() {
    if (_strokes.isEmpty) {
      return;
    }

    _strokes.clear();
    notifyListeners();
  }

  Future<String?> exportAsDataUrl({
    Size size = const Size(640, 220),
  }) async {
    if (!hasSignature) {
      return null;
    }

    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    final bounds = Offset.zero & size;

    canvas.drawRect(bounds, Paint()..color = Colors.white);

    _SignaturePainter(
      strokes: _strokes,
      strokeColor: AppConstants.textPrimary,
      showGuide: false,
    ).paint(canvas, size);

    final image = await recorder.endRecording().toImage(
          size.width.round(),
          size.height.round(),
        );
    final bytes = await image.toByteData(format: ui.ImageByteFormat.png);

    if (bytes == null) {
      return null;
    }

    final buffer = bytes.buffer.asUint8List();

    return 'data:image/png;base64,${base64Encode(buffer)}';
  }
}

class SignaturePad extends StatelessWidget {
  final SignaturePadController controller;
  final double height;

  const SignaturePad({
    super.key,
    required this.controller,
    this.height = 190,
  });

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: controller,
      builder: (context, _) {
        return Container(
          height: height,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: controller.hasSignature
                  ? AppConstants.success
                  : AppConstants.border,
              width: controller.hasSignature ? 1.4 : 1,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 18,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(17),
            child: GestureDetector(
              onPanStart: (details) {
                controller.startStroke(details.localPosition);
              },
              onPanUpdate: (details) {
                controller.appendStrokePoint(details.localPosition);
              },
              child: CustomPaint(
                size: Size.infinite,
                painter: _SignaturePainter(
                  strokes: controller.strokes,
                  strokeColor: AppConstants.textPrimary,
                  showGuide: true,
                ),
                child: controller.hasSignature
                    ? null
                    : const Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              Icons.draw_outlined,
                              size: 28,
                              color: AppConstants.textHint,
                            ),
                            SizedBox(height: 8),
                            Text(
                              'Sign here with your finger',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: AppConstants.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
              ),
            ),
          ),
        );
      },
    );
  }
}

class _SignaturePainter extends CustomPainter {
  final List<List<Offset>> strokes;
  final Color strokeColor;
  final bool showGuide;

  const _SignaturePainter({
    required this.strokes,
    required this.strokeColor,
    required this.showGuide,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final gridPaint = Paint()
      ..color = AppConstants.borderLight
      ..strokeWidth = 1;

    if (showGuide) {
      for (var index = 1; index <= 3; index++) {
        final y = size.height * (index / 4);
        canvas.drawLine(Offset(0, y), Offset(size.width, y), gridPaint);
      }

      final baselinePaint = Paint()
        ..color = AppConstants.primary.withValues(alpha: 0.35)
        ..strokeWidth = 1.5;
      final baselineY = size.height - 32;
      canvas.drawLine(
        Offset(22, baselineY),
        Offset(size.width - 22, baselineY),
        baselinePaint,
      );
    }

    final paint = Paint()
      ..color = strokeColor
      ..strokeWidth = 2.6
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..style = PaintingStyle.stroke
      ..isAntiAlias = true;

    for (final stroke in strokes) {
      if (stroke.isEmpty) {
        continue;
      }

      if (stroke.length == 1) {
        canvas.drawCircle(stroke.first, 1.2, paint..style = PaintingStyle.fill);
        paint.style = PaintingStyle.stroke;
        continue;
      }

      final path = Path()..moveTo(stroke.first.dx, stroke.first.dy);
      for (var index = 1; index < stroke.length; index++) {
        path.lineTo(stroke[index].dx, stroke[index].dy);
      }
      canvas.drawPath(path, paint);
    }
  }

  @override
  bool shouldRepaint(covariant _SignaturePainter oldDelegate) {
    return oldDelegate.strokes != strokes ||
        oldDelegate.strokeColor != strokeColor ||
        oldDelegate.showGuide != showGuide;
  }
}
