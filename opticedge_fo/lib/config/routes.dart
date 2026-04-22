import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'app_icon_assets.dart';
import 'design_tokens.dart';
import '../core/providers/auth_provider.dart';
import '../screens/splash/splash_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/dashboard/dashboard_screen.dart';
import '../screens/customers/customer_list_screen.dart';
import '../screens/customers/customer_detail_screen.dart';
import '../screens/kyc/kyc_wizard_screen.dart';
import '../screens/profile/profile_screen.dart';
import '../screens/settings/settings_screen.dart';
import '../core/l10n/app_strings.dart';
import '../widgets/common/app_color_icon.dart';

class RouterNotifier extends ChangeNotifier {
  RouterNotifier(this._ref) {
    _ref.listen<AuthState>(authProvider, (_, __) => notifyListeners());
  }
  final Ref _ref;

  String? redirect(BuildContext context, GoRouterState state) {
    final authState = _ref.read(authProvider);
    final isAuth = authState.status == AuthStatus.authenticated;
    final isUnknown = authState.status == AuthStatus.unknown;
    final path = state.matchedLocation;

    if (isUnknown) return '/';
    if (isAuth && (path == '/' || path == '/login')) return '/dashboard';
    if (!isAuth && path != '/login' && path != '/') return '/login';
    return null;
  }
}

final appRouterProvider = Provider<GoRouter>((ref) {
  final notifier = RouterNotifier(ref);

  return GoRouter(
    initialLocation: '/',
    debugLogDiagnostics: false,
    refreshListenable: notifier,
    redirect: notifier.redirect,
    routes: [
      GoRoute(
        path: '/',
        builder: (_, __) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (_, __) => const LoginScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) =>
            _MainShell(location: state.matchedLocation, child: child),
        routes: [
          GoRoute(
            path: '/dashboard',
            builder: (_, __) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/customers',
            builder: (context, state) {
              final tab = state.uri.queryParameters['tab'];
              return CustomerListScreen(initialTab: tab);
            },
          ),
          GoRoute(
            path: '/customers/:id',
            builder: (_, state) =>
                CustomerDetailScreen(customerId: state.pathParameters['id']!),
          ),
          GoRoute(
            path: '/kyc/new',
            redirect: (_, state) {
              final draft = state.uri.queryParameters['draft'];
              if (draft != null && draft.isNotEmpty) {
                return '/kyc/new/step/1?draft=${Uri.encodeComponent(draft)}';
              }
              return '/kyc/new/step/1';
            },
          ),
          GoRoute(
            path: '/kyc/new/step/:step',
            pageBuilder: (context, state) {
              final raw = int.tryParse(state.pathParameters['step'] ?? '1') ?? 1;
              final step = raw.clamp(1, 7);
              return CustomTransitionPage<void>(
                key: state.pageKey,
                child: KycWizardScreen(
                  routeStep: step,
                  draftCustomerId: state.uri.queryParameters['draft'],
                ),
                transitionsBuilder:
                    (context, animation, secondaryAnimation, child) {
                  return SlideTransition(
                    position: Tween<Offset>(
                      begin: const Offset(0.08, 0),
                      end: Offset.zero,
                    ).animate(CurvedAnimation(
                      parent: animation,
                      curve: Curves.easeOutCubic,
                    )),
                    child: FadeTransition(
                      opacity: animation,
                      child: child,
                    ),
                  );
                },
              );
            },
          ),
          GoRoute(
            path: '/profile',
            builder: (_, __) => const ProfileScreen(),
          ),
          GoRoute(
            path: '/settings',
            builder: (_, __) => const SettingsScreen(),
          ),
        ],
      ),
    ],
  );
});

class _MainShell extends ConsumerWidget {
  final Widget child;
  final String location;

  const _MainShell({required this.child, required this.location});

  int get _selectedIndex {
    if (location.startsWith('/customers')) return 1;
    if (location.startsWith('/kyc')) return 2;
    if (location.startsWith('/profile')) return 3;
    if (location.startsWith('/settings')) return 4;
    return 0;
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final navBg = isDark ? DesignTokens.darkNavBarBg : Colors.white;
    final navBorder =
        isDark ? DesignTokens.darkNavBarBorder : const Color(0xFFE2E8F0);
    final s = S.of(ref);

    return Scaffold(
      body: child,
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: navBg,
          border: Border(top: BorderSide(color: navBorder, width: 1)),
          boxShadow: [
            BoxShadow(
              color: isDark
                  ? Colors.black.withValues(alpha: 0.45)
                  : DesignTokens.primary.withValues(alpha: 0.06),
              blurRadius: isDark ? 20 : 16,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: SafeArea(
          child: SizedBox(
            height: 68,
            child: Row(
              children: [
                _NavItem(
                  iconAsset: AppIconAssets.dashboard,
                  activeIconAsset: AppIconAssets.dashboard,
                  label: s.dashboard,
                  selected: _selectedIndex == 0,
                  onTap: () => context.go('/dashboard'),
                ),
                _NavItem(
                  iconAsset: AppIconAssets.customers,
                  activeIconAsset: AppIconAssets.customers,
                  label: s.customers,
                  selected: _selectedIndex == 1,
                  onTap: () => context.go('/customers'),
                ),
                // Center FAB-like button
                Expanded(
                  child: GestureDetector(
                    onTap: () => context.go('/kyc/new'),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: isDark
                                ? DesignTokens.darkSurface
                                : Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isDark
                                  ? DesignTokens.darkBorder
                                  : DesignTokens.primaryLight
                                      .withValues(alpha: 0.45),
                              width: 1.2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: DesignTokens.primary
                                    .withValues(
                                        alpha: isDark ? 0.22 : 0.35),
                                blurRadius: 14,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: const Center(
                            child: AppColorIcon(
                              assetName: AppIconAssets.register,
                              size: 24,
                              semanticsLabel: 'Register',
                            ),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 4),
                          child: SizedBox(
                            width: double.infinity,
                            child: FittedBox(
                              fit: BoxFit.scaleDown,
                              child: Text(
                                s.register,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 9,
                                  fontWeight: FontWeight.w700,
                                  color: DesignTokens.primary,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                _NavItem(
                  iconAsset: AppIconAssets.profile,
                  activeIconAsset: AppIconAssets.profile,
                  label: s.profile,
                  selected: _selectedIndex == 3,
                  onTap: () => context.go('/profile'),
                ),
                _NavItem(
                  iconAsset: AppIconAssets.settings,
                  activeIconAsset: AppIconAssets.settings,
                  label: s.settings,
                  selected: _selectedIndex == 4,
                  onTap: () => context.go('/settings'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  final String iconAsset;
  final String activeIconAsset;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  const _NavItem({
    required this.iconAsset,
    required this.activeIconAsset,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        behavior: HitTestBehavior.opaque,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 220),
              curve: Curves.easeOutCubic,
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: selected
                    ? DesignTokens.navSelectedBg
                    : Colors.transparent,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Center(
                child: AppColorIcon(
                  assetName: selected ? activeIconAsset : iconAsset,
                  size: 22,
                  opacity: selected ? 1 : 0.58,
                  semanticsLabel: label,
                ),
              ),
            ),
            const SizedBox(height: 4),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              child: SizedBox(
                width: double.infinity,
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 9,
                      fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                      color: selected
                          ? DesignTokens.navSelectedFg
                          : DesignTokens.navUnselectedFg,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
