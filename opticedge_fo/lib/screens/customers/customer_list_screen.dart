import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../core/providers/customer_provider.dart';
import '../../widgets/common/status_badge.dart';

class CustomerListScreen extends ConsumerStatefulWidget {
  final String? initialTab;
  const CustomerListScreen({super.key, this.initialTab});

  @override
  ConsumerState<CustomerListScreen> createState() => _CustomerListScreenState();
}

class _CustomerListScreenState extends ConsumerState<CustomerListScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _searchCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  Timer? _searchDebounce;

  static const _tabs = [
    ('all', 'All'),
    ('draft', 'Drafts'),
    ('pending', 'Pending'),
    ('approved', 'Approved'),
    ('rejected', 'Rejected'),
  ];

  @override
  void initState() {
    super.initState();
    final initialIndex =
        _tabs.indexWhere((t) => t.$1 == (widget.initialTab ?? 'all'));
    _tabController = TabController(
        length: _tabs.length,
        vsync: this,
        initialIndex: initialIndex >= 0 ? initialIndex : 0);

    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        ref
            .read(customerListProvider.notifier)
            .setTab(_tabs[_tabController.index].$1);
      }
    });

    _scrollCtrl.addListener(() {
      if (_scrollCtrl.position.pixels >=
          _scrollCtrl.position.maxScrollExtent - 200) {
        ref.read(customerListProvider.notifier).load();
      }
    });
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _tabController.dispose();
    _searchCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 420), () {
      ref.read(customerListProvider.notifier).setSearch(value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(customerListProvider);

    return Scaffold(
      backgroundColor: AppConstants.background,
      appBar: AppBar(
        title: const Text('My Customers'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () =>
                ref.read(customerListProvider.notifier).load(reset: true),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(96),
          child: Column(
            children: [
              // Search bar
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: TextField(
                  controller: _searchCtrl,
                  onChanged: _onSearchChanged,
                  decoration: InputDecoration(
                    hintText: 'Search name, phone, NIDA...',
                    hintStyle: const TextStyle(fontSize: 13),
                    prefixIcon: const Icon(Icons.search_rounded, size: 20),
                    suffixIcon: _searchCtrl.text.isNotEmpty
                        ? GestureDetector(
                            onTap: () {
                              _searchDebounce?.cancel();
                              _searchCtrl.clear();
                              ref
                                  .read(customerListProvider.notifier)
                                  .setSearch('');
                            },
                            child: const Icon(Icons.clear, size: 18),
                          )
                        : null,
                    contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 10),
                    filled: true,
                    fillColor: AppConstants.background,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(
                          color: AppConstants.border, width: 1),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(
                          color: AppConstants.border, width: 1),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(
                          color: AppConstants.primary, width: 1.5),
                    ),
                  ),
                ),
              ),

              // Tabs
              TabBar(
                controller: _tabController,
                isScrollable: true,
                tabAlignment: TabAlignment.start,
                labelColor: AppConstants.primary,
                unselectedLabelColor: AppConstants.textSecondary,
                indicatorColor: AppConstants.primary,
                indicatorWeight: 2.5,
                labelStyle:
                    const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                tabs: _tabs.map((t) => Tab(text: t.$2)).toList(),
              ),
            ],
          ),
        ),
      ),
      body: state.items.isEmpty && state.isLoading
          ? _buildShimmer()
          : state.items.isEmpty && !state.isLoading
              ? _buildEmpty(state.selectedTab)
              : RefreshIndicator(
                  color: AppConstants.primary,
                  onRefresh: () =>
                      ref.read(customerListProvider.notifier).load(reset: true),
                  child: ListView.separated(
                    controller: _scrollCtrl,
                    padding: const EdgeInsets.all(16),
                    itemCount: state.items.length + (state.hasMore ? 1 : 0),
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      if (i == state.items.length) {
                        return const Padding(
                          padding: EdgeInsets.symmetric(vertical: 16),
                          child: Center(
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: AppConstants.primary),
                          ),
                        );
                      }
                      final c = state.items[i];
                      return _CustomerCard(
                        item: c,
                        onTap: () => context.go('/customers/${c.id}'),
                      );
                    },
                  ),
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/kyc/new'),
        backgroundColor: AppConstants.primary,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.person_add_rounded),
        label: const Text('Register',
            style: TextStyle(fontWeight: FontWeight.w600)),
        elevation: 2,
      ),
    );
  }

  Widget _buildShimmer() {
    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: 6,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (_, __) => Container(
        height: 80,
        decoration: BoxDecoration(
          color: AppConstants.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppConstants.border),
        ),
      ),
    );
  }

  Widget _buildEmpty(String tab) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.people_outline_rounded,
              size: 64, color: AppConstants.border),
          const SizedBox(height: 16),
          Text(
            tab == 'all' ? 'No customers yet' : 'No $tab customers',
            style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppConstants.textSecondary),
          ),
          const SizedBox(height: 6),
          const Text(
            'Registered customers will appear here',
            style: TextStyle(fontSize: 13, color: AppConstants.textHint),
          ),
        ],
      ),
    );
  }
}

class _CustomerCard extends StatelessWidget {
  final dynamic item;
  final VoidCallback onTap;
  const _CustomerCard({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppConstants.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppConstants.border),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.02),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            _CustomerAvatar(
              name: item.fullName,
              imageUrl: item.headshotUrl,
            ),
            const SizedBox(width: 12),

            // Info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.fullName,
                    style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: AppConstants.textPrimary),
                  ),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      const Icon(Icons.phone_outlined,
                          size: 12, color: AppConstants.textHint),
                      const SizedBox(width: 4),
                      Flexible(
                        child: Text(item.phone,
                            style: const TextStyle(
                                fontSize: 12,
                                color: AppConstants.textSecondary),
                            overflow: TextOverflow.ellipsis),
                      ),
                      if (item.branch != null) ...[
                        const SizedBox(width: 8),
                        const Icon(Icons.location_on_outlined,
                            size: 12, color: AppConstants.textHint),
                        const SizedBox(width: 2),
                        Flexible(
                          child: Text(
                            item.branch!,
                            style: const TextStyle(
                                fontSize: 11, color: AppConstants.textHint),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),

            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                StatusBadge(status: item.kycStatus, small: true),
                if (item.autoCheck != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Auto: ${item.autoCheck}',
                    style: const TextStyle(
                        fontSize: 9, color: AppConstants.textHint),
                  ),
                ],
              ],
            ),
            const SizedBox(width: 4),
            const Icon(Icons.chevron_right,
                size: 18, color: AppConstants.textHint),
          ],
        ),
      ),
    );
  }
}

class _CustomerAvatar extends StatelessWidget {
  final String name;
  final String? imageUrl;

  const _CustomerAvatar({
    required this.name,
    required this.imageUrl,
  });

  @override
  Widget build(BuildContext context) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

    return Container(
      width: 48,
      height: 48,
      decoration: const BoxDecoration(
        color: AppConstants.primarySurface,
        shape: BoxShape.circle,
      ),
      clipBehavior: Clip.antiAlias,
      child: imageUrl == null
          ? Center(
              child: Text(
                initial,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: AppConstants.primary,
                ),
              ),
            )
          : Image.network(
              imageUrl!,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Center(
                child: Text(
                  initial,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: AppConstants.primary,
                  ),
                ),
              ),
            ),
    );
  }
}
