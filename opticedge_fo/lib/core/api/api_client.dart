import 'package:dio/dio.dart';
import '../../config/constants.dart';
import '../storage/secure_storage.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  late final Dio _dio;

  Dio get dio => _dio;

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

  Future<Response> get(String path,
      {Map<String, dynamic>? queryParameters}) async {
    return _dio.get(path, queryParameters: queryParameters);
  }

  Future<Response> post(String path, {dynamic data}) async {
    return _dio.post(path, data: data);
  }

  Future<Response> postForm(String path, FormData formData) async {
    return _dio.post(
      path,
      data: formData,
      options: Options(contentType: 'multipart/form-data'),
    );
  }

  String parseError(dynamic error) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map) {
        if (data['message'] != null) return data['message'].toString();
        if (data['errors'] is Map) {
          final errors = data['errors'] as Map;
          return errors.values.first is List
              ? (errors.values.first as List).first.toString()
              : errors.values.first.toString();
        }
      }
      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.receiveTimeout) {
        return 'Connection timeout. Check your internet.';
      }
      if (error.type == DioExceptionType.connectionError) {
        return 'No internet connection.';
      }
      if (error.response?.statusCode == 401) return 'Session expired. Please login again.';
      if (error.response?.statusCode == 403) return 'Access denied. You don\'t have permission.';
      if (error.response?.statusCode == 422) return 'Validation failed. Check your inputs.';
    }
    return 'Something went wrong. Please try again.';
  }
}
