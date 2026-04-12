import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';

class PaymentState {
  final bool isRequesting;
  final bool isPolling;
  final String? paymentId;
  final String? orderId;
  final double? amount;
  final bool isCompleted;
  final String? error;
  final String? statusMessage;

  const PaymentState({
    this.isRequesting = false,
    this.isPolling = false,
    this.paymentId,
    this.orderId,
    this.amount,
    this.isCompleted = false,
    this.error,
    this.statusMessage,
  });

  PaymentState copyWith({
    bool? isRequesting,
    bool? isPolling,
    String? paymentId,
    String? orderId,
    double? amount,
    bool? isCompleted,
    String? error,
    String? statusMessage,
  }) {
    return PaymentState(
      isRequesting: isRequesting ?? this.isRequesting,
      isPolling: isPolling ?? this.isPolling,
      paymentId: paymentId ?? this.paymentId,
      orderId: orderId ?? this.orderId,
      amount: amount ?? this.amount,
      isCompleted: isCompleted ?? this.isCompleted,
      error: error,
      statusMessage: statusMessage ?? this.statusMessage,
    );
  }
}

class PaymentNotifier extends StateNotifier<PaymentState> {
  PaymentNotifier() : super(const PaymentState());

  Timer? _pollTimer;

  Future<bool> requestPayment({required double amount, String? phone}) async {
    state = state.copyWith(isRequesting: true, error: null, isCompleted: false);
    try {
      final data = <String, dynamic>{'amount': amount};
      if (phone != null && phone.isNotEmpty) data['phone'] = phone;

      final res = await ApiClient.instance.post('/loan/pay', data: data);
      final resData = res.data['data'] as Map<String, dynamic>;

      state = state.copyWith(
        isRequesting: false,
        paymentId: resData['payment_id'] as String?,
        orderId: resData['order_id'] as String?,
        amount: amount,
        statusMessage: 'Payment request sent. Check your phone.',
      );

      _startPolling();
      return true;
    } catch (e) {
      state = state.copyWith(
        isRequesting: false,
        error: ApiClient.parseError(e),
      );
      return false;
    }
  }

  void _startPolling() {
    _pollTimer?.cancel();
    state = state.copyWith(isPolling: true);

    int attempts = 0;
    _pollTimer = Timer.periodic(const Duration(seconds: 5), (timer) async {
      if (state.paymentId == null || !mounted) {
        timer.cancel();
        return;
      }

      attempts++;
      if (attempts > 24) {
        timer.cancel();
        state = state.copyWith(
          isPolling: false,
          statusMessage: 'Payment status check timed out. Pull to refresh later.',
        );
        return;
      }

      try {
        final res = await ApiClient.instance.get('/loan/pay/${state.paymentId}/status');
        final data = res.data['data'] as Map<String, dynamic>;
        final completed = data['is_completed'] as bool? ?? false;

        if (completed) {
          timer.cancel();
          state = state.copyWith(
            isPolling: false,
            isCompleted: true,
            statusMessage: 'Payment successful!',
          );
        }
      } catch (_) {}
    });
  }

  void reset() {
    _pollTimer?.cancel();
    state = const PaymentState();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }
}

final paymentProvider = StateNotifierProvider<PaymentNotifier, PaymentState>((ref) {
  return PaymentNotifier();
});
