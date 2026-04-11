import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/config/constants.dart';
import 'package:opticedge_fo/core/api/api_client.dart';

void main() {
  test('ApiClient exposes the active API base URL after initialization', () {
    ApiClient.instance.init();

    expect(ApiClient.instance.activeBaseUrl, AppConstants.baseUrl);
  });

  test('ApiClient surfaces interrupted upload errors clearly', () {
    final error = DioException(
      requestOptions: RequestOptions(path: '/kyc/application/1/step7'),
      type: DioExceptionType.connectionError,
      message: 'Connection closed before full header was received',
    );

    expect(
      ApiClient.instance.parseError(error),
      'Upload was interrupted before the server finished reading it. Retake the handover image more closely and try again.',
    );
  });

  test(
      'ApiClient maps connection refused errors to a backend reachability hint',
      () {
    ApiClient.instance.init();

    final error = DioException(
      requestOptions: RequestOptions(path: '/kyc/application/device/brands'),
      type: DioExceptionType.connectionError,
      message: 'XMLHttpRequest error. net::ERR_CONNECTION_REFUSED',
    );

    expect(
      ApiClient.instance.parseError(error),
      'Cannot reach the API server at ${AppConstants.baseUrl}. Check that the backend is running, then try again.',
    );
  });

  test('ApiClient maps 413 responses to a size-specific upload message', () {
    final error = DioException(
      requestOptions: RequestOptions(path: '/kyc/application/1/step7'),
      response: Response(
        requestOptions: RequestOptions(path: '/kyc/application/1/step7'),
        statusCode: 413,
      ),
      type: DioExceptionType.badResponse,
    );

    expect(
      ApiClient.instance.parseError(error),
      'Upload is too large. Retake the handover image more closely and try again.',
    );
  });
}
