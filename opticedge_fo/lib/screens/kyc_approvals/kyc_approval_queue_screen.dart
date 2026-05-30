import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/models/kyc_approval_models.dart';
import '../../core/providers/kyc_approval_provider.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';
import '../../widgets/common/status_badge.dart';

class KycApprovalQueueScreen extends ConsumerStatefulWidget {
  const KycApprovalQueueScreen({super.key, this.initialStage});

  final int? initialStage;

  @override
  ConsumerState<KycApprovalQueueScreen> createState() =>
      _KycApprovalQueueScreenState();
}

class _KycApprovalQueueScreenState extends ConsumerState<KycApprovalQueueScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabs;
  final _searchCtrl = TextEditingController();
  Timer? _debounce;

  static const _stageLabels = [
    'Stage 1',
    'Stage 2',
    'Calls',
    'NOK',
  ];

  @override
  void initState() {
    super.initState();
    final stage = (widget.initialStage ?? 1).clamp(1, 4);
    _tabs = TabController(length: 4, vsync: this, initialIndex: stage - 1);
    _tabs.addListener(() {
      if (!_tabs.indexIsChanging) {
        ref
            .read(kycApprovalListProvider.notifier)
            .load(stage: _tabs.index + 1);
      }
    });
    Future.microtask(() {
      ref.read(kycApprovalListProvider.notifier).load(stage: stage);
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _tabs.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onSearch(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      ref.read(kycApprovalListProvider.notifier).load(search: value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycApprovalListProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: PremiumGlassBackground(
        child: NestedScrollView(
          headerSliverBuilder: (context, inner) => [
            SliverAppBar(
              floating: true,
              backgroundColor: Colors.transparent,
              elevation: 0,
              title: const Text(
                'KYC Approvals',
                style: TextStyle(fontWeight: FontWeight.w800),
              ),
              leading: IconButton(
                icon: const Icon(Icons.arrow_back_rounded),
                onPressed: () => context.go('/dashboard'),
              ),
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                child: GlassCard.surface(
                  context,
                  borderRadius: BorderRadius.circular(18),
                  padding: const EdgeInsets.all(14),
                  child: TextField(
                    controller: _searchCtrl,
                    onChanged: _onSearch,
                    decoration: InputDecoration(
                      hintText: 'Tafuta jina, simu, NIDA...',
                      prefixIcon: Icon(Icons.search_rounded,
                          color: AppConstants.primary),
                      border: InputBorder.none,
                      isDense: true,
                    ),
                  ),
                ),
              ),
            ),
            SliverPersistentHeader(
              pinned: true,
              delegate: _TabHeader(
                tabController: _tabs,
                counts: state.stageCounts,
                labels: _stageLabels,
              ),
            ),
          ],
          body: RefreshIndicator(
            color: AppConstants.primary,
            onRefresh: () =>
                ref.read(kycApprovalListProvider.notifier).load(),
            child: _buildBody(state, isDark),
          ),
        ),
      ),
    );
  }

  Widget _buildBody(KycApprovalListState state, bool isDark) {
    if (state.isLoading && state.items.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (state.error != null && state.items.isEmpty) {
      return ListView(
        children: [
          Padding(
            padding: const EdgeInsets.all(24),
            child: GlassCard.surface(
              context,
              child: Column(
                children: [
                  Text(state.error!),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () =>
                        ref.read(kycApprovalListProvider.notifier).load(),
                    child: const Text('Jaribu tena'),
                  ),
                ],
              ),
            ),
          ),
        ],
      );
    }
    if (state.items.isEmpty) {
      return ListView(
        children: const [
          SizedBox(height: 80),
          Center(
            child: Text(
              'Hakuna maombi kwenye hatua hii.',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
      itemCount: state.items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, index) {
        final item = state.items[index];
        return _QueueTile(
          item: item,
          stage: state.stage,
          onTap: () => context.go('/kyc/approvals/${item.id}'),
        );
      },
    );
  }
}

class _QueueTile extends StatelessWidget {
  const _QueueTile({
    required this.item,
    required this.stage,
    required this.onTap,
  });

  final KycApprovalQueueItem item;
  final int stage;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: GlassCard.surface(
          context,
          borderRadius: BorderRadius.circular(20),
          padding: const EdgeInsets.all(16),
          child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              gradient: DesignTokens.heroGradient,
              borderRadius: BorderRadius.circular(14),
            ),
            alignment: Alignment.center,
            child: Text(
              item.fullName.isNotEmpty ? item.fullName[0].toUpperCase() : '?',
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w800,
                fontSize: 18,
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.fullName,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  item.phone,
                  style: TextStyle(
                    fontSize: 12,
                    color: Theme.of(context).hintColor,
                  ),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              StatusBadge(status: 'pending', small: true),
              if (item.faceMatchStatus != null) ...[
                const SizedBox(height: 6),
                Text(
                  item.faceMatchStatus!,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: AppConstants.warning,
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(width: 8),
          const Icon(Icons.chevron_right_rounded),
        ],
          ),
        ),
      ),
    );
  }
}

class _TabHeader extends SliverPersistentHeaderDelegate {
  _TabHeader({
    required this.tabController,
    required this.counts,
    required this.labels,
  });

  final TabController tabController;
  final Map<int, int> counts;
  final List<String> labels;

  @override
  double get minExtent => 48;

  @override
  double get maxExtent => 48;

  @override
  Widget build(
    BuildContext context,
    double shrinkOffset,
    bool overlapsContent,
  ) {
    return Material(
      color: Theme.of(context).scaffoldBackgroundColor.withValues(alpha: 0.85),
      child: TabBar(
        controller: tabController,
        isScrollable: true,
        labelColor: AppConstants.primary,
        unselectedLabelColor: Theme.of(context).hintColor,
        indicatorColor: AppConstants.primary,
        tabs: List.generate(4, (i) {
          final stage = i + 1;
          final count = counts[stage] ?? 0;
          return Tab(text: '${labels[i]} ($count)');
        }),
      ),
    );
  }

  @override
  bool shouldRebuild(covariant _TabHeader oldDelegate) => true;
}
