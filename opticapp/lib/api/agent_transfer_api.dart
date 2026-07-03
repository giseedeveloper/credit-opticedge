import 'dart:convert';
import 'client.dart';

Future<List<Map<String, dynamic>>> listAgentTransfers() async {
  final res = await apiGet('/agent/transfers?per_page=50');
  final data = jsonDecode(res.body) as Map<String, dynamic>?;
  if (res.statusCode != 200) {
    throw Exception(data?['message']?.toString() ?? 'Failed to load transfers');
  }
  final list = data?['data'];
  if (list == null || list is! List) return [];
  return (list as List<dynamic>).map((e) => e as Map<String, dynamic>).toList();
}

Future<Map<String, dynamic>> getAgentTransferDetail(int transferId) async {
  final res = await apiGet('/agent/transfers/$transferId');
  final data = jsonDecode(res.body) as Map<String, dynamic>?;
  if (res.statusCode != 200) {
    throw Exception(data?['message']?.toString() ?? 'Failed to load transfer detail');
  }
  return data?['data'] as Map<String, dynamic>? ?? {};
}

Future<void> cancelAgentTransfer(int transferId) async {
  final res = await apiPost('/agent/transfers/$transferId/cancel', {});
  final data = jsonDecode(res.body) as Map<String, dynamic>?;
  if (res.statusCode != 200) {
    throw Exception(data?['message']?.toString() ?? 'Cancel failed');
  }
}

Future<Map<String, dynamic>> acceptAgentTransfer(int transferId, {String? note}) async {
  final body = <String, dynamic>{};
  if (note != null && note.trim().isNotEmpty) {
    body['note'] = note.trim();
  }
  final res = await apiPost('/agent/transfers/$transferId/accept', body);
  final data = jsonDecode(res.body) as Map<String, dynamic>?;
  if (res.statusCode != 200) {
    throw Exception(data?['message']?.toString() ?? 'Accept failed');
  }
  return data ?? {};
}

Future<Map<String, dynamic>> declineAgentTransfer(int transferId, {String? note}) async {
  final body = <String, dynamic>{};
  if (note != null && note.trim().isNotEmpty) {
    body['note'] = note.trim();
  }
  final res = await apiPost('/agent/transfers/$transferId/decline', body);
  final data = jsonDecode(res.body) as Map<String, dynamic>?;
  if (res.statusCode != 200) {
    throw Exception(data?['message']?.toString() ?? 'Decline failed');
  }
  return data ?? {};
}
