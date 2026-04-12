import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/customer_profile.dart';
import '../storage/secure_storage.dart';

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
  final bool hasPin;
  final String customerName;
  PhoneCheckResult({required this.hasPin, required this.customerName});
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier() : super(const AuthState());

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
    } catch (_) {
      await SecureStorageService.instance.clearAll();
      state = state.copyWith(status: AuthStatus.unauthenticated);
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
        hasPin: data['has_pin'] as bool,
        customerName: data['customer_name'] as String? ?? '',
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

  bool _handleLoginResponse(dynamic responseData) {
    final resData = responseData['data'] as Map<String, dynamic>;
    final token = resData['token'] as String;
    final customerRaw = resData['customer'] as Map<String, dynamic>;

    SecureStorageService.instance.saveToken(token);
    SecureStorageService.instance.saveCustomer(customerRaw);

    final customer = CustomerProfile.fromJson(customerRaw);
    state = state.copyWith(
      status: AuthStatus.authenticated,
      customer: customer,
      isLoading: false,
    );
    return true;
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
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier();
});
