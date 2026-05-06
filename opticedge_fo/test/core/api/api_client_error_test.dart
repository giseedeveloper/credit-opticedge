import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:opticedge_fo/config/constants.dart';
import 'package:opticedge_fo/core/api/api_client.dart';

void main() {
  test('ApiClient exposes the active API base URL after initialization', () {
    ApiClient.instance.init();

    expect(ApiClient.instance.activeBaseUrl, AppConstants.baseUrl);
    expect(ApiClient.instance.dio.options.sendTimeout, isNull);
  });

  test('ApiClient surfaces interrupted upload errors clearly', () {
    final error = DioException(
      requestOptions: RequestOptions(path: '/kyc/application/1/step7'),
      type: DioExceptionType.connectionError,
      message: 'Connection closed before full header was received',
    );

    expect(
      ApiClient.instance.parseError(error),
      'Upload was interrupted. Try again with a smaller file or a stronger connection.',
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
      'No connection to the service. Check your internet or try again later.',
    );
  });

  test(
      'ApiClient maps Dio connection failed boilerplate to a clean login-friendly message',
      () {
    ApiClient.instance.init();

    final error = DioException(
      requestOptions: RequestOptions(path: '/login'),
      type: DioExceptionType.connectionError,
      message:
          'The connection errored: Connection failed This indicates an error which most likely cannot be solved by the library.',
    );

    expect(
      ApiClient.instance.parseError(error),
      'Connection failed. Check your internet or VPN, then try again.',
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

  test('ApiClient localizes face verify ID prerequisite message', () {
    final error = DioException(
      requestOptions:
          RequestOptions(path: '/kyc/application/1/face/verify'),
      response: Response(
        requestOptions:
            RequestOptions(path: '/kyc/application/1/face/verify'),
        statusCode: 422,
        data: const {
          'message':
              'Upload the ID front photo before running face verification.',
        },
      ),
      type: DioExceptionType.badResponse,
    );

    expect(
      ApiClient.instance.parseError(error),
      startsWith('Picha ya mbele'),
    );
  });

  test('ApiClient maps 404 responses to a not-found hint', () {
    final error = DioException(
      requestOptions:
          RequestOptions(path: '/kyc/application/1/face/verify'),
      response: Response(
        requestOptions:
            RequestOptions(path: '/kyc/application/1/face/verify'),
        statusCode: 404,
      ),
      type: DioExceptionType.badResponse,
    );

    expect(
      ApiClient.instance.parseError(error),
      'Not found (404). This action may be unavailable or the link is wrong.',
    );
  });
}
