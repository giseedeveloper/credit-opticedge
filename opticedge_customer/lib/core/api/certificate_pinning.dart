import 'dart:io';

import 'package:crypto/crypto.dart';
import '../../config/constants.dart';
import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter/foundation.dart';

/// Optional TLS certificate pinning for native customer app builds.
class CertificatePinning {
  CertificatePinning._();

  static const String _pinsEnv = String.fromEnvironment(
    'API_CERT_SHA256',
    defaultValue: AppConstants.productionCertSha256,
  );

  static List<String> get configuredPins => _parsePins(_pinsEnv);

  static void applyIfConfigured(Dio dio) {
    if (kIsWeb) {
      return;
    }

    final pins = configuredPins;
    if (pins.isEmpty) {
      return;
    }

    final adapter = dio.httpClientAdapter;
    if (adapter is! IOHttpClientAdapter) {
      return;
    }

    adapter.createHttpClient = () {
      final client = HttpClient();
      client.badCertificateCallback = (cert, host, port) {
        final fingerprint = sha256.convert(cert.der).bytes
            .map((b) => b.toRadixString(16).padLeft(2, '0'))
            .join();
        return pins.contains(fingerprint);
      };
      return client;
    };
  }

  static List<String> _parsePins(String raw) {
    if (raw.trim().isEmpty) {
      return [];
    }

    return raw
        .split(',')
        .map((p) => p.replaceAll(':', '').trim().toLowerCase())
        .where((p) => p.length >= 32)
        .toList();
  }
}
