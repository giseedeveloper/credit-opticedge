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

  AuthState copyWith({AuthStatus? status, CustomerProfile? customer, String? error, bool? isLoading}) {
    return AuthState(
      status: status ?? this.status,
      customer: customer ?? this.customer,
      error: error,
      isLoading: isLoading ?? this.isLoading,
    );
  }
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
      state = state.copyWith(status: AuthStatus.authenticated, customer: customer);
    } catch (_) {
      await SecureStorageService.instance.clearAll();
      state = state.copyWith(status: AuthStatus.unauthenticated);
    }
  }

  Future<bool> login(String phone, String pin) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final res = await ApiClient.instance.post('/login', data: {
        'phone': phone,
        'pin': pin,
      });
      final resData = res.data['data'] as Map<String, dynamic>;
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
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: ApiClient.parseError(e),
      );
      return false;
    }
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
