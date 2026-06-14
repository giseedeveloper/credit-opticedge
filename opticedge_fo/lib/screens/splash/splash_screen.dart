import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/auth_provider.dart';
import '../../widgets/common/app_logo.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen>
    with TickerProviderStateMixin {
  late AnimationController _logoController;
  late AnimationController _scanController;
  late AnimationController _textController;
  late AnimationController _particleController;

  late Animation<double> _logoScale;
  late Animation<double> _logoOpacity;
  late Animation<double> _scanPosition;
  late Animation<double> _textOpacity;
  late Animation<double> _textSlide;

  @override
  void initState() {
    super.initState();

    _logoController = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 900));
    _scanController = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 1800));
    _textController = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 700));
    _particleController =
        AnimationController(vsync: this, duration: const Duration(seconds: 3))
          ..repeat();

    _logoScale = Tween<double>(begin: 0.6, end: 1.0).animate(
        CurvedAnimation(parent: _logoController, curve: Curves.elasticOut));
    _logoOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
        CurvedAnimation(parent: _logoController, curve: Curves.easeOut));
    _scanPosition = Tween<double>(begin: 0.0, end: 1.0).animate(
        CurvedAnimation(parent: _scanController, curve: Curves.easeInOut));
    _textOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
        CurvedAnimation(parent: _textController, curve: Curves.easeOut));
    _textSlide = Tween<double>(begin: 20.0, end: 0.0).animate(
        CurvedAnimation(parent: _textController, curve: Curves.easeOut));

    _startAnimation();
  }

  void _startAnimation() async {
    await Future.delayed(const Duration(milliseconds: 200));
    if (!mounted) return;
    _logoController.forward();
    await Future.delayed(const Duration(milliseconds: 600));
    if (!mounted) return;
    _scanController.forward();
    await Future.delayed(const Duration(milliseconds: 400));
    if (!mounted) return;
    _textController.forward();
    await Future.delayed(const Duration(milliseconds: 1400));
    if (!mounted) return;
    _navigate();
  }

  void _navigate() {
    if (!mounted) return;
    final authState = ref.read(authProvider);
    if (authState.status == AuthStatus.authenticated) {
      context.go('/dashboard');
    } else if (authState.status == AuthStatus.unauthenticated) {
      context.go('/login');
    } else {
      Future.delayed(const Duration(milliseconds: 500), () {
        if (mounted) _navigate();
      });
    }
  }

  @override
  void dispose() {
    _logoController.dispose();
    _scanController.dispose();
    _textController.dispose();
    _particleController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    ref.listen<AuthState>(authProvider, (previous, next) {
      if (!mounted || next.status == AuthStatus.unknown) {
        return;
      }
      if (previous?.status == AuthStatus.unknown) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) {
            _navigate();
          }
        });
      }
    });

    final isDark = Theme.of(context).brightness == Brightness.dark;
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        body: Container(
          width: double.infinity,
          height: double.infinity,
          decoration: BoxDecoration(
            gradient: isDark
                ? DesignTokens.heroGradientWithPrimaryHintDark
                : DesignTokens.heroGradientWithPrimaryHint,
          ),
          child: Stack(
          children: [
            // Floating particles background
            AnimatedBuilder(
              animation: _particleController,
              builder: (_, __) => CustomPaint(
                painter: _ParticlePainter(_particleController.value),
                child: const SizedBox.expand(),
              ),
            ),

            // Scan line overlay
            AnimatedBuilder(
              animation: _scanPosition,
              builder: (_, __) {
                final size = MediaQuery.of(context).size;
                return Positioned(
                  top: _scanPosition.value * size.height,
                  left: 0,
                  right: 0,
                  child: Opacity(
                    opacity: (1 - (_scanPosition.value - 0.5).abs() * 2)
                        .clamp(0.0, 0.4),
                    child: Container(
                      height: 2,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            Colors.transparent,
                            AppConstants.primaryLight.withValues(alpha: 0.45),
                            Colors.white.withValues(alpha: 0.85),
                            Colors.transparent,
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),

            // Main content
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Logo
                  AnimatedBuilder(
                    animation: _logoController,
                    builder: (_, __) => Opacity(
                      opacity: _logoOpacity.value,
                      child: Transform.scale(
                        scale: _logoScale.value,
                        child: _buildLogo(),
                      ),
                    ),
                  ),

                  const SizedBox(height: 32),

                  // App name + tagline
                  AnimatedBuilder(
                    animation: _textController,
                    builder: (_, __) => Opacity(
                      opacity: _textOpacity.value,
                      child: Transform.translate(
                        offset: Offset(0, _textSlide.value),
                        child: Column(
                          children: [
                            const Text(
                              AppConstants.appName,
                              style: TextStyle(
                                fontSize: 30,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                                letterSpacing: -0.5,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 16, vertical: 6),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(
                                  color: Colors.white.withValues(alpha: 0.3),
                                ),
                              ),
                              child: const Text(
                                AppConstants.tagline,
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                  color: Colors.white,
                                  letterSpacing: 0.8,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 80),

                  // Loading dots
                  AnimatedBuilder(
                    animation: _particleController,
                    builder: (_, __) => Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(
                        3,
                        (i) => Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 3),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 300),
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(
                                alpha: (math.sin(
                                          (_particleController.value *
                                                  2 *
                                                  math.pi) +
                                              (i * math.pi / 1.5),
                                        ) *
                                        0.5 +
                                    0.5)
                                    .clamp(0.2, 1.0),
                              ),
                              shape: BoxShape.circle,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Bottom branding
            Positioned(
              bottom: 40,
              left: 0,
              right: 0,
              child: Text(
                'Opticedge Africa © 2026',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.white.withValues(alpha: 0.52),
                  fontWeight: FontWeight.w400,
                ),
              ),
            ),
          ],
        ),
        ),
      ),
    );
  }

  Widget _buildLogo() {
    return const AppLogo(
      size: 112,
      borderRadius: 26,
      elevation: 12,
      showShadow: true,
    );
  }
}

class _ParticlePainter extends CustomPainter {
  final double progress;
  _ParticlePainter(this.progress);

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..style = PaintingStyle.fill;
    final rng = math.Random(42);

    for (int i = 0; i < 18; i++) {
      final x = rng.nextDouble() * size.width;
      final baseY = rng.nextDouble() * size.height;
      final speed = 0.3 + rng.nextDouble() * 0.7;
      final y = (baseY - progress * size.height * speed) % size.height;
      final radius = 1.5 + rng.nextDouble() * 3;
      final opacity = 0.05 + rng.nextDouble() * 0.12;

      paint.color = Colors.white.withValues(alpha: opacity);
      canvas.drawCircle(Offset(x, y), radius, paint);
    }
  }

  @override
  bool shouldRepaint(_ParticlePainter old) => old.progress != progress;
}
