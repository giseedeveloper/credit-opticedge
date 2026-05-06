import 'dart:async';
import 'dart:math';
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
  String? _lastIdempotencyKey;
  String? _lastRequestFingerprint;
  DateTime? _lastRequestAt;

  Future<bool> requestPayment({required double amount, String? phone}) async {
    state = state.copyWith(isRequesting: true, error: null, isCompleted: false);
    try {
      final normalizedPhone = phone?.trim() ?? '';
      final requestFingerprint = '${amount.toStringAsFixed(2)}|$normalizedPhone';
      final now = DateTime.now();
      final shouldReuseKey = _lastRequestFingerprint == requestFingerprint &&
          _lastRequestAt != null &&
          now.difference(_lastRequestAt!).inMinutes < 2;
      final idempotencyKey = shouldReuseKey
          ? (_lastIdempotencyKey ?? _generateIdempotencyKey())
          : _generateIdempotencyKey();

      _lastIdempotencyKey = idempotencyKey;
      _lastRequestFingerprint = requestFingerprint;
      _lastRequestAt = now;

      final data = <String, dynamic>{'amount': amount};
      if (phone != null && phone.isNotEmpty) data['phone'] = phone;
      data['idempotency_key'] = idempotencyKey;

      final res = await ApiClient.instance.post('/loan/pay', data: data);
      final resData = res.data['data'] as Map<String, dynamic>;
      final paymentId = resData['payment_id']?.toString();

      state = state.copyWith(
        isRequesting: false,
        paymentId: paymentId,
        orderId: resData['order_id'] as String?,
        amount: amount,
        statusMessage: 'Payment request sent. Check your phone.',
      );

      if (paymentId == null || paymentId.isEmpty) {
        state = state.copyWith(
          isPolling: false,
          error: 'Payment request received but missing payment reference. Please try again.',
        );
        return false;
      }

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
        state = state.copyWith(isPolling: false);
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
    _lastIdempotencyKey = null;
    _lastRequestFingerprint = null;
    _lastRequestAt = null;
    state = const PaymentState();
  }

  String _generateIdempotencyKey() {
    final random = Random.secure().nextInt(1 << 32).toRadixString(16);
    return 'custpay-${DateTime.now().microsecondsSinceEpoch}-$random';
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
