import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';

final staffMetricsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ApiClient.instance.get('/staff/metrics');
  return Map<String, dynamic>.from(res.data['data'] as Map? ?? {});
});

final recoveryTicketsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final res = await ApiClient.instance.get('/recovery/tickets');
  final data = res.data['data'];
  if (data is Map<String, dynamic>) {
    return data;
  }
  return {'data': data};
});

final stockSearchProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>?, String>((ref, imei) async {
  if (imei.trim().length < 14) {
    return null;
  }
  final res = await ApiClient.instance.get(
    '/stock/search',
    queryParameters: {'imei': imei.trim()},
  );
  return Map<String, dynamic>.from(res.data['data'] as Map? ?? {});
});

final vendorStockProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final res = await ApiClient.instance.get('/stock/vendor-list');
  final raw = res.data['data'];
  if (raw is List) {
    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }
  return [];
});
