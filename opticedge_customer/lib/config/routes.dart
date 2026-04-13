import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'constants.dart';
import '../core/providers/auth_provider.dart';
import '../screens/auth/login_screen.dart';
import '../screens/device/device_screen.dart';
import '../screens/home/home_screen.dart';
import '../screens/payment/pay_screen.dart';
import '../screens/profile/profile_screen.dart';
import '../screens/schedule/schedule_screen.dart';
import '../screens/splash/splash_screen.dart';

class RouterNotifier extends ChangeNotifier {
  final Ref _ref;
  RouterNotifier(this._ref) {
    _ref.listen<AuthState>(authProvider, (_, _) => notifyListeners());
  }

  String? redirect(BuildContext context, GoRouterState state) {
    final auth = _ref.read(authProvider);
    final isLogin = state.matchedLocation == '/login';
    final isSplash = state.matchedLocation == '/';

    if (auth.status == AuthStatus.unknown) {
      return isSplash ? null : '/';
    }
    if (auth.status == AuthStatus.unauthenticated) {
      return isLogin ? null : '/login';
    }
    if (auth.status == AuthStatus.authenticated) {
      if (isLogin || isSplash) return '/home';
    }
    return null;
  }
}

final routerNotifierProvider = Provider<RouterNotifier>((ref) {
  return RouterNotifier(ref);
});

final appRouterProvider = Provider<GoRouter>((ref) {
  final notifier = ref.read(routerNotifierProvider);

  return GoRouter(
    refreshListenable: notifier,
    redirect: notifier.redirect,
    initialLocation: '/',
    routes: [
      GoRoute(path: '/', builder: (_, _) => const SplashScreen()),
      GoRoute(path: '/login', builder: (_, _) => const LoginScreen()),
      ShellRoute(
        builder: (context, state, child) => MainShell(child: child),
        routes: [
          GoRoute(path: '/home', builder: (_, _) => const HomeScreen()),
          GoRoute(path: '/schedule', builder: (_, _) => const ScheduleScreen()),
          GoRoute(path: '/pay', builder: (_, _) => const PayScreen()),
          GoRoute(path: '/device', builder: (_, _) => const DeviceScreen()),
          GoRoute(path: '/profile', builder: (_, _) => const ProfileScreen()),
        ],
      ),
    ],
  );
});

class MainShell extends StatelessWidget {
  final Widget child;
  const MainShell({super.key, required this.child});

  static const _tabs = ['/home', '/schedule', '/pay', '/device', '/profile'];

  int _indexOf(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final idx = _tabs.indexOf(location);
    return idx >= 0 ? idx : 0;
  }

  @override
  Widget build(BuildContext context) {
    final currentIndex = _indexOf(context);
    return Scaffold(
      body: child,
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: AppConstants.surface,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.06),
              blurRadius: 12,
              offset: const Offset(0, -4),
            ),
          ],
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: List.generate(5, (i) {
                final isSelected = i == currentIndex;
                final icons = [
                  Icons.home_rounded,
                  Icons.calendar_month_rounded,
                  Icons.payments_rounded,
                  Icons.phone_android_rounded,
                  Icons.person_rounded,
                ];
                final labels = ['Home', 'Ratiba', 'Lipa', 'Kifaa', 'Profaili'];
                return GestureDetector(
                  onTap: () => context.go(_tabs[i]),
                  behavior: HitTestBehavior.opaque,
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 250),
                    curve: Curves.easeInOut,
                    padding: EdgeInsets.symmetric(
                      horizontal: isSelected ? 16 : 12,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: isSelected
                          ? AppConstants.primarySurface
                          : Colors.transparent,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          icons[i],
                          size: 22,
                          color: isSelected
                              ? AppConstants.primary
                              : AppConstants.textHint,
                        ),
                        if (isSelected) ...[
                          const SizedBox(width: 8),
                          Text(
                            labels[i],
                            style: const TextStyle(
                              color: AppConstants.primary,
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              }),
            ),
          ),
        ),
      ),
    );
  }
}
