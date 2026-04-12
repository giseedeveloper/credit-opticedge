import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/loan_model.dart';
import '../models/schedule_model.dart';
import '../models/transaction_model.dart';

// ─── Loan Summary ───────────────────────────────────────────────────
class LoanState {
  final LoanModel? loan;
  final bool isLoading;
  final String? error;

  const LoanState({this.loan, this.isLoading = false, this.error});

  LoanState copyWith({LoanModel? loan, bool? isLoading, String? error}) {
    return LoanState(
      loan: loan ?? this.loan,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class LoanNotifier extends StateNotifier<LoanState> {
  LoanNotifier() : super(const LoanState());

  Future<void> load() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final res = await ApiClient.instance.get('/loan');
      final data = res.data['data'];
      if (data == null) {
        state = const LoanState(loan: null, isLoading: false);
        return;
      }
      state = LoanState(loan: LoanModel.fromJson(data), isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: ApiClient.parseError(e));
    }
  }
}

final loanProvider = StateNotifierProvider<LoanNotifier, LoanState>((ref) {
  return LoanNotifier();
});

// ─── Repayment Schedule ─────────────────────────────────────────────
class ScheduleState {
  final ScheduleResponse? schedule;
  final bool isLoading;
  final String? error;

  const ScheduleState({this.schedule, this.isLoading = false, this.error});
}

class ScheduleNotifier extends StateNotifier<ScheduleState> {
  ScheduleNotifier() : super(const ScheduleState());

  Future<void> load() async {
    state = const ScheduleState(isLoading: true);
    try {
      final res = await ApiClient.instance.get('/loan/schedule');
      final data = res.data['data'];
      if (data == null || (data is List && data.isEmpty)) {
        state = const ScheduleState();
        return;
      }
      state = ScheduleState(schedule: ScheduleResponse.fromJson(data));
    } catch (e) {
      state = ScheduleState(error: ApiClient.parseError(e));
    }
  }
}

final scheduleProvider = StateNotifierProvider<ScheduleNotifier, ScheduleState>((ref) {
  return ScheduleNotifier();
});

// ─── Transactions ───────────────────────────────────────────────────
class TransactionsState {
  final List<TransactionModel> items;
  final bool isLoading;
  final String? error;
  final int currentPage;
  final bool hasMore;

  const TransactionsState({
    this.items = const [],
    this.isLoading = false,
    this.error,
    this.currentPage = 1,
    this.hasMore = true,
  });
}

class TransactionsNotifier extends StateNotifier<TransactionsState> {
  TransactionsNotifier() : super(const TransactionsState());

  Future<void> load({bool reset = false}) async {
    if (state.isLoading) return;
    final page = reset ? 1 : state.currentPage;
    state = TransactionsState(
      items: reset ? [] : state.items,
      isLoading: true,
      currentPage: page,
    );

    try {
      final res = await ApiClient.instance.get('/loan/transactions', queryParameters: {
        'page': page,
        'per_page': 20,
      });
      final data = res.data['data'] as Map<String, dynamic>;
      final list = (data['data'] as List)
          .map((e) => TransactionModel.fromJson(e as Map<String, dynamic>))
          .toList();

      state = TransactionsState(
        items: reset ? list : [...state.items, ...list],
        currentPage: (data['current_page'] as num).toInt() + 1,
        hasMore: (data['current_page'] as num) < (data['last_page'] as num),
      );
    } catch (e) {
      state = TransactionsState(
        items: state.items,
        error: ApiClient.parseError(e),
      );
    }
  }
}

final transactionsProvider = StateNotifierProvider<TransactionsNotifier, TransactionsState>((ref) {
  return TransactionsNotifier();
});
