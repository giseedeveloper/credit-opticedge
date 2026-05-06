import 'package:dio/dio.dart';
import 'dart:async';
import '../../config/constants.dart';
import '../storage/secure_storage.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();
  static final StreamController<void> _sessionExpiredController =
      StreamController<void>.broadcast();
  static Stream<void> get sessionExpiredStream =>
      _sessionExpiredController.stream;

  late final Dio _dio;
  bool _initialized = false;

  Future<void> init() async {
    if (_initialized) return;

    _dio = Dio(
      BaseOptions(
        baseUrl: AppConstants.baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onError: (error, handler) {
          final statusCode = error.response?.statusCode;
          if (statusCode == 401 || statusCode == 403) {
            _sessionExpiredController.add(null);
          }
          handler.next(error);
        },
      ),
    );

    _initialized = true;
  }

  Future<Options> _authOptions() async {
    final token = await SecureStorageService.instance.getToken();
    return Options(
      headers: token != null ? {'Authorization': 'Bearer $token'} : null,
    );
  }

  Future<Response> get(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) async {
    await _ensureInit();
    final opts = await _authOptions();
    return _dio.get(path, queryParameters: queryParameters, options: opts);
  }

  Future<Response> post(String path, {dynamic data}) async {
    await _ensureInit();
    final opts = await _authOptions();
    return _dio.post(path, data: data, options: opts);
  }

  Future<Response> put(String path, {dynamic data}) async {
    await _ensureInit();
    final opts = await _authOptions();
    return _dio.put(path, data: data, options: opts);
  }

  Future<void> _ensureInit() async {
    if (!_initialized) await init();
  }

  /// Parse error message from API response or DioException.
  static String parseError(dynamic error) {
    if (error is DioException) {
      if (error.response?.data is Map) {
        final data = error.response!.data as Map;
        if (data['message'] != null) return data['message'].toString();
        if (data['errors'] is Map) {
          final errors = data['errors'] as Map;
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
        }
      }
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return 'Connection timed out. Please try again.';
        case DioExceptionType.connectionError:
          return 'No internet connection. Check your network.';
        default:
          return 'Something went wrong. Please try again.';
      }
    }
    return error.toString();
  }
}
