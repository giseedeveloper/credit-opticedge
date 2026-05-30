import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import 'dart:async';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../models/customer_profile.dart';
import '../services/biometric_service.dart';
import '../storage/secure_storage.dart';
import '../../config/constants.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState {
  final AuthStatus status;
  final CustomerProfile? customer;
  final String? error;
  final bool isLoading;

  const AuthState({
    this.status = AuthStatus.unknown,
    this.customer,
    this.error,
    this.isLoading = false,
  });

  AuthState copyWith({
    AuthStatus? status,
    CustomerProfile? customer,
    String? error,
    bool? isLoading,
  }) {
    return AuthState(
      status: status ?? this.status,
      customer: customer ?? this.customer,
      error: error,
      isLoading: isLoading ?? this.isLoading,
    );
  }
}

/// Result of the check-phone step.
class PhoneCheckResult {
  final String eligibility;
  final bool hasPin;
  final String customerName;
  final String? kycStatus;
  final int? kycStage;
  final String? portalMessage;

  const PhoneCheckResult({
    required this.eligibility,
    required this.hasPin,
    required this.customerName,
    this.kycStatus,
    this.kycStage,
    this.portalMessage,
  });

  bool get isKycInProgress => eligibility == 'kyc_in_progress';

  bool get isPortalActive => eligibility == 'portal_active';
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier() : super(const AuthState()) {
    _sessionExpiredSub = ApiClient.sessionExpiredStream.listen((_) async {
      await SecureStorageService.instance.clearAll();
      state = const AuthState(
        status: AuthStatus.unauthenticated,
        error: 'Session expired. Please login again.',
      );
    });
  }

  late final StreamSubscription<void> _sessionExpiredSub;

  Future<void> checkAuth() async {
    final token = await SecureStorageService.instance.getToken();
    if (token == null) {
      state = state.copyWith(status: AuthStatus.unauthenticated);
      return;
    }
    try {
      final res = await ApiClient.instance.get('/profile');
      final data = res.data['data'] as Map<String, dynamic>;
      final customer = CustomerProfile.fromJson(data);
      await SecureStorageService.instance.saveCustomer(data);
      state = state.copyWith(
        status: AuthStatus.authenticated,
        customer: customer,
      );
    } catch (e) {
      if (_isUnauthorizedError(e)) {
        await SecureStorageService.instance.clearAll();
        state = state.copyWith(status: AuthStatus.unauthenticated);
        return;
      }

      // Keep valid local session during transient network/server errors.
      final cached = await SecureStorageService.instance.getCustomer();
      if (cached != null) {
        state = state.copyWith(
          status: AuthStatus.authenticated,
          customer: CustomerProfile.fromJson(cached),
          error: ApiClient.parseError(e),
        );
      } else {
        state = state.copyWith(
          status: AuthStatus.unknown,
          error: ApiClient.parseError(e),
        );
      }
    }
  }

  /// Step 1: Check if phone exists and whether customer has a PIN.
  Future<PhoneCheckResult> checkPhone(String phone) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final res = await ApiClient.instance.post(
        '/check-phone',
        data: {'phone': phone},
      );
      final data = res.data['data'] as Map<String, dynamic>;
      state = state.copyWith(isLoading: false);
      return PhoneCheckResult(
        eligibility: data['eligibility']?.toString() ?? 'portal_active',
        hasPin: data['has_pin'] as bool? ?? false,
        customerName: data['customer_name'] as String? ?? '',
        kycStatus: data['kyc_status']?.toString(),
        kycStage: (data['kyc_stage'] as num?)?.toInt(),
        portalMessage: data['portal_message']?.toString(),
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: ApiClient.parseError(e));
      rethrow;
    }
  }

  /// Step 2a: Set PIN for first-time customer (auto-login after).
  Future<bool> setPin(String phone, String pin, String confirm) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final res = await ApiClient.instance.post(
        '/set-pin',
        data: {'phone': phone, 'new_pin': pin, 'new_pin_confirmation': confirm},
      );
      return _handleLoginResponse(res.data);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: ApiClient.parseError(e));
      return false;
    }
  }

  /// Step 2b: Login with phone + PIN.
  Future<bool> login(String phone, String pin) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final res = await ApiClient.instance.post(
        '/login',
        data: {'phone': phone, 'pin': pin},
      );
      return _handleLoginResponse(res.data);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: ApiClient.parseError(e));
      return false;
    }
  }

  Future<bool> _handleLoginResponse(dynamic responseData) async {
    final resData = responseData['data'] as Map<String, dynamic>;
    final token = resData['token'] as String;
    final customerRaw = resData['customer'] as Map<String, dynamic>;

    await SecureStorageService.instance.saveToken(token);
    await SecureStorageService.instance.saveCustomer(customerRaw);

    final customer = CustomerProfile.fromJson(customerRaw);
    state = state.copyWith(
      status: AuthStatus.authenticated,
      customer: customer,
      isLoading: false,
    );
    return true;
  }

  bool _isUnauthorizedError(dynamic error) {
    if (error is! DioException) {
      return false;
    }
    final code = error.response?.statusCode;
    return code == 401 || code == 403;
  }

  Future<void> refreshProfile() async {
    try {
      final res = await ApiClient.instance.get('/profile');
      final data = res.data['data'] as Map<String, dynamic>;
      final customer = CustomerProfile.fromJson(data);
      await SecureStorageService.instance.saveCustomer(data);
      state = state.copyWith(customer: customer);
    } catch (_) {}
  }

  Future<void> logout() async {
    try {
      await ApiClient.instance.post('/logout');
    } catch (_) {}
    await SecureStorageService.instance.clearAll();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }

  Future<bool> isBiometricUnlockEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    final token = await SecureStorageService.instance.getToken();
    return prefs.getBool(AppConstants.biometricEnabledKey) == true &&
        token != null;
  }

  Future<void> setBiometricUnlockEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.biometricEnabledKey, enabled);
  }

  /// Unlock with fingerprint/Face ID when a session token exists.
  Future<bool> unlockWithBiometrics() async {
    if (!await isBiometricUnlockEnabled()) {
      state = state.copyWith(
        error: 'Washa kuingia kwa alama za kidole kwenye profaili kwanza.',
      );
      return false;
    }
    final ok = await BiometricService.instance.authenticate(
      reason: 'Thibitisha ili kuingia kwenye akaunti yako',
    );
    if (!ok) {
      return false;
    }
    await checkAuth();
    return state.status == AuthStatus.authenticated;
  }

  @override
  void dispose() {
    _sessionExpiredSub.cancel();
    super.dispose();
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier();
});
