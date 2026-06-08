import 'dart:io';

import 'package:google_mlkit_barcode_scanning/google_mlkit_barcode_scanning.dart';
import 'package:google_mlkit_text_recognition/google_mlkit_text_recognition.dart';

import 'imei_scan.dart';

class DeviceScanHints {
  final String? modelText;
  final String? modelCode;
  final String? ram;
  final String? storage;
  final String rawText;
  final ImeiScanResult? imei;

  const DeviceScanHints({
    this.modelText,
    this.modelCode,
    this.ram,
    this.storage,
    this.rawText = '',
    this.imei,
  });
}

class DeviceScan {
  static Future<DeviceScanHints> extractHints(File imageFile) async {
    final input = InputImage.fromFile(imageFile);
    final imei = await ImeiScan.extractImei(imageFile);
    final textRecognizer = TextRecognizer(script: TextRecognitionScript.latin);

    try {
      final recognized = await textRecognizer.processImage(input);
      final rawText = recognized.text;
      final modelCode = _extractModelCode(rawText);
      final modelText = _extractLikelyModelLine(rawText);
      final ramStorage = _extractRamStorage(rawText);

      return DeviceScanHints(
        modelText: modelText,
        modelCode: modelCode,
        ram: ramStorage.$1,
        storage: ramStorage.$2,
        rawText: rawText,
        imei: imei,
      );
    } finally {
      await textRecognizer.close();
    }
  }

  static String? _extractModelCode(String rawText) {
    final match = RegExp(
      r'\bSM-[A-Z0-9]{3,10}(?:\/[A-Z0-9]{1,6})?\b',
      caseSensitive: false,
    ).firstMatch(rawText);

    return match?.group(0)?.toUpperCase();
  }

  static String? _extractLikelyModelLine(String rawText) {
    final keywords = [
      'GALAXY',
      'SAMSUNG',
      'INFINIX',
      'TECNO',
      'ITEL',
      'IPHONE',
      'XIAOMI',
      'REDMI',
      'OPPO',
      'VIVO',
      'REALME',
      'NOKIA',
      'HUAWEI',
      'PIXEL',
    ];

    final lines = rawText
        .split(RegExp(r'\r?\n'))
        .map((line) => line.trim())
        .where((line) => line.isNotEmpty)
        .take(80);

    String? best;
    var bestScore = 0;

    for (final line in lines) {
      final upper = line.toUpperCase();
      final score = keywords.where(upper.contains).length;
      if (score > bestScore) {
        bestScore = score;
        best = line;
      }
    }

    return best;
  }

  static (String?, String?) _extractRamStorage(String rawText) {
    final match = RegExp(
      r'(\d{1,2})\s*GB\s*\|\s*(\d{2,4})\s*GB',
      caseSensitive: false,
    ).firstMatch(rawText);

    if (match == null) {
      return (null, null);
    }

    return ('${match.group(1)}GB', '${match.group(2)}GB');
  }
}
