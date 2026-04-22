import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/app_icon_assets.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/models/dashboard_model.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/connectivity_provider.dart';
import '../../core/providers/customer_provider.dart';
import '../../core/l10n/app_strings.dart';
import '../../widgets/common/app_color_icon.dart';
import '../../widgets/common/glass_card.dart';

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
    final onlineAsync = ref.watch(onlineStatusProvider);
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
            _buildAppBar(user, dashAsync.valueOrNull, onlineAsync),
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
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildSectionTitle(s.recentCustomers),
                          const SizedBox(height: 4),
                          Text(
                            'Latest registrations and status changes',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: theme.colorScheme.onSurface
                                  .withValues(alpha: 0.55),
                            ),
                          ),
                        ],
                      ),
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

  SliverAppBar _buildAppBar(
    user,
    DashboardStats? stats,
    AsyncValue<bool> onlineAsync,
  ) {
    final s = S.of(ref);
    final insight = stats ?? DashboardStats.empty;
    final isOnline = onlineAsync.maybeWhen(data: (v) => v, orElse: () => true);

    return SliverAppBar(
      expandedHeight: 400,
      pinned: true,
      elevation: 0,
      backgroundColor: AppConstants.heroStart,
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          fit: StackFit.expand,
          children: [
            Container(
              decoration: BoxDecoration(gradient: DesignTokens.heroGradient),
            ),
            Positioned(
              top: -40,
              right: -20,
              child: Container(
                width: 190,
                height: 190,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppConstants.primaryLight.withValues(alpha: 0.12),
                ),
              ),
            ),
            Positioned(
              left: -40,
              bottom: 16,
              child: Container(
                width: 160,
                height: 160,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: DesignTokens.accentSky.withValues(alpha: 0.10),
                ),
              ),
            ),
            SafeArea(
              bottom: false,
              child: Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _todayFormattedL10n(s),
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.white.withValues(
                                    alpha: 0.78,
                                  ),
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                '${_greeting(s)}, ${user?.name.split(' ').first ?? 'FO'}',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.w800,
                                  height: 1.05,
                                  color: Colors.white,
                                  letterSpacing: -0.8,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 16),
                        Row(
                          children: [
                            _heroActionButton(
                              icon: Icons.notifications_none_rounded,
                              dotColor: insight.pending > 0
                                  ? const Color(0xFFFDE68A)
                                  : null,
                              badgeCount: insight.pending > 0
                                  ? insight.pending
                                  : null,
                              semanticsLabel: insight.pending > 0
                                  ? 'View ${insight.pending} pending applications'
                                  : 'No pending applications',
                              onTap: insight.pending > 0
                                  ? () =>
                                      context.go('/customers?tab=pending')
                                  : null,
                            ),
                            const SizedBox(width: 10),
                            GestureDetector(
                              onTap: () => context.go('/profile'),
                              child: Container(
                                width: 48,
                                height: 48,
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(
                                    alpha: 0.18,
                                  ),
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(
                                    color: Colors.white.withValues(
                                      alpha: 0.34,
                                    ),
                                    width: 1.5,
                                  ),
                                ),
                                child: Center(
                                  child: Text(
                                    user?.initials ?? 'FO',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 16,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    Expanded(
                          child: SingleChildScrollView(
                            physics: const ClampingScrollPhysics(),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 14),
                                Wrap(
                                  spacing: 10,
                                  runSpacing: 10,
                                  children: [
                                    if (user?.branch != null)
                                      _heroChip(
                                        icon: Icons.location_on_outlined,
                                        label: user!.branch!.name,
                                      ),
                                    _heroChip(
                                      icon: Icons.circle,
                                      label: isOnline ? 'Online' : 'Offline',
                                      iconColor: isOnline
                                          ? const Color(0xFF4ADE80)
                                          : AppConstants.textHint,
                                    ),
                                    _heroChip(
                                      icon: Icons.inventory_2_outlined,
                                      label: '${insight.drafts} drafts',
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                GestureDetector(
                                  onTap: () => context.go('/customers'),
                                  child: Container(
                                    height: 52,
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 16,
                                    ),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(
                                        alpha: 0.18,
                                      ),
                                      borderRadius: BorderRadius.circular(18),
                                      border: Border.all(
                                        color: Colors.white.withValues(
                                          alpha: 0.16,
                                        ),
                                      ),
                                    ),
                                    child: Row(
                                      children: [
                                        const Icon(
                                          Icons.search_rounded,
                                          color: Colors.white,
                                          size: 20,
                                        ),
                                        const SizedBox(width: 12),
                                        Text(
                                          s.searchCustomers,
                                          style: TextStyle(
                                            color: Colors.white.withValues(
                                              alpha: 0.82,
                                            ),
                                            fontSize: 14,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 12),
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.all(16),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.14),
                                    borderRadius: BorderRadius.circular(24),
                                    border: Border.all(
                                      color: Colors.white.withValues(
                                        alpha: 0.12,
                                      ),
                                    ),
                                  ),
                                  child: Row(
                                    children: [
                                      Expanded(
                                        child: _heroInsight(
                                          label: 'Pending review',
                                          value: '${insight.pending}',
                                          tone: const Color(0xFFFDE68A),
                                        ),
                                      ),
                                      Container(
                                        width: 1,
                                        height: 42,
                                        color: Colors.white.withValues(
                                          alpha: 0.16,
                                        ),
                                      ),
                                      Expanded(
                                        child: _heroInsight(
                                          label: 'Verified today',
                                          value: '${insight.verified}',
                                          tone: const Color(0xFF86EFAC),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _heroActionButton({
    required IconData icon,
    Color? dotColor,
    int? badgeCount,
    VoidCallback? onTap,
    String? semanticsLabel,
  }) {
    final child = Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Center(
            child: Icon(icon, color: Colors.white, size: 22),
          ),
          if (dotColor != null && badgeCount == null)
            Positioned(
              top: 10,
              right: 10,
              child: Container(
                width: 10,
                height: 10,
                decoration: BoxDecoration(
                  color: dotColor,
                  shape: BoxShape.circle,
                ),
              ),
            ),
          if (badgeCount != null && badgeCount > 0)
            Positioned(
              top: 4,
              right: 2,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                decoration: BoxDecoration(
                  color: AppConstants.primary,
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.5)),
                ),
                constraints: const BoxConstraints(minWidth: 18, minHeight: 18),
                child: Text(
                  badgeCount > 99 ? '99+' : '$badgeCount',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 9,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ),
        ],
      ),
    );

    if (onTap == null) {
      return Semantics(
        label: semanticsLabel ?? 'Notifications',
        child: child,
      );
    }

    return Semantics(
      button: true,
      label: semanticsLabel ?? 'Notifications',
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: onTap,
          child: child,
        ),
      ),
    );
  }

  Widget _heroChip({
    required IconData icon,
    required String label,
    Color? iconColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: icon == Icons.circle ? 8 : 14,
            color: iconColor ?? Colors.white.withValues(alpha: 0.92),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  Widget _heroInsight({
    required String label,
    required String value,
    required Color tone,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.72),
              fontSize: 11,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              color: tone,
              fontSize: 20,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsGrid(DashboardStats stats) {
    final s = S.of(ref);
    final items = [
      _StatData(
        label: s.total,
        value: stats.totalRegistered.toString(),
        note: 'Customers onboarded',
        iconAsset: AppIconAssets.customers,
        color: DesignTokens.statBlue,
        background: DesignTokens.statBlueBg,
        accent: DesignTokens.statBlueAccent,
      ),
      _StatData(
        label: s.drafts,
        value: stats.drafts.toString(),
        note: 'Need completion',
        iconAsset: AppIconAssets.drafts,
        color: DesignTokens.statAmber,
        background: DesignTokens.statAmberBg,
        accent: DesignTokens.statAmberAccent,
      ),
      _StatData(
        label: s.pending,
        value: stats.pending.toString(),
        note: 'Waiting review',
        iconAsset: AppIconAssets.pending,
        color: DesignTokens.statViolet,
        background: DesignTokens.statVioletBg,
        accent: DesignTokens.statVioletAccent,
      ),
      _StatData(
        label: s.verified,
        value: stats.verified.toString(),
        note: 'Ready to release',
        iconAsset: AppIconAssets.verified,
        color: DesignTokens.statGreen,
        background: DesignTokens.statGreenBg,
        accent: DesignTokens.statGreenAccent,
      ),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 14,
      mainAxisSpacing: 14,
      childAspectRatio: 1.08,
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
        _ActionData(
          label: s.registerCustomer,
          caption: 'Start a new KYC flow',
          iconAsset: AppIconAssets.register,
          color: AppConstants.primary,
          surface: const Color(0xFFFFF4EC),
          onTap: () => context.go('/kyc/new'),
        ),
      _ActionData(
        label: s.myCustomers,
        caption: 'Review active customers',
        iconAsset: AppIconAssets.customers,
        color: DesignTokens.statBlue,
        surface: DesignTokens.statBlueBg,
        onTap: () => context.go('/customers'),
      ),
      _ActionData(
        label: s.search,
        caption: 'Find by name, phone or NIDA',
        iconAsset: AppIconAssets.search,
        color: DesignTokens.statViolet,
        surface: DesignTokens.statVioletBg,
        onTap: () => context.go('/customers'),
      ),
      _ActionData(
        label: s.drafts,
        caption: 'Resume unfinished onboarding',
        iconAsset: AppIconAssets.checklist,
        color: DesignTokens.statAmber,
        surface: DesignTokens.statAmberBg,
        onTap: () => context.go('/customers?tab=draft'),
      ),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 14,
      mainAxisSpacing: 14,
      childAspectRatio: 1.22,
      children: actions.map((a) => _buildActionCard(a)).toList(),
    );
  }

  Widget _buildActionCard(_ActionData action) {
    return GestureDetector(
      onTap: action.onTap,
      child: GlassCard(
        tint: action.surface,
        borderRadius: BorderRadius.circular(22),
        borderColor: action.color.withValues(alpha: 0.22),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: action.color.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Center(
                child: AppColorIcon(
                  assetName: action.iconAsset,
                  size: 24,
                  semanticsLabel: action.label,
                ),
              ),
            ),
            const Spacer(),
            Text(
              action.label,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: action.color,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              action.caption,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppConstants.textSecondary,
                height: 1.35,
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
        fontSize: 18,
        fontWeight: FontWeight.w800,
        color: Theme.of(context).textTheme.bodyLarge?.color,
        letterSpacing: -0.4,
      ),
    );
  }

  Widget _buildStatsShimmer() {
    final theme = Theme.of(context);
    final shimmerColor = theme.cardTheme.color ?? theme.colorScheme.surface;
    final borderColor = theme.brightness == Brightness.dark
        ? DesignTokens.darkBorder
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
        ? DesignTokens.darkBorder
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
          AppColorIcon(
            assetName: AppIconAssets.customers,
            size: 48,
            opacity: 0.45,
            tintColor: theme.dividerColor,
            semanticsLabel: 'No customers',
          ),
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
  final String note;
  final String iconAsset;
  final Color color;
  final Color background;
  final Color accent;

  const _StatData({
    required this.label,
    required this.value,
    required this.note,
    required this.iconAsset,
    required this.color,
    required this.background,
    required this.accent,
  });
}

class _ActionData {
  final String label;
  final String caption;
  final String iconAsset;
  final Color color;
  final Color surface;
  final VoidCallback onTap;

  const _ActionData({
    required this.label,
    required this.caption,
    required this.iconAsset,
    required this.color,
    required this.surface,
    required this.onTap,
  });
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
          child: GlassCard(
            tint: widget.data.background,
            borderRadius: BorderRadius.circular(24),
            borderColor: widget.data.color.withValues(alpha: 0.18),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: widget.data.accent,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Center(
                        child: AppColorIcon(
                          assetName: widget.data.iconAsset,
                          size: 24,
                          semanticsLabel: widget.data.label,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.7),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        widget.data.value,
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: widget.data.color,
                          letterSpacing: -0.6,
                        ),
                      ),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.data.label,
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: widget.data.color,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      widget.data.note,
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: AppConstants.textSecondary,
                      ),
                    ),
                  ],
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
    final borderColor = isDark ? DesignTokens.darkBorder : AppConstants.border;
    final statusColor =
        AppConstants.statusColors[status] ?? AppConstants.textSecondary;
    final statusBg = AppConstants.statusBg[status] ?? AppConstants.borderLight;
    final statusLabel = AppConstants.statusLabels[status] ?? status;

    return GestureDetector(
      onTap: onTap,
      child: GlassCard(
        tint: Colors.white,
        borderColor: borderColor,
        borderRadius: BorderRadius.circular(20),
        padding: const EdgeInsets.all(14),
        boxShadow: const [
          BoxShadow(
            color: Color(0x120B1220),
            blurRadius: 18,
            offset: Offset(0, 12),
          ),
        ],
        child: Row(
          children: [
            _CustomerAvatar(
              name: name,
              imageUrl: headshotUrl,
              isDark: isDark,
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: theme.textTheme.bodyLarge?.color),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    phone,
                    style: TextStyle(
                        fontSize: 12, color: theme.textTheme.bodyMedium?.color),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                  color:
                      isDark ? statusColor.withValues(alpha: 0.15) : statusBg,
                  borderRadius: BorderRadius.circular(999)),
              child: Text(
                statusLabel,
                style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: statusColor),
              ),
            ),
            const SizedBox(width: 8),
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: AppConstants.surfaceMuted,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(Icons.chevron_right_rounded,
                  size: 18, color: theme.textTheme.bodySmall?.color),
            ),
          ],
        ),
      ),
    );
  }
}

class _CustomerAvatar extends StatelessWidget {
  final String name;
  final String? imageUrl;
  final bool isDark;

  const _CustomerAvatar({
    required this.name,
    required this.imageUrl,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

    return Container(
      width: 48,
      height: 48,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: isDark
            ? AppConstants.primary.withValues(alpha: 0.15)
            : AppConstants.primarySurface,
        shape: BoxShape.circle,
      ),
      child: imageUrl == null
          ? Center(
              child: Text(
                initial,
                style: const TextStyle(
                  color: AppConstants.primary,
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
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
                    color: AppConstants.primary,
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
              ),
            ),
    );
  }
}
