import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Default API root when no custom URL is saved (full path including `/api`).
const String kInternalApiBaseUrl = 'https://optic.opticedgeafrica.net/api';

/// Old production host — PHP < 8.2; auto-remapped to [kInternalApiBaseUrl].
const String _deprecatedProductionApiBaseUrl = 'https://opticedgeafrica.net/api';

const Set<String> _allowedProductionApiHosts = {
  'optic.opticedgeafrica.net',
};

const String _prefsKeyServerSettingsApiUrl = 'server_settings_api_url';
const String _prefsKeyLegacyApiBaseUrlOverride = 'api_base_url_override';

String? _cachedResolvedBaseUrl;

String _normalizeApiBaseUrl(String url) {
  var s = url.trim();
  while (s.endsWith('/')) {
    s = s.substring(0, s.length - 1);
  }
  return s;
}

/// Vendor tenant subdomains (e.g. optic-edge-africa.opticedgeafrica.net) are no longer used.
bool isLegacyTenantApiBaseUrl(String url) {
  final uri = Uri.tryParse(_normalizeApiBaseUrl(url));
  final host = uri?.host ?? '';
  if (host.isEmpty || _allowedProductionApiHosts.contains(host)) return false;
  return host.endsWith('.opticedgeafrica.net');
}

/// Accepts user input; adds `http://` when the scheme is omitted.
String? normalizeServerSettingsApiUrlInput(String value) {
  var t = value.trim();
  if (t.isEmpty) return null;
  if (!t.contains('://')) {
    t = 'http://$t';
  }
  final u = Uri.tryParse(t);
  if (u == null || !u.hasScheme || (u.scheme != 'http' && u.scheme != 'https')) {
    return null;
  }
  if (u.host.isEmpty) return null;
  return _normalizeApiBaseUrl(t);
}

/// Maps broken `opticedgeafrica.net` API root to [kInternalApiBaseUrl].
String remapDeprecatedProductionApiUrl(String url) {
  final normalized = _normalizeApiBaseUrl(url);
  if (normalized == _normalizeApiBaseUrl(_deprecatedProductionApiBaseUrl)) {
    return kInternalApiBaseUrl;
  }
  final uri = Uri.tryParse(normalized);
  if (uri != null &&
      uri.host == 'opticedgeafrica.net' &&
      (uri.path == '/api' || uri.path.isEmpty)) {
    return kInternalApiBaseUrl;
  }
  return normalized;
}

/// Parses an API response body as JSON; throws a readable error when the server returns plain text/HTML.
dynamic decodeApiJsonBody(String body, {required int statusCode}) {
  final trimmed = body.trim();
  if (trimmed.isEmpty) {
    throw Exception('Server returned an empty response (HTTP $statusCode).');
  }
  try {
    return jsonDecode(trimmed);
  } on FormatException {
    if (trimmed.contains('Composer detected issues in your platform')) {
      throw Exception(
        'Wrong API server: opticedgeafrica.net requires PHP 8.2+. '
        'Use $kInternalApiBaseUrl in Server settings.',
      );
    }
    if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html')) {
      throw Exception(
        'Server returned a web page instead of JSON. '
        'Check API URL in Server settings (expected $kInternalApiBaseUrl).',
      );
    }
    final preview = trimmed.length > 180 ? '${trimmed.substring(0, 180)}…' : trimmed;
    throw Exception('Invalid server response (HTTP $statusCode): $preview');
  }
}

Map<String, dynamic>? decodeApiJsonMap(http.Response res) {
  final decoded = decodeApiJsonBody(res.body, statusCode: res.statusCode);
  if (decoded is Map<String, dynamic>) return decoded;
  if (decoded is Map) return Map<String, dynamic>.from(decoded);
  throw Exception('Expected JSON object from server (HTTP ${res.statusCode}).');
}

void _invalidateResolvedBaseUrlCache() {
  _cachedResolvedBaseUrl = null;
}

Future<String?> _readServerSettingsApiUrl(SharedPreferences prefs) async {
  await prefs.reload();
  var raw = prefs.getString(_prefsKeyServerSettingsApiUrl);
  if (raw == null || raw.trim().isEmpty) {
    raw = prefs.getString(_prefsKeyLegacyApiBaseUrlOverride);
    if (raw != null && raw.trim().isNotEmpty) {
      final migrated = remapDeprecatedProductionApiUrl(_normalizeApiBaseUrl(raw));
      await prefs.setString(_prefsKeyServerSettingsApiUrl, migrated);
      await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
      return migrated;
    }
  }
  final trimmed = raw?.trim();
  if (trimmed == null || trimmed.isEmpty) return null;
  final normalized = remapDeprecatedProductionApiUrl(_normalizeApiBaseUrl(trimmed));
  if (normalized != _normalizeApiBaseUrl(trimmed)) {
    await prefs.setString(_prefsKeyServerSettingsApiUrl, normalized);
  }
  return normalized;
}

/// Resolved URL used for every request: server settings URL if saved, otherwise [kInternalApiBaseUrl].
Future<String> resolveBaseUrl() async {
  if (_cachedResolvedBaseUrl != null) return _cachedResolvedBaseUrl!;

  final prefs = await SharedPreferences.getInstance();
  final saved = await _readServerSettingsApiUrl(prefs);
  if (saved == null) {
    _cachedResolvedBaseUrl = kInternalApiBaseUrl;
    return kInternalApiBaseUrl;
  }
  if (isLegacyTenantApiBaseUrl(saved)) {
    await prefs.remove(_prefsKeyServerSettingsApiUrl);
    await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
    _cachedResolvedBaseUrl = kInternalApiBaseUrl;
    return kInternalApiBaseUrl;
  }
  final resolved = remapDeprecatedProductionApiUrl(saved);
  _cachedResolvedBaseUrl = resolved;
  return resolved;
}

/// Saves server settings API URL. Pass null or blank to clear and use [kInternalApiBaseUrl].
Future<void> setServerSettingsApiUrl(String? url) async {
  _invalidateResolvedBaseUrlCache();
  final prefs = await SharedPreferences.getInstance();
  final normalized = normalizeServerSettingsApiUrlInput(url ?? '');
  if (normalized == null) {
    await prefs.remove(_prefsKeyServerSettingsApiUrl);
    await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
  } else {
    final remapped = remapDeprecatedProductionApiUrl(normalized);
    await prefs.setString(_prefsKeyServerSettingsApiUrl, remapped);
    await prefs.remove(_prefsKeyLegacyApiBaseUrlOverride);
    _cachedResolvedBaseUrl = remapped;
  }
}

Future<String?> getServerSettingsApiUrl() async {
  final prefs = await SharedPreferences.getInstance();
  return _readServerSettingsApiUrl(prefs);
}

Future<String?> getStoredToken() async {
  final prefs = await SharedPreferences.getInstance();
  return prefs.getString('token');
}

Future<void> setStoredToken(String token) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('token', token);
}

Future<void> clearStoredAuth() async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove('token');
  await prefs.remove('user');
}

Future<Map<String, dynamic>?> getStoredUser() async {
  final prefs = await SharedPreferences.getInstance();
  final s = prefs.getString('user');
  if (s == null) return null;
  try {
    return jsonDecode(s) as Map<String, dynamic>;
  } catch (_) {
    return null;
  }
}

Future<void> setStoredUser(Map<String, dynamic> user) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('user', jsonEncode(user));
}

Future<http.Response> apiGet(String path, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  return http.get(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
  );
}

Future<http.Response> apiPost(String path, Map<String, dynamic> body, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  return http.post(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
    body: jsonEncode(body),
  );
}

Future<http.Response> apiPut(String path, Map<String, dynamic> body, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  return http.put(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
    body: jsonEncode(body),
  );
}

Future<http.Response> apiDelete(String path, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  return http.delete(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
  );
}

Future<http.Response> apiPatch(String path, Map<String, dynamic> body, {String? token}) async {
  final base = await resolveBaseUrl();
  final t = token ?? await getStoredToken();
  return http.patch(
    Uri.parse('$base$path'),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    },
    body: jsonEncode(body),
  );
}
