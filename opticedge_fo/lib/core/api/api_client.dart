import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../../config/constants.dart';
import '../storage/secure_storage.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

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

  String parseError(dynamic error) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map) {
        if (data['message'] != null) {
          return data['message'].toString();
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
        return 'Request took too long. Try again with a smaller handover image or a stronger connection.';
      }
      if (error.type == DioExceptionType.connectionError) {
        final message = (error.message ?? '').toLowerCase();
        final activeBaseUrl =
            _initialized ? _dio.options.baseUrl : AppConstants.baseUrl;
        if (message.contains('connection refused') ||
            message.contains('failed to fetch') ||
            message.contains('xmlhttprequest error')) {
          return 'Cannot reach the API server at $activeBaseUrl. Check that the backend is running, then try again.';
        }
        if (message.contains('closed before full header') ||
            message.contains('connection terminated') ||
            message.contains('connection closed')) {
          return 'Upload was interrupted before the server finished reading it. Retake the handover image more closely and try again.';
        }
        if (error.message != null && error.message!.trim().isNotEmpty) {
          return 'Network issue: ${error.message}';
        }

        return 'No internet connection.';
      }
      if (error.response?.statusCode == 401) {
        return 'Session expired. Please login again.';
      }
      if (error.response?.statusCode == 403) {
        return 'Access denied. You don\'t have permission.';
      }
      if (error.response?.statusCode == 413) {
        return 'Upload is too large. Retake the handover image more closely and try again.';
      }
      if (error.response?.statusCode == 422) {
        return 'Validation failed. Check your inputs.';
      }
      if (error.response?.statusCode != null) {
        return 'Request failed (${error.response?.statusCode}). Please try again.';
      }
    }
    return 'Something went wrong. Please try again.';
  }
}
