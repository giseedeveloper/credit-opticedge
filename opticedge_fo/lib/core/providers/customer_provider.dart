import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../models/customer_model.dart';
import '../models/dashboard_model.dart';

// ─── Dashboard ───────────────────────────────────────────────────
class DashboardNotifier extends StateNotifier<AsyncValue<DashboardStats>> {
  DashboardNotifier() : super(const AsyncValue.loading()) {
    load();
  }

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final res = await ApiClient.instance.get('/kyc/dashboard');
      final data = res.data['data'] as Map<String, dynamic>;
      state = AsyncValue.data(DashboardStats.fromJson(data));
    } catch (e) {
      state = AsyncValue.error(
          ApiClient.instance.parseError(e), StackTrace.current);
    }
  }
}

final dashboardProvider =
    StateNotifierProvider<DashboardNotifier, AsyncValue<DashboardStats>>(
  (ref) => DashboardNotifier(),
);

// ─── Branches ────────────────────────────────────────────────────
final branchesProvider = FutureProvider<List<BranchModel>>((ref) async {
  final res = await ApiClient.instance.get('/kyc/branches');
  final list = res.data['data'] as List<dynamic>;
  return list
      .map((e) => BranchModel.fromJson(e as Map<String, dynamic>))
      .toList();
});

// ─── Customer List ────────────────────────────────────────────────
class CustomerListState {
  final List<CustomerListItem> items;
  final bool isLoading;
  final bool hasMore;
  final int page;
  final String? error;
  final String selectedTab;
  final String search;

  const CustomerListState({
    this.items = const [],
    this.isLoading = false,
    this.hasMore = true,
    this.page = 1,
    this.error,
    this.selectedTab = 'all',
    this.search = '',
  });

  CustomerListState copyWith({
    List<CustomerListItem>? items,
    bool? isLoading,
    bool? hasMore,
    int? page,
    String? error,
    String? selectedTab,
    String? search,
  }) =>
      CustomerListState(
        items: items ?? this.items,
        isLoading: isLoading ?? this.isLoading,
        hasMore: hasMore ?? this.hasMore,
        page: page ?? this.page,
        error: error,
        selectedTab: selectedTab ?? this.selectedTab,
        search: search ?? this.search,
      );
}

class CustomerListNotifier extends StateNotifier<CustomerListState> {
  CustomerListNotifier() : super(const CustomerListState()) {
    load();
  }

  Future<void> load({bool reset = false}) async {
    if (reset) {
      state = state.copyWith(
          items: [], page: 1, hasMore: true, isLoading: true, error: null);
    } else {
      if (!state.hasMore || state.isLoading) return;
      state = state.copyWith(isLoading: true, error: null);
    }

    try {
      final params = <String, dynamic>{
        'page': reset ? 1 : state.page,
        'per_page': 20,
      };
      if (state.selectedTab != 'all') params['status'] = state.selectedTab;
      if (state.search.isNotEmpty) params['search'] = state.search;

      final res = await ApiClient.instance.get('/kyc/customers',
          queryParameters: params);
      final body = res.data['data'] as Map<String, dynamic>;
      final newItems = (body['data'] as List<dynamic>)
          .map((e) => CustomerListItem.fromJson(e as Map<String, dynamic>))
          .toList();
      final lastPage = (body['last_page'] as num).toInt();
      final currentPage = (body['current_page'] as num).toInt();

      state = state.copyWith(
        items: reset ? newItems : [...state.items, ...newItems],
        page: currentPage + 1,
        hasMore: currentPage < lastPage,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: ApiClient.instance.parseError(e),
      );
    }
  }

  void setTab(String tab) {
    if (state.selectedTab == tab) return;
    state = state.copyWith(selectedTab: tab);
    load(reset: true);
  }

  void setSearch(String query) {
    state = state.copyWith(search: query);
    load(reset: true);
  }
}

final customerListProvider =
    StateNotifierProvider<CustomerListNotifier, CustomerListState>(
  (ref) => CustomerListNotifier(),
);

// ─── Customer Detail ──────────────────────────────────────────────
final customerDetailProvider =
    FutureProvider.family<CustomerDetail, String>((ref, id) async {
  final res = await ApiClient.instance.get('/kyc/customers/$id');
  return CustomerDetail.fromJson(
      res.data['data'] as Map<String, dynamic>);
});
