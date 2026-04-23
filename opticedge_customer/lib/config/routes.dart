import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'customer_colors.dart';
import '../core/providers/auth_provider.dart';
import '../widgets/common/floating_glass_nav.dart';
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
    final bottomInset = MediaQuery.paddingOf(context).bottom;
    const navReserve = 76.0;

    const icons = [
      Icons.home_rounded,
      Icons.calendar_month_rounded,
      Icons.payments_rounded,
      Icons.phone_android_rounded,
      Icons.person_rounded,
    ];
    const labels = ['Home', 'Ratiba', 'Lipa', 'Kifaa', 'Profaili'];

    final cc = CustomerColors.of(context);

    return Scaffold(
      backgroundColor: Colors.transparent,
      extendBody: true,
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Fills safe areas + gap above floating nav (transparent scaffold would show OS black).
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: cc.premiumGradientColors,
                  stops: cc.premiumGradientStops,
                ),
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.only(bottom: navReserve + bottomInset),
            child: child,
          ),
          Positioned(
            left: 18,
            right: 18,
            bottom: 10 + bottomInset,
            child: FloatingGlassNavBar(
              currentIndex: currentIndex,
              onTap: (i) => context.go(_tabs[i]),
              icons: icons,
              labels: labels,
            ),
          ),
        ],
      ),
    );
  }
}
