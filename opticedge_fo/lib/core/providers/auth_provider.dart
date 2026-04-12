import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/user_model.dart';
import '../providers/settings_provider.dart';
import '../services/biometric_service.dart';
import '../storage/secure_storage.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState {
  final AuthStatus status;
  final UserModel? user;
  final String? error;
  final bool isLoading;
  final bool canUseBiometricUnlock;

  const AuthState({
    this.status = AuthStatus.unknown,
    this.user,
    this.error,
    this.isLoading = false,
    this.canUseBiometricUnlock = false,
  });

  AuthState copyWith({
    AuthStatus? status,
    UserModel? user,
    String? error,
    bool? isLoading,
    bool? canUseBiometricUnlock,
  }) =>
      AuthState(
        status: status ?? this.status,
        user: user ?? this.user,
        error: error,
        isLoading: isLoading ?? this.isLoading,
        canUseBiometricUnlock:
            canUseBiometricUnlock ?? this.canUseBiometricUnlock,
      );
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier() : super(const AuthState()) {
    _init();
  }

  static bool shouldOfferBiometricUnlock({
    required bool biometricEnabled,
    required bool hasToken,
    required bool hasUser,
  }) {
    return biometricEnabled && hasToken && hasUser;
  }

  Future<void> _init() async {
    final token = await SecureStorageService.instance.getToken();
    if (token == null) {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        canUseBiometricUnlock: false,
      );
      return;
    }

    final userData = await SecureStorageService.instance.getUser();
    if (userData != null) {
      final biometricEnabled = await _isBiometricUnlockEnabled();
      if (shouldOfferBiometricUnlock(
        biometricEnabled: biometricEnabled,
        hasToken: true,
        hasUser: true,
      )) {
        state = state.copyWith(
          status: AuthStatus.unauthenticated,
          user: UserModel.fromJson(userData),
          canUseBiometricUnlock: true,
        );
        return;
      }

      state = state.copyWith(
        status: AuthStatus.authenticated,
        user: UserModel.fromJson(userData),
        canUseBiometricUnlock: false,
      );
    } else {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        canUseBiometricUnlock: false,
      );
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
          canUseBiometricUnlock: false,
        );
      } catch (_) {
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: UserModel.fromJson(userRaw),
          isLoading: false,
          canUseBiometricUnlock: false,
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

  Future<bool> unlockWithBiometrics() async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final token = await SecureStorageService.instance.getToken();
      final userData = await SecureStorageService.instance.getUser();

      if (token == null || userData == null) {
        state = state.copyWith(
          isLoading: false,
          status: AuthStatus.unauthenticated,
          canUseBiometricUnlock: false,
          error: 'No saved session is available for biometric unlock.',
        );
        return false;
      }

      final biometricEnabled = await _isBiometricUnlockEnabled();
      if (!biometricEnabled) {
        state = state.copyWith(
          isLoading: false,
          status: AuthStatus.unauthenticated,
          canUseBiometricUnlock: false,
          error: 'Enable biometric login from settings first.',
        );
        return false;
      }

      final authentication =
          await BiometricService.instance.authenticateWithResult(
        reason: 'Confirm your identity to unlock Opticedge FO',
      );

      if (!authentication.isAuthenticated) {
        state = state.copyWith(
          isLoading: false,
          status: AuthStatus.unauthenticated,
          canUseBiometricUnlock: true,
          error: authentication.message ?? 'Biometric unlock failed.',
        );
        return false;
      }

      final user = UserModel.fromJson(userData);
      state = state.copyWith(
        status: AuthStatus.authenticated,
        user: user,
        isLoading: false,
        canUseBiometricUnlock: false,
      );

      await refreshProfile();
      return true;
    } catch (error) {
      state = state.copyWith(
        isLoading: false,
        status: AuthStatus.unauthenticated,
        canUseBiometricUnlock: true,
        error: ApiClient.instance.parseError(error),
      );
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await ApiClient.instance.post('/logout');
    } catch (_) {}
    await SecureStorageService.instance.deleteToken();
    await SecureStorageService.instance.deleteUser();
    state = const AuthState(
      status: AuthStatus.unauthenticated,
      canUseBiometricUnlock: false,
    );
  }

  Future<void> refreshProfile() async {
    try {
      final res = await ApiClient.instance.get('/me');
      final data = res.data['data'] as Map<String, dynamic>;
      await SecureStorageService.instance.saveUser(data);
      state = state.copyWith(user: UserModel.fromJson(data));
    } catch (_) {}
  }

  Future<bool> _isBiometricUnlockEnabled() async {
    final raw =
        await SecureStorageService.instance.read(SettingsNotifier.storageKey);
    if (raw == null || raw.trim().isEmpty) {
      return false;
    }

    try {
      final json = Map<String, dynamic>.from(jsonDecode(raw) as Map);
      final settings = SettingsState.fromJson(json);
      return settings.biometricEnabled;
    } catch (_) {
      return false;
    }
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>(
  (ref) => AuthNotifier(),
);
