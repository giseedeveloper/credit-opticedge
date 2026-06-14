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
    with SingleTickerProviderStateMixin {
  late AnimationController _ambientController;

  @override
  void initState() {
    super.initState();
    _ambientController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 6),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
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

    final isDark = theme.brightness == Brightness.dark;
    final scrollSheetColor = isDark
        ? const Color(0xFF0F172A).withValues(alpha: 0.97)
        : Colors.white.withValues(alpha: 0.97);

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: PremiumGlassBackground(
        child: Column(
          children: [
            _buildFixedHeroHeader(
              user,
              dashAsync.valueOrNull,
              onlineAsync,
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppConstants.primary,
                onRefresh: () async {
                  await ref.read(dashboardProvider.notifier).load();
                  ref.invalidate(recentCustomersProvider);
                  await ref
                      .read(customerListProvider.notifier)
                      .load(reset: true);
                },
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(
                    parent: BouncingScrollPhysics(),
                  ),
                  padding: EdgeInsets.zero,
                  children: [
                    Transform.translate(
                      offset: const Offset(0, -10),
                      child: Container(
                        decoration: BoxDecoration(
                          color: scrollSheetColor,
                          borderRadius: const BorderRadius.vertical(
                            top: Radius.circular(28),
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black
                                  .withValues(alpha: isDark ? 0.28 : 0.07),
                              blurRadius: 24,
                              offset: const Offset(0, -6),
                            ),
                          ],
                        ),
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 22, 20, 28),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              dashAsync.when(
                                loading: () => const SizedBox.shrink(),
                                error: (e, _) =>
                                    _buildErrorBanner(e.toString()),
                                data: (stats) => stats.actions.isNotEmpty
                                    ? _buildActionInbox(stats)
                                    : const SizedBox.shrink(),
                              ),
                              const SizedBox(height: 14),
                              _DashboardSectionHeader(
                                title: s.quickActions,
                                subtitle:
                                    'Shortcuts for your daily field work',
                                compact: true,
                              ),
                              Transform.translate(
                                offset: const Offset(0, -18),
                                child: _buildQuickActions(user, s),
                              ),
                              Transform.translate(
                                offset: const Offset(0, -14),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    _DashboardSectionHeader(
                                      title: s.recentCustomers,
                                      subtitle:
                                          'Latest registrations and status changes',
                                      trailing: TextButton(
                                        onPressed: () =>
                                            context.go('/customers'),
                                        style: TextButton.styleFrom(
                                          foregroundColor:
                                              theme.colorScheme.primary,
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                          ),
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
                                    const SizedBox(height: 4),
                                    _buildRecentCustomers(),
                                  ],
                                ),
                              ),
                            ],
                          ),
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

  String _todayFormattedL10n(S s) {
    final now = DateTime.now();
    return '${s.weekdays[now.weekday - 1]}, ${now.day} ${s.months[now.month - 1]}';
  }

  Widget _buildFixedHeroHeader(
    user,
    DashboardStats? stats,
    AsyncValue<bool> onlineAsync,
  ) {
    final s = S.of(ref);
    final insight = stats ?? DashboardStats.empty;
    final isOnline = onlineAsync.maybeWhen(data: (v) => v, orElse: () => true);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final reduceMotion = MediaQuery.of(context).disableAnimations;

    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: isDark
            ? DesignTokens.loginHeroMeshDark
            : DesignTokens.loginHeroMesh,
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Positioned.fill(
            child: IgnorePointer(
              child: CustomPaint(
                painter: _DashboardGridPainter(
                  color: Colors.white.withValues(alpha: 0.035),
                ),
              ),
            ),
          ),
          if (!reduceMotion)
            Positioned.fill(
              child: IgnorePointer(
                child: AnimatedBuilder(
                  animation: _ambientController,
                  builder: (_, __) {
                    final drift =
                        math.sin(_ambientController.value * math.pi * 2) * 10;
                    return Stack(
                      clipBehavior: Clip.none,
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
              ),
            ),
          Positioned.fill(
            child: IgnorePointer(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: DesignTokens.dashboardHeroOverlay,
                ),
              ),
            ),
          ),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 10, 20, 24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
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
                      const SizedBox(width: 8),
                      _heroActionButton(
                        icon: Icons.notifications_none_rounded,
                        badgeCount: insight.actionableCount > 0
                            ? insight.actionableCount
                            : null,
                        semanticsLabel: insight.actionableCount > 0
                            ? 'View ${insight.actionableCount} actionable items'
                            : 'Open action inbox',
                        onTap: _openActionInbox,
                        size: 40,
                      ),
                      const SizedBox(width: 8),
                      _heroProfileAvatar(user, size: 40),
                    ],
                  ),
                  const SizedBox(height: 12),
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
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(20),
                      child: BackdropFilter(
                        filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            onTap: () => context.go('/customers'),
                            child: Container(
                              height: 48,
                              padding:
                                  const EdgeInsets.symmetric(horizontal: 16),
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
                                      color:
                                          Colors.white.withValues(alpha: 0.16),
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
                                        color:
                                            Colors.white.withValues(alpha: 0.88),
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
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: _DashboardHeroInsights(insight: insight),
                  ),
                ],
              ),
            ),
          ),
        ],
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
    required VoidCallback onTap,
    String? semanticsLabel,
    double size = 48,
  }) {
    final radius = size * 0.33;
    final iconSize = size * 0.45;

    final child = Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Center(
            child: Icon(icon, color: Colors.white, size: iconSize),
          ),
          if (badgeCount != null && badgeCount > 0)
            Positioned(
              top: 3,
              right: 1,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                decoration: BoxDecoration(
                  color: AppConstants.primary,
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.5)),
                ),
                constraints: BoxConstraints(
                  minWidth: size * 0.38,
                  minHeight: size * 0.38,
                ),
                child: Text(
                  badgeCount > 99 ? '99+' : '$badgeCount',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: size * 0.2,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ),
        ],
      ),
    );

    return Semantics(
      button: true,
      label: semanticsLabel ?? 'Notifications',
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: onTap,
        child: child,
      ),
    );
  }

  Widget _heroProfileAvatar(dynamic user, {double size = 40}) {
    final innerSize = size - 4;

    return Semantics(
      button: true,
      label: 'Open profile',
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => context.go('/profile'),
        child: Container(
          width: size,
          height: size,
          padding: const EdgeInsets.all(2),
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
            width: innerSize,
            height: innerSize,
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
                  fontSize: size * 0.32,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
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

  void _openActionInbox() {
    final stats = ref.read(dashboardProvider).valueOrNull;
    if (stats != null) {
      _showActionInboxSheet(stats);
      return;
    }

    ref.read(dashboardProvider.notifier).load().then((_) {
      if (!mounted) {
        return;
      }

      final fresh = ref.read(dashboardProvider).valueOrNull;
      if (fresh != null) {
        _showActionInboxSheet(fresh);
        return;
      }

      final error = ref.read(dashboardProvider).error;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            error != null
                ? 'Could not load inbox: $error'
                : 'Could not load inbox. Pull to refresh and try again.',
          ),
        ),
      );
    });
  }

  void _showActionInboxSheet(DashboardStats stats) {
    final theme = Theme.of(context);

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: theme.colorScheme.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (sheetContext) {
        final maxHeight = MediaQuery.of(sheetContext).size.height * 0.72;

        return SafeArea(
          child: ConstrainedBox(
            constraints: BoxConstraints(maxHeight: maxHeight),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: theme.dividerColor,
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Action inbox',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    stats.actionableCount > 0
                        ? '${stats.actionableCount} items need your attention'
                        : 'No urgent actions right now',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 13,
                      color: theme.textTheme.bodyMedium?.color,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Grouped summaries — tap a row to open the matching customer list.',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 11,
                      color: theme.textTheme.bodySmall?.color,
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (stats.actions.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 24),
                      child: Center(
                        child: Column(
                          children: [
                            Icon(
                              Icons.notifications_none_rounded,
                              size: 40,
                              color: theme.dividerColor,
                            ),
                            const SizedBox(height: 12),
                            Text(
                              'You are all caught up.',
                              style: GoogleFonts.plusJakartaSans(
                                fontWeight: FontWeight.w600,
                                color: theme.textTheme.bodyMedium?.color,
                              ),
                            ),
                          ],
                        ),
                      ),
                    )
                  else
                    Flexible(
                      child: ListView.separated(
                        shrinkWrap: true,
                        itemCount: stats.actions.length,
                        separatorBuilder: (_, __) => const Divider(height: 1),
                        itemBuilder: (_, index) {
                          final action = stats.actions[index];
                          final color = _actionSeverityColor(action.severity);

                          return ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: Container(
                              width: 42,
                              height: 42,
                              decoration: BoxDecoration(
                                color: color.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Center(
                                child: Text(
                                  '${action.count}',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontWeight: FontWeight.w800,
                                    color: color,
                                  ),
                                ),
                              ),
                            ),
                            title: Text(
                              action.title,
                              style: GoogleFonts.plusJakartaSans(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            subtitle: Text(action.subtitle),
                            trailing: const Icon(Icons.chevron_right_rounded),
                            onTap: () {
                              Navigator.of(sheetContext).pop();
                              context.go('/customers?tab=${action.tab}');
                            },
                          );
                        },
                      ),
                    ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Color _actionSeverityColor(String severity) {
    return switch (severity) {
      'warning' => DesignTokens.statAmber,
      'success' => DesignTokens.statGreen,
      'danger' => AppConstants.error,
      _ => DesignTokens.statBlue,
    };
  }

  Widget _buildActionInbox(DashboardStats stats) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final preview = stats.actions.take(3).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _DashboardSectionHeader(
          title: 'Action inbox',
          subtitle: '${stats.actionableCount} items need attention',
          trailing: stats.actions.length > 3
              ? TextButton(
                  onPressed: _openActionInbox,
                  child: const Text('See all'),
                )
              : null,
        ),
        const SizedBox(height: 16),
        ...preview.map((action) {
          final color = _actionSeverityColor(action.severity);
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: GestureDetector(
              onTap: () => context.go('/customers?tab=${action.tab}'),
              child: GlassCard.surface(
                context,
                borderRadius: BorderRadius.circular(18),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: isDark ? 0.2 : 0.12),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Center(
                        child: Text(
                          '${action.count}',
                          style: GoogleFonts.plusJakartaSans(
                            fontWeight: FontWeight.w800,
                            color: color,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            action.title,
                            style: GoogleFonts.plusJakartaSans(
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                              height: 1.25,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            action.subtitle,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 12,
                              height: 1.35,
                              color: theme.textTheme.bodyMedium?.color,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Icon(Icons.chevron_right_rounded, color: color),
                    ),
                  ],
                ),
              ),
            ),
          );
        }),
      ],
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

    return LayoutBuilder(
      builder: (context, constraints) {
        final cellWidth = (constraints.maxWidth - 6) / 2;
        const cellHeight = 58.0;

        return GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: 5,
          mainAxisSpacing: 5,
          childAspectRatio: cellWidth / cellHeight,
          children: actions.map((a) => _buildActionCard(a)).toList(),
        );
      },
    );
  }

  Widget _buildActionCard(_ActionData action) {
    final captionColor = Theme.of(context).textTheme.bodyMedium?.color;
    final isPrimary = action.color == AppConstants.primary;

    return GestureDetector(
      onTap: action.onTap,
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: action.color.withValues(alpha: 0.08),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
          gradient: isPrimary
              ? LinearGradient(
                  colors: [
                    action.color.withValues(alpha: 0.4),
                    DesignTokens.loginSkyGlow.withValues(alpha: 0.2),
                  ],
                )
              : null,
          color: isPrimary ? null : action.surface.withValues(alpha: 0.5),
        ),
        child: Padding(
          padding: EdgeInsets.all(isPrimary ? 1 : 0),
          child: GlassCard(
            tint: action.surface,
            borderRadius: BorderRadius.circular(isPrimary ? 15 : 16),
            borderColor: action.color.withValues(alpha: 0.28),
            blurSigma: 14,
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
            boxShadow: const [],
            child: Row(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    gradient: DesignTokens.statCardSheen(action.color),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: action.color.withValues(alpha: 0.2),
                    ),
                  ),
                  child: Center(
                    child: AppColorIcon(
                      assetName: action.iconAsset,
                      size: 18,
                      semanticsLabel: action.label,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        action.label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                          color: action.color,
                          height: 1.1,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        action.caption,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 9,
                          fontWeight: FontWeight.w600,
                          color: captionColor,
                          height: 1.15,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(
                  Icons.arrow_outward_rounded,
                  size: 13,
                  color: action.color.withValues(alpha: 0.8),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildRecentCustomers() {
    final recentAsync = ref.watch(recentCustomersProvider);

    return recentAsync.when(
      loading: () => _buildCustomerShimmer(),
      error: (_, __) => _buildEmptyState(),
      data: (recent) {
        if (recent.isEmpty) {
          return _buildEmptyState();
        }

        return Column(
          children: recent
              .map((c) => _CustomerTile(
                    name: c.fullName,
                    phone: c.phone,
                    status: c.kycStatus,
                    headshotUrl: c.headshotUrl,
                    dealer: c.dealer,
                    autoCheck: c.autoCheck,
                    faceMatchNeedsReview: c.faceMatchNeedsReview,
                    readyForRelease: c.readyForRelease,
                    resumeStep: c.resumeStep,
                    isStaleDraft: c.isStaleDraft,
                    onTap: () => context.go('/customers/${c.id}'),
                  ))
              .toList(),
        );
      },
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
                margin: const EdgeInsets.only(bottom: 6),
                height: 58,
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

Widget _miniBadge(String label, Color color, bool isDark) {
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: isDark ? color.withValues(alpha: 0.18) : color.withValues(alpha: 0.1),
      borderRadius: BorderRadius.circular(999),
      border: Border.all(color: color.withValues(alpha: 0.25)),
    ),
    child: Text(
      label,
      style: GoogleFonts.plusJakartaSans(
        fontSize: 10,
        fontWeight: FontWeight.w700,
        color: color,
      ),
    ),
  );
}

class _CustomerTile extends StatelessWidget {
  final String name;
  final String phone;
  final String status;
  final String? headshotUrl;
  final String? dealer;
  final String? autoCheck;
  final bool faceMatchNeedsReview;
  final bool readyForRelease;
  final int? resumeStep;
  final bool isStaleDraft;
  final VoidCallback onTap;

  const _CustomerTile({
    required this.name,
    required this.phone,
    required this.status,
    this.headshotUrl,
    this.dealer,
    this.autoCheck,
    this.faceMatchNeedsReview = false,
    this.readyForRelease = false,
    this.resumeStep,
    this.isStaleDraft = false,
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
      padding: const EdgeInsets.only(bottom: 6),
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
                  padding: const EdgeInsets.fromLTRB(14, 11, 12, 11),
                  child: Row(
                    children: [
                      _CustomerAvatar(
                        name: name,
                        imageUrl: headshotUrl,
                        isDark: isDark,
                        ringColor: statusColor,
                      ),
                      const SizedBox(width: 10),
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
                            if (dealer != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                dealer!,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 11,
                                  color: theme.textTheme.bodySmall?.color,
                                ),
                              ),
                            ],
                            const SizedBox(height: 6),
                            Wrap(
                              spacing: 5,
                              runSpacing: 5,
                              children: [
                                if (status == 'draft' && resumeStep != null)
                                  _miniBadge(
                                    'Step $resumeStep',
                                    DesignTokens.statBlue,
                                    isDark,
                                  ),
                                if (isStaleDraft)
                                  _miniBadge(
                                    'Stale',
                                    DesignTokens.statAmber,
                                    isDark,
                                  ),
                                if (faceMatchNeedsReview)
                                  _miniBadge(
                                    'Face review',
                                    DesignTokens.statViolet,
                                    isDark,
                                  ),
                                if (autoCheck != null)
                                  _miniBadge(
                                    autoCheck!,
                                    autoCheck == 'passed'
                                        ? DesignTokens.statGreen
                                        : DesignTokens.statAmber,
                                    isDark,
                                  ),
                                if (readyForRelease)
                                  _miniBadge(
                                    'Release ready',
                                    DesignTokens.statGreen,
                                    isDark,
                                  ),
                              ],
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
    this.compact = false,
  });

  final String title;
  final String subtitle;
  final Widget? trailing;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 4,
          height: compact ? 20 : 42,
          margin: EdgeInsets.only(top: compact ? 2 : 2, right: compact ? 10 : 12),
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
                  fontSize: compact ? 16 : 20,
                  fontWeight: FontWeight.w800,
                  color: theme.textTheme.bodyLarge?.color,
                  letterSpacing: -0.5,
                  height: compact ? 1.15 : null,
                ),
              ),
              if (!compact) ...[
                const SizedBox(height: 6),
                Text(
                  subtitle,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: theme.textTheme.bodyMedium?.color,
                  ),
                ),
              ],
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
      borderRadius: BorderRadius.circular(20),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(20),
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
              _divider(),
              _insightCell(
                Icons.cancel_outlined,
                'Declined',
                '${insight.declined}',
                const Color(0xFFFCA5A5),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _divider() => Container(
        width: 1,
        height: 36,
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
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: tone, size: 16),
          const SizedBox(height: 4),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              value,
              style: GoogleFonts.plusJakartaSans(
                color: Colors.white,
                fontSize: 17,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.5,
              ),
            ),
          ),
          const SizedBox(height: 2),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              label,
              maxLines: 1,
              style: GoogleFonts.plusJakartaSans(
                color: Colors.white.withValues(alpha: 0.72),
                fontSize: 9,
                fontWeight: FontWeight.w600,
              ),
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
