import 'dart:math' as math;
import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
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
import '../../widgets/common/premium_glass_background.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen>
    with TickerProviderStateMixin {
  late AnimationController _animController;
  late AnimationController _ambientController;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
    _ambientController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 6),
    )..repeat(reverse: true);
    Future.delayed(const Duration(milliseconds: 100), _animController.forward);
  }

  @override
  void dispose() {
    _animController.dispose();
    _ambientController.dispose();
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
      backgroundColor: Colors.transparent,
      body: PremiumGlassBackground(
        child: RefreshIndicator(
          color: AppConstants.primary,
          onRefresh: () async {
            ref.read(dashboardProvider.notifier).load();
          },
          child: CustomScrollView(
            slivers: [
              _buildAppBar(user, dashAsync.valueOrNull, onlineAsync),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    dashAsync.when(
                      loading: () => _buildStatsShimmer(),
                      error: (e, _) => _buildErrorBanner(e.toString()),
                      data: (stats) => _buildStatsGrid(stats),
                    ),
                    const SizedBox(height: 28),
                    _DashboardSectionHeader(
                      title: s.quickActions,
                      subtitle: 'Shortcuts for your daily field work',
                    ),
                    const SizedBox(height: 14),
                    _buildQuickActions(user, s),
                    const SizedBox(height: 28),
                    _DashboardSectionHeader(
                      title: s.recentCustomers,
                      subtitle: 'Latest registrations and status changes',
                      trailing: TextButton(
                        onPressed: () => context.go('/customers'),
                        style: TextButton.styleFrom(
                          foregroundColor: theme.colorScheme.primary,
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                        ),
                        child: Text(
                          s.seeAll,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 13,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),
                    _buildRecentCustomers(),
                  ]),
                ),
              ),
            ],
          ),
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
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final reduceMotion = MediaQuery.of(context).disableAnimations;

    return SliverAppBar(
      expandedHeight: 388,
      pinned: true,
      stretch: true,
      elevation: 0,
      backgroundColor:
          isDark ? DesignTokens.loginDeep : DesignTokens.loginNavy,
      flexibleSpace: FlexibleSpaceBar(
        stretchModes: const [StretchMode.zoomBackground],
        background: Stack(
          fit: StackFit.expand,
          children: [
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: isDark
                    ? DesignTokens.loginHeroMeshDark
                    : DesignTokens.loginHeroMesh,
              ),
            ),
            CustomPaint(
              painter: _DashboardGridPainter(
                color: Colors.white.withValues(alpha: 0.035),
              ),
            ),
            if (!reduceMotion)
              AnimatedBuilder(
                animation: _ambientController,
                builder: (_, __) {
                  final drift = math.sin(_ambientController.value * math.pi * 2) * 10;
                  return Stack(
                    children: [
                      Positioned(
                        top: -20 + drift,
                        right: -24,
                        child: _heroGlowOrb(
                          150,
                          AppConstants.primary.withValues(alpha: 0.2),
                        ),
                      ),
                      Positioned(
                        left: -36,
                        bottom: 48 - drift,
                        child: _heroGlowOrb(
                          120,
                          DesignTokens.loginSkyGlow.withValues(alpha: 0.14),
                        ),
                      ),
                    ],
                  );
                },
              ),
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: DesignTokens.dashboardHeroOverlay,
              ),
            ),
            SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 18),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 5,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(999),
                                  border: Border.all(
                                    color: Colors.white.withValues(alpha: 0.2),
                                  ),
                                ),
                                child: Text(
                                  _todayFormattedL10n(s),
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: Colors.white.withValues(alpha: 0.9),
                                    letterSpacing: 0.3,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                _greeting(s),
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.white.withValues(alpha: 0.75),
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                user?.name.split(' ').first ?? 'Officer',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 30,
                                  fontWeight: FontWeight.w800,
                                  height: 1.05,
                                  color: Colors.white,
                                  letterSpacing: -0.8,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        _heroActionButton(
                          icon: Icons.notifications_none_rounded,
                          badgeCount:
                              insight.pending > 0 ? insight.pending : null,
                          semanticsLabel: insight.pending > 0
                              ? 'View ${insight.pending} pending applications'
                              : 'No pending applications',
                          onTap: insight.pending > 0
                              ? () => context.go('/customers?tab=pending')
                              : null,
                        ),
                        const SizedBox(width: 10),
                        GestureDetector(
                          onTap: () => context.go('/profile'),
                          child: Container(
                            padding: const EdgeInsets.all(2.5),
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: LinearGradient(
                                colors: [
                                  AppConstants.primary,
                                  DesignTokens.loginSkyGlow.withValues(alpha: 0.7),
                                ],
                              ),
                            ),
                            child: Container(
                              width: 46,
                              height: 46,
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.14),
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: Colors.white.withValues(alpha: 0.25),
                                ),
                              ),
                              child: Center(
                                child: Text(
                                  user?.initials ?? 'FO',
                                  style: GoogleFonts.plusJakartaSans(
                                    color: Colors.white,
                                    fontSize: 15,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        if (user?.branch != null)
                          _heroChip(
                            icon: Icons.location_on_outlined,
                            label: user!.branch!.name,
                          ),
                        if (user?.dealer != null)
                          _heroChip(
                            icon: Icons.storefront_outlined,
                            label: user!.dealer!.name,
                          ),
                        _heroChip(
                          icon: Icons.circle,
                          label: isOnline ? 'Online' : 'Offline',
                          iconColor: isOnline
                              ? const Color(0xFF4ADE80)
                              : AppConstants.textHint,
                        ),
                      ],
                    ),
                    const Spacer(),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(20),
                      child: BackdropFilter(
                        filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            onTap: () => context.go('/customers'),
                            child: Container(
                              height: 54,
                              padding: const EdgeInsets.symmetric(horizontal: 16),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.14),
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(
                                  color: Colors.white.withValues(alpha: 0.22),
                                ),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    width: 36,
                                    height: 36,
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.16),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Icon(
                                      Icons.search_rounded,
                                      color: Colors.white,
                                      size: 20,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      s.searchCustomers,
                                      style: GoogleFonts.plusJakartaSans(
                                        color: Colors.white.withValues(alpha: 0.88),
                                        fontSize: 14,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                  Icon(
                                    Icons.arrow_forward_rounded,
                                    color: Colors.white.withValues(alpha: 0.7),
                                    size: 18,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    _DashboardHeroInsights(insight: insight),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _heroGlowOrb(double size, Color color) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [color, color.withValues(alpha: 0)],
        ),
      ),
    );
  }

  Widget _heroActionButton({
    required IconData icon,
    int? badgeCount,
    VoidCallback? onTap,
    String? semanticsLabel,
  }) {
    final child = Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Center(
            child: Icon(icon, color: Colors.white, size: 22),
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

  Widget _buildStatsGrid(DashboardStats stats) {
    final s = S.of(ref);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final items = [
      _StatData(
        label: s.total,
        value: stats.totalRegistered.toString(),
        note: 'Customers onboarded',
        iconAsset: AppIconAssets.customers,
        color: DesignTokens.statBlue,
        background:
            isDark ? DesignTokens.statBlueBgDark : DesignTokens.statBlueBg,
        accent: isDark
            ? DesignTokens.statBlueAccentDark
            : DesignTokens.statBlueAccent,
      ),
      _StatData(
        label: s.drafts,
        value: stats.drafts.toString(),
        note: 'Need completion',
        iconAsset: AppIconAssets.drafts,
        color: DesignTokens.statAmber,
        background:
            isDark ? DesignTokens.statAmberBgDark : DesignTokens.statAmberBg,
        accent: isDark
            ? DesignTokens.statAmberAccentDark
            : DesignTokens.statAmberAccent,
      ),
      _StatData(
        label: s.pending,
        value: stats.pending.toString(),
        note: 'Waiting review',
        iconAsset: AppIconAssets.pending,
        color: DesignTokens.statViolet,
        background: isDark
            ? DesignTokens.statVioletBgDark
            : DesignTokens.statVioletBg,
        accent: isDark
            ? DesignTokens.statVioletAccentDark
            : DesignTokens.statVioletAccent,
      ),
      _StatData(
        label: s.verified,
        value: stats.verified.toString(),
        note: 'Ready to release',
        iconAsset: AppIconAssets.verified,
        color: DesignTokens.statGreen,
        background: isDark
            ? DesignTokens.statGreenBgDark
            : DesignTokens.statGreenBg,
        accent: isDark
            ? DesignTokens.statGreenAccentDark
            : DesignTokens.statGreenAccent,
      ),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 14,
      mainAxisSpacing: 14,
      childAspectRatio: 1.02,
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
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final actions = [
      if (canRegister)
        _ActionData(
          label: s.registerCustomer,
          caption: 'Start a new KYC flow',
          iconAsset: AppIconAssets.register,
          color: AppConstants.primary,
          surface: isDark
              ? DesignTokens.statRegisterBgDark
              : const Color(0xFFFFF4EC),
          onTap: () => context.go('/kyc/new'),
        ),
      if (canRegister)
        _ActionData(
          label: 'KYC Approvals',
          caption: 'HQ queue — approve stages',
          iconAsset: AppIconAssets.verified,
          color: DesignTokens.statViolet,
          surface: isDark
              ? DesignTokens.statVioletBgDark
              : DesignTokens.statVioletBg,
          onTap: () => context.go('/kyc/approvals'),
        ),
      _ActionData(
        label: s.myCustomers,
        caption: 'Review active customers',
        iconAsset: AppIconAssets.customers,
        color: DesignTokens.statBlue,
        surface:
            isDark ? DesignTokens.statBlueBgDark : DesignTokens.statBlueBg,
        onTap: () => context.go('/customers'),
      ),
      _ActionData(
        label: s.search,
        caption: 'Find by name, phone or NIDA',
        iconAsset: AppIconAssets.search,
        color: DesignTokens.statViolet,
        surface: isDark
            ? DesignTokens.statVioletBgDark
            : DesignTokens.statVioletBg,
        onTap: () => context.go('/customers'),
      ),
      _ActionData(
        label: s.drafts,
        caption: 'Resume unfinished onboarding',
        iconAsset: AppIconAssets.checklist,
        color: DesignTokens.statAmber,
        surface: isDark
            ? DesignTokens.statAmberBgDark
            : DesignTokens.statAmberBg,
        onTap: () => context.go('/customers?tab=draft'),
      ),
      if ((user?.canViewStock ?? false) ||
          (user?.canViewStaffMetrics ?? false) ||
          (user?.canViewRecovery ?? false))
        _ActionData(
          label: 'Operations',
          caption: 'Stock, metrics & recovery',
          iconAsset: AppIconAssets.search,
          color: DesignTokens.statBlue,
          surface:
              isDark ? DesignTokens.statBlueBgDark : DesignTokens.statBlueBg,
          onTap: () => context.go('/operations'),
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
    final captionColor = Theme.of(context).textTheme.bodyMedium?.color;
    final isPrimary = action.color == AppConstants.primary;

    return GestureDetector(
      onTap: action.onTap,
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: action.color.withValues(alpha: 0.14),
              blurRadius: 24,
              offset: const Offset(0, 12),
            ),
          ],
          gradient: isPrimary
              ? LinearGradient(
                  colors: [
                    action.color.withValues(alpha: 0.45),
                    DesignTokens.loginSkyGlow.withValues(alpha: 0.25),
                  ],
                )
              : null,
          color: isPrimary ? null : action.surface.withValues(alpha: 0.5),
        ),
        child: Padding(
          padding: EdgeInsets.all(isPrimary ? 1.2 : 0),
          child: GlassCard(
            tint: action.surface,
            borderRadius: BorderRadius.circular(isPrimary ? 22.8 : 24),
            borderColor: action.color.withValues(alpha: 0.3),
            blurSigma: 22,
            padding: const EdgeInsets.all(16),
            boxShadow: const [],
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        gradient: DesignTokens.statCardSheen(action.color),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: action.color.withValues(alpha: 0.2),
                        ),
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
                    Container(
                      width: 30,
                      height: 30,
                      decoration: BoxDecoration(
                        color: action.color.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(
                        Icons.arrow_outward_rounded,
                        size: 16,
                        color: action.color,
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                Text(
                  action.label,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: action.color,
                    letterSpacing: -0.2,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  action.caption,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: captionColor,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
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
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: borderColor),
          ),
        ),
      ),
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
    final start = widget.delay / 900.0;
    final end = (widget.delay / 900.0 + 0.45).clamp(0.0, 1.0);
    _opacity = Tween<double>(begin: 0, end: 1).animate(CurvedAnimation(
        parent: widget.parentController,
        curve: Interval(start, end, curve: Curves.easeOut)));
    _slide = Tween<double>(begin: 20, end: 0).animate(CurvedAnimation(
        parent: widget.parentController,
        curve: Interval(start, end, curve: Curves.easeOut)));
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final noteColor = theme.textTheme.bodyMedium?.color;
    final valueChipBg = isDark
        ? widget.data.color.withValues(alpha: 0.14)
        : Colors.white.withValues(alpha: 0.7);

    return AnimatedBuilder(
      animation: widget.parentController,
      builder: (_, __) => Opacity(
        opacity: _opacity.value,
        child: Transform.translate(
          offset: Offset(0, _slide.value),
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(26),
              gradient: LinearGradient(
                colors: [
                  widget.data.color.withValues(alpha: 0.5),
                  widget.data.color.withValues(alpha: 0.12),
                ],
              ),
              boxShadow: [
                BoxShadow(
                  color: widget.data.color.withValues(alpha: isDark ? 0.15 : 0.1),
                  blurRadius: 22,
                  offset: const Offset(0, 12),
                ),
              ],
            ),
            child: Padding(
              padding: const EdgeInsets.all(1.1),
              child: GlassCard(
                tint: widget.data.background,
                borderRadius: BorderRadius.circular(24.9),
                borderColor: Colors.transparent,
                blurSigma: 20,
                padding: const EdgeInsets.all(16),
                boxShadow: const [],
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          width: 46,
                          height: 46,
                          decoration: BoxDecoration(
                            gradient: DesignTokens.statCardSheen(widget.data.color),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: widget.data.color.withValues(alpha: 0.22),
                            ),
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
                            horizontal: 12,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            color: valueChipBg,
                            borderRadius: BorderRadius.circular(999),
                            border: Border.all(
                              color: widget.data.color.withValues(alpha: 0.2),
                            ),
                          ),
                          child: Text(
                            widget.data.value,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 22,
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
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: widget.data.color,
                          ),
                        ),
                        const SizedBox(height: 5),
                        Text(
                          widget.data.note,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: noteColor,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
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
    final statusColor =
        AppConstants.statusColors[status] ?? AppConstants.textSecondary;
    final statusBg = AppConstants.statusBg[status] ?? AppConstants.borderLight;
    final statusLabel = AppConstants.statusLabels[status] ?? status;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            boxShadow: [
              BoxShadow(
                color: statusColor.withValues(alpha: 0.08),
                blurRadius: 16,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(22),
            child: Stack(
              children: [
                GlassCard.surface(
                  context,
                  borderRadius: BorderRadius.circular(22),
                  blurSigma: 20,
                  padding: const EdgeInsets.fromLTRB(18, 14, 14, 14),
                  child: Row(
                    children: [
                      _CustomerAvatar(
                        name: name,
                        imageUrl: headshotUrl,
                        isDark: isDark,
                        ringColor: statusColor,
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
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                                color: theme.textTheme.bodyLarge?.color,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              phone,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 12,
                                fontWeight: FontWeight.w500,
                                color: theme.textTheme.bodyMedium?.color,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: isDark
                                  ? statusColor.withValues(alpha: 0.18)
                                  : statusBg,
                              borderRadius: BorderRadius.circular(999),
                              border: Border.all(
                                color: statusColor.withValues(alpha: 0.25),
                              ),
                            ),
                            child: Text(
                              statusLabel,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: statusColor,
                              ),
                            ),
                          ),
                          const SizedBox(height: 8),
                          Container(
                            width: 30,
                            height: 30,
                            decoration: BoxDecoration(
                              gradient: DesignTokens.statCardSheen(statusColor),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Icon(
                              Icons.chevron_right_rounded,
                              size: 18,
                              color: statusColor,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Positioned(
                  left: 0,
                  top: 0,
                  bottom: 0,
                  child: Container(
                    width: 4,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          statusColor,
                          statusColor.withValues(alpha: 0.35),
                        ],
                      ),
                    ),
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

class _DashboardSectionHeader extends StatelessWidget {
  const _DashboardSectionHeader({
    required this.title,
    required this.subtitle,
    this.trailing,
  });

  final String title;
  final String subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 4,
          height: 42,
          margin: const EdgeInsets.only(top: 2, right: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            gradient: const LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                AppConstants.primary,
                DesignTokens.loginSkyGlow,
              ],
            ),
          ),
        ),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: theme.textTheme.bodyLarge?.color,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: theme.textTheme.bodyMedium?.color,
                ),
              ),
            ],
          ),
        ),
        if (trailing != null) trailing!,
      ],
    );
  }
}

class _DashboardHeroInsights extends StatelessWidget {
  const _DashboardHeroInsights({required this.insight});

  final DashboardStats insight;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(22),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
          ),
          child: Row(
            children: [
              _insightCell(
                Icons.hourglass_top_rounded,
                'Pending',
                '${insight.pending}',
                const Color(0xFFFDE68A),
              ),
              _divider(),
              _insightCell(
                Icons.verified_rounded,
                'Verified',
                '${insight.verified}',
                const Color(0xFF86EFAC),
              ),
              _divider(),
              _insightCell(
                Icons.edit_note_rounded,
                'Drafts',
                '${insight.drafts}',
                DesignTokens.loginCoralGlow,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _divider() => Container(
        width: 1,
        height: 44,
        color: Colors.white.withValues(alpha: 0.14),
      );

  Widget _insightCell(
    IconData icon,
    String label,
    String value,
    Color tone,
  ) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, color: tone, size: 18),
          const SizedBox(height: 6),
          Text(
            value,
            style: GoogleFonts.plusJakartaSans(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              color: Colors.white.withValues(alpha: 0.72),
              fontSize: 10,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _DashboardGridPainter extends CustomPainter {
  _DashboardGridPainter({required this.color});

  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1;
    const step = 32.0;
    for (var x = 0.0; x < size.width; x += step) {
      canvas.drawLine(Offset(x, 0), Offset(x, size.height), paint);
    }
    for (var y = 0.0; y < size.height; y += step) {
      canvas.drawLine(Offset(0, y), Offset(size.width, y), paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _CustomerAvatar extends StatelessWidget {
  final String name;
  final String? imageUrl;
  final bool isDark;
  final Color ringColor;

  const _CustomerAvatar({
    required this.name,
    required this.imageUrl,
    required this.isDark,
    this.ringColor = AppConstants.primary,
  });

  @override
  Widget build(BuildContext context) {
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';

    return Container(
      padding: const EdgeInsets.all(2),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: LinearGradient(
          colors: [
            ringColor,
            ringColor.withValues(alpha: 0.4),
          ],
        ),
      ),
      child: Container(
        width: 46,
        height: 46,
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
                  style: GoogleFonts.plusJakartaSans(
                    color: AppConstants.primary,
                    fontWeight: FontWeight.w800,
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
                    style: GoogleFonts.plusJakartaSans(
                      color: AppConstants.primary,
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                    ),
                  ),
                ),
              ),
      ),
    );
  }
}
