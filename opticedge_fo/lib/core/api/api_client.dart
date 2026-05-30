import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'dart:async';
import '../../config/constants.dart';
import '../storage/secure_storage.dart';
import 'certificate_pinning.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();
  static final StreamController<void> _sessionExpiredController =
      StreamController<void>.broadcast();
  static Stream<void> get sessionExpiredStream =>
      _sessionExpiredController.stream;

  late Dio _dio;
  bool _initialized = false;

  Dio get dio {
    _ensureReady();
    return _dio;
  }

  void init() {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConstants.baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 60),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );
    CertificatePinning.applyIfConfigured(_dio);
    _initialized = true;

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await SecureStorageService.instance.getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          final code = error.response?.statusCode;
          if (code == 401 || code == 403) {
            _sessionExpiredController.add(null);
          }
          handler.next(error);
        },
      ),
    );
  }

  String get activeBaseUrl {
    _ensureReady();
    return _dio.options.baseUrl;
  }

  void _ensureReady() {
    if (!_initialized) {
      init();
      return;
    }

    final expectedBaseUrl = AppConstants.baseUrl;

    if (_dio.options.baseUrl != expectedBaseUrl) {
      _dio.options.baseUrl = expectedBaseUrl;
    }
  }

  Future<Response> get(String path,
      {Map<String, dynamic>? queryParameters}) async {
    _ensureReady();
    return _dio.get(path, queryParameters: queryParameters);
  }

  Future<Response> post(String path, {dynamic data}) async {
    _ensureReady();
    return _dio.post(
      path,
      data: data,
      options: data == null ? null : _sendOptions(),
    );
  }

  Future<Response> postForm(String path, FormData formData) async {
    _ensureReady();
    return _dio.post(
      path,
      data: formData,
      options: _sendOptions(contentType: 'multipart/form-data'),
    );
  }

  Options _sendOptions({String? contentType}) {
    return Options(
      contentType: contentType,
      sendTimeout: kIsWeb ? null : const Duration(seconds: 90),
    );
  }

  /// User-facing text for transport failures (short, no URLs or dev jargon).
  String _friendlyConnectionError(DioException error) {
    final msg = (error.message ?? '').toLowerCase();
    final underlying = (error.error?.toString() ?? '').toLowerCase();
    final combined = '$msg $underlying';

    if (combined.contains('connection refused') ||
        combined.contains('failed to fetch') ||
        combined.contains('xmlhttprequest error') ||
        combined.contains('err_connection_refused')) {
      return 'No connection to the service. Check your internet or try again later.';
    }
    if (combined.contains('closed before full header') ||
        combined.contains('connection terminated') ||
        combined.contains('connection closed')) {
      return 'Upload was interrupted. Try again with a smaller file or a stronger connection.';
    }
    if (combined.contains('failed host lookup') ||
        combined.contains('nodename nor servname') ||
        combined.contains('name or service not known')) {
      return 'Internet looks unstable. Check Wi‑Fi or mobile data, then try again.';
    }
    if (combined.contains('certificate') ||
        combined.contains('ssl') ||
        combined.contains('handshake') ||
        combined.contains('cert_verify')) {
      return 'Secure connection failed. Check this device\'s date and time, then try again.';
    }
    if (combined.contains('network is unreachable') ||
        combined.contains('no route to host')) {
      return 'You appear to be offline. Turn on Wi‑Fi or mobile data.';
    }
    if (combined.contains('connection failed') ||
        combined.contains('connection errored') ||
        combined.contains('socketexception')) {
      return 'Connection failed. Check your internet or VPN, then try again.';
    }

    return 'Something went wrong with the network. Please try again.';
  }

  /// Maps known English API messages to short Swahili hints for FO users.
  String _maybeLocalizeApiMessage(String message) {
    final l = message.toLowerCase();
    if (l.contains('upload the id front photo before')) {
      return 'Picha ya mbele ya kitambulisho lazima iwe kwenye seva kabla ya uthibitishaji wa uso. Tumia kitufe cha Endelea kwenye hatua ya kitambulisho, au rudi upakie ID kisha ujaribu tena.';
    }
    if (l.contains('id front photo is missing from storage')) {
      return 'Picha ya ID haipo kwenye hifadhi. Pakia tena picha ya mbele ya kitambulisho kwenye fomu.';
    }
    return message;
  }

  String parseError(dynamic error) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map) {
        if (data['message'] != null) {
          return _maybeLocalizeApiMessage(data['message'].toString());
        }
        if (data['errors'] is Map) {
          final errors = data['errors'] as Map;
          return errors.values.first is List
              ? (errors.values.first as List).first.toString()
              : errors.values.first.toString();
        }
      }
      if (data is String && data.trim().isNotEmpty) {
        return data;
      }
      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.sendTimeout ||
          error.type == DioExceptionType.receiveTimeout) {
        return 'Request timed out. Check your connection or try again with a smaller upload.';
      }
      if (error.type == DioExceptionType.connectionError) {
        return _friendlyConnectionError(error);
      }
      if (error.response?.statusCode == 401) {
        return 'Session expired. Please login again.';
      }
      if (error.response?.statusCode == 403) {
        return 'Access denied. You don\'t have permission.';
      }
      if (error.response?.statusCode == 404) {
        return 'Not found (404). This action may be unavailable or the link is wrong.';
      }
      if (error.response?.statusCode == 413) {
        return 'Upload is too large. Retake the handover image more closely and try again.';
      }
      if (error.response?.statusCode == 422) {
        return 'Validation failed. Check your inputs.';
      }
      if (error.response?.statusCode != null) {
        return 'Something went wrong (${error.response?.statusCode}). Please try again.';
      }
    }
    return 'Something went wrong. Please try again.';
  }
}
