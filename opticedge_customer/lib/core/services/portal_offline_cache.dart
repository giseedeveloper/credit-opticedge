import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// Persists customer portal API payloads for offline read access.
class PortalOfflineCache {
  PortalOfflineCache._();

  static const _loanKey = 'customer_portal_cache_loan_v1';
  static const _scheduleKey = 'customer_portal_cache_schedule_v1';

  static Future<void> saveLoan(Map<String, dynamic> data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_loanKey, jsonEncode(data));
  }

  static Future<Map<String, dynamic>?> loadLoan() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_loanKey);
    if (raw == null || raw.isEmpty) {
      return null;
    }
    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }
    } catch (_) {}
    return null;
  }

  static Future<void> saveSchedule(Map<String, dynamic> data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_scheduleKey, jsonEncode(data));
  }

  static Future<Map<String, dynamic>?> loadSchedule() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_scheduleKey);
    if (raw == null || raw.isEmpty) {
      return null;
    }
    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }
    } catch (_) {}
    return null;
  }
}
