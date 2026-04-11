import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/customer_provider.dart';
import '../../core/l10n/app_strings.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _animController;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 600));
    Future.delayed(const Duration(milliseconds: 100), _animController.forward);
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  String _greeting(S s) {
    final h = DateTime.now().hour;
    if (h < 12) return s.goodMorning;
    if (h < 17) return s.goodAfternoon;
    return s.goodEvening;
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).user;
    final dashAsync = ref.watch(dashboardProvider);
    final theme = Theme.of(context);
    final s = S.of(ref);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: RefreshIndicator(
        color: AppConstants.primary,
        onRefresh: () async {
          ref.read(dashboardProvider.notifier).load();
        },
        child: CustomScrollView(
          slivers: [
            _buildAppBar(user),
            SliverPadding(
              padding: const EdgeInsets.all(20),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  // Stats
                  dashAsync.when(
                    loading: () => _buildStatsShimmer(),
                    error: (e, _) => _buildErrorBanner(e.toString()),
                    data: (stats) => _buildStatsGrid(stats),
                  ),

                  const SizedBox(height: 24),

                  // Quick Actions
                  _buildSectionTitle(s.quickActions),
                  const SizedBox(height: 12),
                  _buildQuickActions(user, s),

                  const SizedBox(height: 24),

                  // Recent customers header
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _buildSectionTitle(s.recentCustomers),
                      TextButton(
                        onPressed: () => context.go('/customers'),
                        child: Text(s.seeAll,
                            style: const TextStyle(
                                fontSize: 13, color: AppConstants.primary)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _buildRecentCustomers(),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _todayFormattedL10n(S s) {
    final now = DateTime.now();
    return '${s.weekdays[now.weekday - 1]}, ${now.day} ${s.months[now.month - 1]}';
  }

  SliverAppBar _buildAppBar(user) {
    final s = S.of(ref);
    return SliverAppBar(
      expandedHeight: 180,
      pinned: true,
      elevation: 0,
      backgroundColor: AppConstants.primary,
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Color(0xFFEA580C),
                Color(0xFFDC2626),
                Color(0xFFC2410C),
              ],
              stops: [0.0, 0.5, 1.0],
            ),
          ),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Top row: greeting + actions
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _todayFormattedL10n(s),
                              style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.white.withValues(alpha: 0.7),
                                  fontWeight: FontWeight.w400),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${_greeting(s)}, ${user?.name.split(' ').first ?? 'FO'}',
                              style: const TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                                letterSpacing: -0.3,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Notification bell
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Stack(
                          children: [
                            const Center(
                              child: Icon(Icons.notifications_outlined,
                                  color: Colors.white, size: 22),
                            ),
                            Positioned(
                              top: 8,
                              right: 8,
                              child: Container(
                                width: 8,
                                height: 8,
                                decoration: const BoxDecoration(
                                  color: Color(0xFF4ADE80),
                                  shape: BoxShape.circle,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 10),
                      // Avatar
                      GestureDetector(
                        onTap: () => context.go('/profile'),
                        child: Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.25),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.3),
                              width: 1.5,
                            ),
                          ),
                          child: Center(
                            child: Text(
                              user?.initials ?? 'FO',
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.w700),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 14),

                  // Branch + Online badge row
                  Row(
                    children: [
                      if (user?.branch != null) ...[
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 5),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.location_on_outlined,
                                  color: Colors.white70, size: 13),
                              const SizedBox(width: 4),
                              Text(
                                user!.branch!.name,
                                style: const TextStyle(
                                    color: Colors.white70, fontSize: 11),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                      ],
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: const Color(0xFF4ADE80).withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.circle,
                                color: Color(0xFF4ADE80), size: 7),
                            SizedBox(width: 5),
                            Text('Online',
                                style: TextStyle(
                                    color: Color(0xFF4ADE80),
                                    fontSize: 11,
                                    fontWeight: FontWeight.w500)),
                          ],
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 14),

                  // Search bar
                  GestureDetector(
                    onTap: () => context.go('/customers'),
                    child: Container(
                      height: 40,
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.search_rounded,
                              color: Colors.white.withValues(alpha: 0.7),
                              size: 18),
                          const SizedBox(width: 10),
                          Text(
                            s.searchCustomers,
                            style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.5),
                                fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatsGrid(stats) {
    final s = S.of(ref);
    final items = [
      _StatData(
          s.total,
          stats.totalRegistered.toString(),
          Icons.people_outline_rounded,
          AppConstants.info,
          const Color(0xFFEFF6FF)),
      _StatData(
          s.pending,
          stats.pending.toString(),
          Icons.hourglass_empty_rounded,
          const Color(0xFFF59E0B),
          const Color(0xFFFFFBEB)),
      _StatData(
          s.verified,
          stats.verified.toString(),
          Icons.check_circle_outline_rounded,
          AppConstants.success,
          const Color(0xFFECFDF5)),
      _StatData(s.declined, stats.declined.toString(), Icons.cancel_outlined,
          AppConstants.error, const Color(0xFFFEF2F2)),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.6,
      children: items
          .asMap()
          .entries
          .map((e) => _AnimatedStatCard(
                data: e.value,
                delay: e.key * 80,
                parentController: _animController,
              ))
          .toList(),
    );
  }

  Widget _buildQuickActions(user, S s) {
    final canRegister = user?.canRegisterCustomers ?? false;

    final actions = [
      if (canRegister)
        _ActionData(s.registerCustomer, Icons.person_add_rounded,
            AppConstants.primary, () => context.go('/kyc/new')),
      _ActionData(s.myCustomers, Icons.people_rounded, AppConstants.info,
          () => context.go('/customers')),
      _ActionData(s.search, Icons.search_rounded, const Color(0xFF8B5CF6),
          () => context.go('/customers')),
      _ActionData(s.drafts, Icons.edit_note_rounded, AppConstants.warning,
          () => context.go('/customers?tab=draft')),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 2.4,
      children: actions.map((a) => _buildActionCard(a)).toList(),
    );
  }

  Widget _buildActionCard(_ActionData action) {
    return GestureDetector(
      onTap: action.onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: action.color.withOpacity(0.08),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: action.color.withOpacity(0.2)),
        ),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: action.color.withOpacity(0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(action.icon, color: action.color, size: 18),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                action.label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: action.color,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRecentCustomers() {
    final listState = ref.watch(customerListProvider);
    if (listState.isLoading && listState.items.isEmpty) {
      return _buildCustomerShimmer();
    }
    if (listState.items.isEmpty) {
      return _buildEmptyState();
    }
    final recent = listState.items.take(5).toList();
    return Column(
      children: recent
          .map((c) => _CustomerTile(
                name: c.fullName,
                phone: c.phone,
                status: c.kycStatus,
                headshotUrl: c.headshotUrl,
                onTap: () => context.go('/customers/${c.id}'),
              ))
          .toList(),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w700,
        color: Theme.of(context).textTheme.bodyLarge?.color,
      ),
    );
  }

  Widget _buildStatsShimmer() {
    final theme = Theme.of(context);
    final shimmerColor = theme.cardTheme.color ?? theme.colorScheme.surface;
    final borderColor = theme.brightness == Brightness.dark
        ? const Color(0xFF2A2D3A)
        : AppConstants.border;
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.6,
      children: List.generate(
          4,
          (_) => Container(
                decoration: BoxDecoration(
                  color: shimmerColor,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: borderColor),
                ),
              )),
    );
  }

  Widget _buildCustomerShimmer() {
    final theme = Theme.of(context);
    final shimmerColor = theme.cardTheme.color ?? theme.colorScheme.surface;
    final borderColor = theme.brightness == Brightness.dark
        ? const Color(0xFF2A2D3A)
        : AppConstants.border;
    return Column(
      children: List.generate(
          3,
          (_) => Container(
                margin: const EdgeInsets.only(bottom: 10),
                height: 64,
                decoration: BoxDecoration(
                  color: shimmerColor,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: borderColor),
                ),
              )),
    );
  }

  Widget _buildEmptyState() {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 32),
      child: Column(
        children: [
          Icon(Icons.people_outline_rounded,
              size: 48, color: theme.dividerColor),
          const SizedBox(height: 12),
          Text(S.of(ref).noCustomersYet,
              style: TextStyle(
                  color: theme.textTheme.bodyMedium?.color,
                  fontWeight: FontWeight.w500)),
          const SizedBox(height: 4),
          Text(S.of(ref).registerFirstCustomer,
              style: TextStyle(
                  fontSize: 12, color: theme.textTheme.bodySmall?.color)),
        ],
      ),
    );
  }

  Widget _buildErrorBanner(String error) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark
            ? AppConstants.error.withValues(alpha: 0.15)
            : const Color(0xFFFEF2F2),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppConstants.error.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline, color: AppConstants.error, size: 18),
          const SizedBox(width: 8),
          Expanded(
              child: Text(error,
                  style: const TextStyle(
                      fontSize: 12, color: AppConstants.error))),
        ],
      ),
    );
  }
}

class _StatData {
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final Color bg;
  const _StatData(this.label, this.value, this.icon, this.color, this.bg);
}

class _ActionData {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _ActionData(this.label, this.icon, this.color, this.onTap);
}

class _AnimatedStatCard extends StatefulWidget {
  final _StatData data;
  final int delay;
  final AnimationController parentController;

  const _AnimatedStatCard({
    required this.data,
    required this.delay,
    required this.parentController,
  });

  @override
  State<_AnimatedStatCard> createState() => _AnimatedStatCardState();
}

class _AnimatedStatCardState extends State<_AnimatedStatCard> {
  late Animation<double> _opacity;
  late Animation<double> _slide;

  @override
  void initState() {
    super.initState();
    final start = widget.delay / 600.0;
    final end = (widget.delay / 600.0 + 0.4).clamp(0.0, 1.0);
    _opacity = Tween<double>(begin: 0, end: 1).animate(CurvedAnimation(
        parent: widget.parentController,
        curve: Interval(start, end, curve: Curves.easeOut)));
    _slide = Tween<double>(begin: 20, end: 0).animate(CurvedAnimation(
        parent: widget.parentController,
        curve: Interval(start, end, curve: Curves.easeOut)));
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.parentController,
      builder: (_, __) => Opacity(
        opacity: _opacity.value,
        child: Transform.translate(
          offset: Offset(0, _slide.value),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: widget.data.bg,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: widget.data.color.withOpacity(0.15)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Icon(widget.data.icon, color: widget.data.color, size: 22),
                    Text(
                      widget.data.value,
                      style: TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.w800,
                        color: widget.data.color,
                        letterSpacing: -0.5,
                      ),
                    ),
                  ],
                ),
                Text(
                  widget.data.label,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: widget.data.color.withOpacity(0.8),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _CustomerTile extends StatelessWidget {
  final String name;
  final String phone;
  final String status;
  final String? headshotUrl;
  final VoidCallback onTap;

  const _CustomerTile({
    required this.name,
    required this.phone,
    required this.status,
    this.headshotUrl,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final cardColor = theme.cardTheme.color ?? theme.colorScheme.surface;
    final borderColor = isDark ? const Color(0xFF2A2D3A) : AppConstants.border;
    final statusColor =
        AppConstants.statusColors[status] ?? AppConstants.textSecondary;
    final statusBg = AppConstants.statusBg[status] ?? AppConstants.borderLight;
    final statusLabel = AppConstants.statusLabels[status] ?? status;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: cardColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: borderColor),
        ),
        child: Row(
          children: [
            CircleAvatar(
              radius: 20,
              backgroundColor: isDark
                  ? AppConstants.primary.withValues(alpha: 0.15)
                  : AppConstants.primarySurface,
              backgroundImage:
                  headshotUrl != null ? NetworkImage(headshotUrl!) : null,
              child: headshotUrl == null
                  ? Text(
                      name.isNotEmpty ? name[0].toUpperCase() : '?',
                      style: const TextStyle(
                          color: AppConstants.primary,
                          fontWeight: FontWeight.w700,
                          fontSize: 14),
                    )
                  : null,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: theme.textTheme.bodyLarge?.color),
                  ),
                  Text(
                    phone,
                    style: TextStyle(
                        fontSize: 11, color: theme.textTheme.bodyMedium?.color),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                  color:
                      isDark ? statusColor.withValues(alpha: 0.15) : statusBg,
                  borderRadius: BorderRadius.circular(20)),
              child: Text(
                statusLabel,
                style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: statusColor),
              ),
            ),
            const SizedBox(width: 4),
            Icon(Icons.chevron_right,
                size: 16, color: theme.textTheme.bodySmall?.color),
          ],
        ),
      ),
    );
  }
}
