import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../storage/secure_storage.dart';
import '../services/biometric_service.dart';

// ─── Language ────────────────────────────────────────────────────────────────

enum AppLanguage { en, sw }

extension AppLanguageX on AppLanguage {
  String get label => this == AppLanguage.en ? 'English' : 'Kiswahili';
  String get flag => this == AppLanguage.en ? '🇬🇧' : '🇹🇿';
}

// ─── Settings State ──────────────────────────────────────────────────────────

class SettingsState {
  final ThemeMode themeMode;
  final AppLanguage language;
  final bool biometricEnabled;
  final bool notificationsEnabled;

  const SettingsState({
    this.themeMode = ThemeMode.light,
    this.language = AppLanguage.en,
    this.biometricEnabled = false,
    this.notificationsEnabled = true,
  });

  SettingsState copyWith({
    ThemeMode? themeMode,
    AppLanguage? language,
    bool? biometricEnabled,
    bool? notificationsEnabled,
  }) =>
      SettingsState(
        themeMode: themeMode ?? this.themeMode,
        language: language ?? this.language,
        biometricEnabled: biometricEnabled ?? this.biometricEnabled,
        notificationsEnabled: notificationsEnabled ?? this.notificationsEnabled,
      );

  Map<String, dynamic> toJson() => {
        'themeMode': themeMode.index,
        'language': language.index,
        'biometricEnabled': biometricEnabled,
        'notificationsEnabled': notificationsEnabled,
      };

  factory SettingsState.fromJson(Map<String, dynamic> json) {
    final rawThemeIndex = json['themeMode'];
    final themeIndex = rawThemeIndex is int ? rawThemeIndex : null;
    final themeMode = (themeIndex != null &&
            themeIndex >= 0 &&
            themeIndex < ThemeMode.values.length)
        ? ThemeMode.values[themeIndex]
        : ThemeMode.light;

    final rawLangIndex = json['language'];
    final langIndex = rawLangIndex is int ? rawLangIndex : null;
    final language = (langIndex != null &&
            langIndex >= 0 &&
            langIndex < AppLanguage.values.length)
        ? AppLanguage.values[langIndex]
        : AppLanguage.en;

    return SettingsState(
      themeMode: themeMode,
      language: language,
      biometricEnabled: json['biometricEnabled'] ?? false,
      notificationsEnabled: json['notificationsEnabled'] ?? true,
    );
  }
}

// ─── Notifier ────────────────────────────────────────────────────────────────

class SettingsNotifier extends StateNotifier<SettingsState> {
  SettingsNotifier() : super(const SettingsState()) {
    _load();
  }

  static const storageKey = 'fo_app_settings';

  Future<void> _load() async {
    final data = await SecureStorageService.instance.read(storageKey);
    if (data != null) {
      try {
        state = SettingsState.fromJson(jsonDecode(data));
      } catch (_) {}
    }
  }

  Future<void> _save() async {
    await SecureStorageService.instance
        .write(storageKey, jsonEncode(state.toJson()));
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    state = state.copyWith(themeMode: mode);
    await _save();
  }

  Future<void> setLanguage(AppLanguage lang) async {
    state = state.copyWith(language: lang);
    await _save();
  }

  /// Enable biometric: checks availability, then authenticates.
  /// Returns a message if it fails, or null on success.
  Future<String?> toggleBiometric(bool enabled) async {
    if (enabled) {
      final bio = BiometricService.instance;
      final availability = await bio.availability();
      if (!availability.isAvailable) {
        return availability.message ??
            'Biometrics not available on this device.';
      }

      final authentication = await bio.authenticateWithResult(
        reason: 'Verify your identity to enable biometric login',
      );
      if (!authentication.isAuthenticated) {
        return authentication.message ?? 'Authentication failed';
      }
    }
    state = state.copyWith(biometricEnabled: enabled);
    await _save();
    return null;
  }

  Future<void> toggleNotifications(bool enabled) async {
    state = state.copyWith(notificationsEnabled: enabled);
    await _save();
  }
}

final settingsProvider = StateNotifierProvider<SettingsNotifier, SettingsState>(
  (ref) => SettingsNotifier(),
);
