import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/user_model.dart';
import '../storage/secure_storage.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState {
  final AuthStatus status;
  final UserModel? user;
  final String? error;
  final bool isLoading;

  const AuthState({
    this.status = AuthStatus.unknown,
    this.user,
    this.error,
    this.isLoading = false,
  });

  AuthState copyWith({
    AuthStatus? status,
    UserModel? user,
    String? error,
    bool? isLoading,
  }) =>
      AuthState(
        status: status ?? this.status,
        user: user ?? this.user,
        error: error,
        isLoading: isLoading ?? this.isLoading,
      );
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier() : super(const AuthState()) {
    _init();
  }

  Future<void> _init() async {
    final token = await SecureStorageService.instance.getToken();
    if (token == null) {
      state = state.copyWith(status: AuthStatus.unauthenticated);
      return;
    }
    final userData = await SecureStorageService.instance.getUser();
    if (userData != null) {
      state = state.copyWith(
        status: AuthStatus.authenticated,
        user: UserModel.fromJson(userData),
      );
    } else {
      state = state.copyWith(status: AuthStatus.unauthenticated);
    }
  }

  Future<bool> login(String identifier, String password) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final response = await ApiClient.instance.post('/login', data: {
        'login_identifier': identifier,
        'password': password,
      });
      final body = response.data as Map<String, dynamic>;
      final token = body['data']['token']?.toString() ?? '';
      final userRaw = body['data']['user'] as Map<String, dynamic>;

      await SecureStorageService.instance.saveToken(token);
      await SecureStorageService.instance.saveUser(userRaw);

      // Fetch full profile with permissions
      try {
        final meResponse = await ApiClient.instance.get('/me');
        final meData = meResponse.data['data'] as Map<String, dynamic>;
        await SecureStorageService.instance.saveUser(meData);
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: UserModel.fromJson(meData),
          isLoading: false,
        );
      } catch (_) {
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: UserModel.fromJson(userRaw),
          isLoading: false,
        );
      }
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: ApiClient.instance.parseError(e),
      );
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await ApiClient.instance.post('/logout');
    } catch (_) {}
    await SecureStorageService.instance.clearAll();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }

  Future<void> refreshProfile() async {
    try {
      final res = await ApiClient.instance.get('/me');
      final data = res.data['data'] as Map<String, dynamic>;
      await SecureStorageService.instance.saveUser(data);
      state = state.copyWith(user: UserModel.fromJson(data));
    } catch (_) {}
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>(
  (ref) => AuthNotifier(),
);
