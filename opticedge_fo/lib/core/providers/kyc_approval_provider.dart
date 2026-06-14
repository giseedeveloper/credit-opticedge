import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/kyc_approval_models.dart';

class KycApprovalListState {
  final bool isLoading;
  final String? error;
  final int stage;
  final List<KycApprovalQueueItem> items;
  final Map<int, int> stageCounts;
  final int currentPage;
  final int lastPage;

  const KycApprovalListState({
    this.isLoading = false,
    this.error,
    this.stage = 1,
    this.items = const [],
    this.stageCounts = const {},
    this.currentPage = 1,
    this.lastPage = 1,
  });

  KycApprovalListState copyWith({
    bool? isLoading,
    String? error,
    int? stage,
    List<KycApprovalQueueItem>? items,
    Map<int, int>? stageCounts,
    int? currentPage,
    int? lastPage,
  }) {
    return KycApprovalListState(
      isLoading: isLoading ?? this.isLoading,
      error: error,
      stage: stage ?? this.stage,
      items: items ?? this.items,
      stageCounts: stageCounts ?? this.stageCounts,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
    );
  }
}

List<dynamic> _parseQueueCustomers(dynamic raw) {
  if (raw is List) {
    return raw;
  }
  if (raw is Map) {
    final nested = raw['data'];
    if (nested is List) {
      return nested;
    }
  }
  return const [];
}

class KycApprovalListNotifier extends StateNotifier<KycApprovalListState> {
  KycApprovalListNotifier() : super(const KycApprovalListState());

  String _search = '';

  Future<void> load({int? stage, String? search}) async {
    if (stage != null) {
      state = state.copyWith(stage: stage);
    }
    if (search != null) {
      _search = search;
    }

    state = state.copyWith(isLoading: true, error: null);
    try {
      final res = await ApiClient.instance.get(
        '/kyc/approvals/pending',
        queryParameters: {
          'stage': state.stage,
          if (_search.isNotEmpty) 'search': _search,
          'per_page': 20,
        },
      );
      final data = res.data['data'] as Map<String, dynamic>;
      final customers = _parseQueueCustomers(data['customers'])
          .map((e) => KycApprovalQueueItem.fromJson(
                Map<String, dynamic>.from(e as Map),
              ))
          .toList();
      final countsRaw = data['stage_counts'] as Map<String, dynamic>? ?? {};
      final counts = <int, int>{};
      countsRaw.forEach((key, value) {
        final k = int.tryParse(key);
        if (k != null) {
          counts[k] = (value as num?)?.toInt() ?? 0;
        }
      });
      final pagination =
          data['pagination'] as Map<String, dynamic>? ?? const {};
      state = state.copyWith(
        isLoading: false,
        items: customers,
        stageCounts: counts,
        currentPage: (pagination['current_page'] as num?)?.toInt() ?? 1,
        lastPage: (pagination['last_page'] as num?)?.toInt() ?? 1,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: ApiClient.instance.parseError(e),
      );
    }
  }
}

class KycApprovalDetailState {
  final bool isLoading;
  final bool isSubmitting;
  final String? error;
  final KycApprovalDetail? detail;

  const KycApprovalDetailState({
    this.isLoading = false,
    this.isSubmitting = false,
    this.error,
    this.detail,
  });

  KycApprovalDetailState copyWith({
    bool? isLoading,
    bool? isSubmitting,
    String? error,
    KycApprovalDetail? detail,
  }) {
    return KycApprovalDetailState(
      isLoading: isLoading ?? this.isLoading,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      error: error,
      detail: detail ?? this.detail,
    );
  }
}

class KycApprovalDetailNotifier extends StateNotifier<KycApprovalDetailState> {
  KycApprovalDetailNotifier() : super(const KycApprovalDetailState());

  Future<void> load(String customerId) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final res =
          await ApiClient.instance.get('/kyc/approvals/customers/$customerId');
      final data = res.data['data'] as Map<String, dynamic>;
      state = state.copyWith(
        isLoading: false,
        detail: KycApprovalDetail.fromJson(data),
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: ApiClient.instance.parseError(e),
      );
    }
  }

  Future<bool> approveStage(String customerId, int stage, {String? notes}) async {
    return _post(
      '/kyc/approvals/customers/$customerId/stages/$stage/approve',
      {'notes': notes},
      customerId,
    );
  }

  Future<bool> rejectStage(
    String customerId,
    int stage, {
    required String reason,
    String? notes,
  }) async {
    return _post(
      '/kyc/approvals/customers/$customerId/stages/$stage/reject',
      {'reason': reason, 'notes': notes},
      customerId,
    );
  }

  Future<bool> recordConfirmationCall(
    String customerId, {
    required String outcome,
    String? notes,
  }) async {
    return _post(
      '/kyc/approvals/customers/$customerId/confirmation-call',
      {'outcome': outcome, 'notes': notes},
      customerId,
    );
  }

  Future<bool> recordNokCall(
    String customerId, {
    required String outcome,
    String? notes,
  }) async {
    return _post(
      '/kyc/approvals/customers/$customerId/nok-call',
      {'outcome': outcome, 'notes': notes},
      customerId,
    );
  }

  Future<bool> manualVerifyFace(String customerId) async {
    return _post(
      '/kyc/approvals/customers/$customerId/face-match/manual-verify',
      {},
      customerId,
    );
  }

  Future<bool> _post(
    String path,
    Map<String, dynamic> body,
    String customerId,
  ) async {
    state = state.copyWith(isSubmitting: true, error: null);
    try {
      final payload = <String, dynamic>{};
      for (final entry in body.entries) {
        final value = entry.value;
        if (value != null && value.toString().isNotEmpty) {
          payload[entry.key] = value;
        }
      }
      await ApiClient.instance.post(path, data: payload);
      await load(customerId);
      state = state.copyWith(isSubmitting: false);
      return true;
    } catch (e) {
      state = state.copyWith(
        isSubmitting: false,
        error: ApiClient.instance.parseError(e),
      );
      return false;
    }
  }
}

final kycApprovalListProvider =
    StateNotifierProvider<KycApprovalListNotifier, KycApprovalListState>((ref) {
  return KycApprovalListNotifier();
});

final kycApprovalDetailProvider = StateNotifierProvider.family<
    KycApprovalDetailNotifier, KycApprovalDetailState, String>((ref, id) {
  return KycApprovalDetailNotifier();
});
