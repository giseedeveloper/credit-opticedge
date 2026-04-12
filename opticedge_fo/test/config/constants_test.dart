import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/config/constants.dart';
import 'package:flutter/material.dart';

void main() {
  test('AppConstants uses production API by default', () {
    final baseUrl = AppConstants.resolveBaseUrl(
      isWeb: true,
      isDebug: true,
      currentUri: Uri.parse('http://localhost:53321/'),
      targetPlatform: TargetPlatform.android,
    );

    expect(baseUrl, 'https://credit.opticedgeafrica.net/api/v1');
  });

  test('AppConstants keeps production API on debug Android by default', () {
    final baseUrl = AppConstants.resolveBaseUrl(
      isWeb: false,
      isDebug: true,
      currentUri: Uri.parse('file:///android_asset/flutter_assets/'),
      targetPlatform: TargetPlatform.android,
    );

    expect(baseUrl, 'https://credit.opticedgeafrica.net/api/v1');
  });

  test('AppConstants prefers configured API base URL override', () {
    final baseUrl = AppConstants.resolveBaseUrl(
      isWeb: true,
      isDebug: true,
      currentUri: Uri.parse('http://localhost:53321/'),
      configuredBaseUrl: 'https://api.example.com/v1',
      targetPlatform: TargetPlatform.android,
    );

    expect(baseUrl, 'https://api.example.com/v1');
  });

  test('AppConstants converts storage URLs into the API media proxy URL', () {
    final resolved = AppConstants.resolveMediaUrl(
      'https://credit.opticedgeafrica.net/storage/kyc/headshot/avatar.jpg',
    );

    expect(
      resolved,
      'https://credit.opticedgeafrica.net/api/v1/public-media?path=kyc%2Fheadshot%2Favatar.jpg',
    );
  });
}
