import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/kyc_progress.dart';

class KycTrackingState {
  final bool isLoading;
  final String? error;
  final KycProgressSnapshot? snapshot;
  final String? phone;

  const KycTrackingState({
    this.isLoading = false,
    this.error,
    this.snapshot,
    this.phone,
  });

  KycTrackingState copyWith({
    bool? isLoading,
    String? error,
    KycProgressSnapshot? snapshot,
    String? phone,
  }) {
    return KycTrackingState(
      isLoading: isLoading ?? this.isLoading,
      error: error,
      snapshot: snapshot ?? this.snapshot,
      phone: phone ?? this.phone,
    );
  }
}

class KycTrackingNotifier extends StateNotifier<KycTrackingState> {
  KycTrackingNotifier() : super(const KycTrackingState());

  Future<KycProgressSnapshot?> loadByPhone(String phone) async {
    state = state.copyWith(isLoading: true, error: null, phone: phone);
    try {
      final res = await ApiClient.instance.post(
        '/kyc-status',
        data: {'phone': phone},
      );
      final data = res.data['data'] as Map<String, dynamic>;
      final snapshot = KycProgressSnapshot.fromJson(data);
      state = state.copyWith(isLoading: false, snapshot: snapshot);
      return snapshot;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: ApiClient.parseError(e),
      );
      rethrow;
    }
  }

  Future<void> refresh() async {
    final phone = state.phone;
    if (phone == null || phone.isEmpty) {
      return;
    }
    await loadByPhone(phone);
  }
}

final kycTrackingProvider =
    StateNotifierProvider<KycTrackingNotifier, KycTrackingState>((ref) {
  return KycTrackingNotifier();
});
