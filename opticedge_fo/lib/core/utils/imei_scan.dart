import 'dart:io';

import 'package:google_mlkit_barcode_scanning/google_mlkit_barcode_scanning.dart';
import 'package:google_mlkit_text_recognition/google_mlkit_text_recognition.dart';

class ImeiScanResult {
  final String imei;
  final String source; // 'barcode' | 'ocr'

  const ImeiScanResult({required this.imei, required this.source});
}

class ImeiScan {
  static final _imeiRegex = RegExp(r'(?<!\d)(\d{15})(?!\d)');

  static Future<ImeiScanResult?> extractImei(File imageFile) async {
    final input = InputImage.fromFile(imageFile);

    // 1) Barcode first (device boxes often have IMEI barcode).
    final barcodeScanner = BarcodeScanner(
      formats: [BarcodeFormat.all],
    );
    try {
      final barcodes = await barcodeScanner.processImage(input);
      for (final code in barcodes) {
        final raw = code.rawValue ?? '';
        final imei = _pickBestImeiFromText(raw);
        if (imei != null) {
          return ImeiScanResult(imei: imei, source: 'barcode');
        }
      }
    } finally {
      await barcodeScanner.close();
    }

    // 2) OCR fallback.
    final textRecognizer = TextRecognizer(script: TextRecognitionScript.latin);
    try {
      final recognized = await textRecognizer.processImage(input);
      final imei = _pickBestImeiFromText(recognized.text);
      if (imei != null) {
        return ImeiScanResult(imei: imei, source: 'ocr');
      }
    } finally {
      await textRecognizer.close();
    }

    return null;
  }

  static String? _pickBestImeiFromText(String text) {
    // Extract candidates and return the first valid IMEI (Luhn).
    final matches = _imeiRegex.allMatches(text.replaceAll(' ', ''));
    for (final m in matches) {
      final candidate = m.group(1);
      if (candidate == null) continue;
      if (_isValidImei(candidate)) {
        return candidate;
      }
    }
    return null;
  }

  static bool _isValidImei(String imei) {
    if (imei.length != 15) return false;
    if (!RegExp(r'^\d{15}$').hasMatch(imei)) return false;

    // Luhn algorithm (IMEI uses Luhn checksum).
    int sum = 0;
    for (int i = 0; i < 15; i++) {
      int digit = int.parse(imei[i]);
      final isEvenFromRight = ((14 - i) % 2 == 1);
      if (isEvenFromRight) {
        digit *= 2;
        if (digit > 9) digit -= 9;
      }
      sum += digit;
    }
    return sum % 10 == 0;
  }
}

