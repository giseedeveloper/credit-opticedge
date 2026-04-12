import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../config/constants.dart';

class SecureStorageService {
  SecureStorageService._();
  static final SecureStorageService instance = SecureStorageService._();

  final _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  Future<void> saveToken(String token) async {
    await _storage.write(key: AppConstants.tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return _storage.read(key: AppConstants.tokenKey);
  }

  Future<void> deleteToken() async {
    await _storage.delete(key: AppConstants.tokenKey);
  }

  Future<void> saveCustomer(Map<String, dynamic> customer) async {
    await _storage.write(key: AppConstants.customerKey, value: jsonEncode(customer));
  }

  Future<Map<String, dynamic>?> getCustomer() async {
    final raw = await _storage.read(key: AppConstants.customerKey);
    if (raw == null) return null;
    return jsonDecode(raw) as Map<String, dynamic>;
  }

  Future<void> deleteCustomer() async {
    await _storage.delete(key: AppConstants.customerKey);
  }

  Future<void> clearAll() async {
    await _storage.deleteAll();
  }
}
