import 'dart:math' as math;
import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/l10n/app_strings.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/app_logo.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen>
    with TickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _identifierCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _identifierFocus = FocusNode();
  final _passwordFocus = FocusNode();

  bool _obscurePassword = true;
  bool _rememberMe = false;

  late AnimationController _entranceController;
  late AnimationController _ambientController;
  late Animation<double> _heroFade;
  late Animation<double> _cardSlide;
  late Animation<double> _cardFade;
  late List<Animation<double>> _fieldStagger;

  @override
  void initState() {
    super.initState();
    _entranceController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1100),
    );
    _ambientController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 5),
    )..repeat(reverse: true);

    _heroFade = CurvedAnimation(
      parent: _entranceController,
      curve: const Interval(0.0, 0.45, curve: Curves.easeOut),
    );
    _cardSlide = Tween<double>(begin: 48, end: 0).animate(
      CurvedAnimation(
        parent: _entranceController,
        curve: const Interval(0.2, 0.85, curve: Curves.easeOutCubic),
      ),
    );
    _cardFade = CurvedAnimation(
      parent: _entranceController,
      curve: const Interval(0.2, 0.75, curve: Curves.easeOut),
    );
    _fieldStagger = List.generate(
      6,
      (i) => CurvedAnimation(
        parent: _entranceController,
        curve: Interval(
          0.35 + (i * 0.07),
          0.88,
          curve: Curves.easeOutCubic,
        ),
      ),
    );

    Future.delayed(const Duration(milliseconds: 80), () {
      if (mounted) _entranceController.forward();
    });
  }

  @override
  void dispose() {
    _entranceController.dispose();
    _ambientController.dispose();
    _identifierCtrl.dispose();
    _passwordCtrl.dispose();
    _identifierFocus.dispose();
    _passwordFocus.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    await ref.read(authProvider.notifier).login(
          _identifierCtrl.text.trim(),
          _passwordCtrl.text,
        );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final size = MediaQuery.of(context).size;
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final reduceMotion = MediaQuery.of(context).disableAnimations;

    ref.listen(authProvider, (_, next) {
      if (next.error != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next.error!),
            backgroundColor: AppConstants.error,
            behavior: SnackBarBehavior.floating,
            margin: const EdgeInsets.all(16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        );
      }
    });

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: isDark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.light,
      child: Scaffold(
        body: Stack(
          fit: StackFit.expand,
          children: [
            _LoginBackdrop(isDark: isDark, size: size),
            if (!reduceMotion)
              AnimatedBuilder(
                animation: _ambientController,
                builder: (_, __) => _LoginOrbLayer(
                  t: _ambientController.value,
                  size: size,
                ),
              ),
            SafeArea(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(22, 12, 22, 28),
                child: Column(
                  children: [
                    FadeTransition(
                      opacity: _heroFade,
                      child: _LoginHeader(isDark: isDark, strings: S.of(ref)),
                    ),
                    const SizedBox(height: 28),
                    AnimatedBuilder(
                      animation: _entranceController,
                      builder: (_, __) => Opacity(
                        opacity: _cardFade.value,
                        child: Transform.translate(
                          offset: Offset(0, _cardSlide.value),
                          child: _buildLoginCard(
                            authState: authState,
                            theme: theme,
                            isDark: isDark,
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

  Widget _buildLoginCard({
    required AuthState authState,
    required ThemeData theme,
    required bool isDark,
  }) {
    final s = S.of(ref);
    final cardSurface = isDark
        ? DesignTokens.darkSurfaceElevated.withValues(alpha: 0.92)
        : Colors.white.withValues(alpha: 0.94);
    final fieldFill = isDark
        ? DesignTokens.darkSurface.withValues(alpha: 0.85)
        : const Color(0xFFF4F7FB);
    final borderColor =
        isDark ? DesignTokens.darkBorder : AppConstants.border;

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: AppConstants.primary.withValues(alpha: isDark ? 0.12 : 0.08),
            blurRadius: 40,
            offset: const Offset(0, 20),
          ),
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.35 : 0.06),
            blurRadius: 32,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(28),
        child: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: DesignTokens.loginCardAccentBorder,
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(1.2),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(26.8),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 24, sigmaY: 24),
                  child: Container(
                    padding: const EdgeInsets.fromLTRB(22, 26, 22, 22),
                    decoration: BoxDecoration(
                      color: cardSurface,
                      borderRadius: BorderRadius.circular(26.8),
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _staggered(
                            0,
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  s.welcomeBack,
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 24,
                                    fontWeight: FontWeight.w800,
                                    color: theme.colorScheme.onSurface,
                                    letterSpacing: -0.5,
                                    height: 1.15,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  s.signInSubtitle,
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w500,
                                    color: theme.textTheme.bodyMedium?.color,
                                    height: 1.45,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 22),
                          _staggered(
                            1,
                            _LoginField(
                              controller: _identifierCtrl,
                              focusNode: _identifierFocus,
                              label: s.emailOrPhone,
                              hint: 'you@example.com or +255...',
                              icon: Icons.alternate_email_rounded,
                              keyboardType: TextInputType.emailAddress,
                              textInputAction: TextInputAction.next,
                              fillColor: fieldFill,
                              borderColor: borderColor,
                              onSubmitted: (_) => _passwordFocus.requestFocus(),
                              validator: (v) => (v == null || v.trim().isEmpty)
                                  ? s.required
                                  : null,
                            ),
                          ),
                          const SizedBox(height: 14),
                          _staggered(
                            2,
                            _LoginField(
                              controller: _passwordCtrl,
                              focusNode: _passwordFocus,
                              label: s.password,
                              hint: '••••••••',
                              icon: Icons.lock_rounded,
                              obscureText: _obscurePassword,
                              textInputAction: TextInputAction.done,
                              fillColor: fieldFill,
                              borderColor: borderColor,
                              onSubmitted: (_) => _login(),
                              suffix: IconButton(
                                onPressed: () => setState(
                                  () => _obscurePassword = !_obscurePassword,
                                ),
                                icon: Icon(
                                  _obscurePassword
                                      ? Icons.visibility_off_outlined
                                      : Icons.visibility_outlined,
                                  size: 20,
                                  color: theme.textTheme.bodySmall?.color,
                                ),
                              ),
                              validator: (v) =>
                                  (v == null || v.isEmpty) ? s.required : null,
                            ),
                          ),
                          const SizedBox(height: 12),
                          _staggered(
                            3,
                            Material(
                              color: Colors.transparent,
                              child: InkWell(
                                onTap: () =>
                                    setState(() => _rememberMe = !_rememberMe),
                                borderRadius: BorderRadius.circular(12),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 6,
                                    horizontal: 2,
                                  ),
                                  child: Row(
                                    children: [
                                      AnimatedContainer(
                                        duration:
                                            const Duration(milliseconds: 200),
                                        width: 22,
                                        height: 22,
                                        decoration: BoxDecoration(
                                          borderRadius:
                                              BorderRadius.circular(7),
                                          gradient: _rememberMe
                                              ? const LinearGradient(
                                                  colors: [
                                                    AppConstants.primary,
                                                    AppConstants.primaryLight,
                                                  ],
                                                )
                                              : null,
                                          color: _rememberMe
                                              ? null
                                              : fieldFill,
                                          border: Border.all(
                                            color: _rememberMe
                                                ? Colors.transparent
                                                : borderColor,
                                            width: 1.5,
                                          ),
                                        ),
                                        child: _rememberMe
                                            ? const Icon(
                                                Icons.check_rounded,
                                                size: 14,
                                                color: Colors.white,
                                              )
                                            : null,
                                      ),
                                      const SizedBox(width: 10),
                                      Text(
                                        s.keepMeSignedIn,
                                        style: GoogleFonts.plusJakartaSans(
                                          fontSize: 13,
                                          fontWeight: FontWeight.w600,
                                          color:
                                              theme.textTheme.bodyMedium?.color,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 22),
                          _staggered(
                            4,
                            AppButton(
                              label: s.signIn,
                              width: double.infinity,
                              isLoading: authState.isLoading,
                              icon: Icons.arrow_forward_rounded,
                              onPressed: _login,
                            ),
                          ),
                          if (authState.canUseBiometricUnlock) ...[
                            const SizedBox(height: 14),
                            _staggered(
                              5,
                              Column(
                                children: [
                                  SizedBox(
                                    width: double.infinity,
                                    child: OutlinedButton.icon(
                                      onPressed: authState.isLoading
                                          ? null
                                          : () => ref
                                              .read(authProvider.notifier)
                                              .unlockWithBiometrics(),
                                      icon: Icon(
                                        Icons.fingerprint_rounded,
                                        size: 22,
                                        color: theme.colorScheme.primary,
                                      ),
                                      label: Text(
                                        'Unlock with biometrics',
                                        style: GoogleFonts.plusJakartaSans(
                                          fontWeight: FontWeight.w700,
                                          fontSize: 14,
                                        ),
                                      ),
                                      style: OutlinedButton.styleFrom(
                                        foregroundColor:
                                            theme.colorScheme.primary,
                                        backgroundColor: fieldFill,
                                        side: BorderSide(
                                          color: theme.colorScheme.primary
                                              .withValues(alpha: 0.28),
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                          vertical: 15,
                                        ),
                                        shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(16),
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 10),
                                  Text(
                                    'Use fingerprint or Face ID for your last session.',
                                    textAlign: TextAlign.center,
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 12,
                                      color: theme.textTheme.bodySmall?.color,
                                      height: 1.4,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                          const SizedBox(height: 20),
                          _LoginSecurityStrip(isDark: isDark, label: s.securityNote),
                        ],
                      ),
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

  Widget _staggered(int index, Widget child) {
    return FadeTransition(
      opacity: _fieldStagger[index.clamp(0, _fieldStagger.length - 1)],
      child: SlideTransition(
        position: Tween<Offset>(
          begin: const Offset(0, 0.08),
          end: Offset.zero,
        ).animate(_fieldStagger[index.clamp(0, _fieldStagger.length - 1)]),
        child: child,
      ),
    );
  }
}

class _LoginHeader extends StatelessWidget {
  const _LoginHeader({required this.isDark, required this.strings});

  final bool isDark;
  final S strings;

  @override
  Widget build(BuildContext context) {
    final s = strings;

    return Column(
      children: [
        const AppLogo(
          size: 88,
          borderRadius: 22,
          elevation: 10,
          showShadow: true,
        ),
        const SizedBox(height: 18),
        Text(
          AppConstants.appName,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 28,
            fontWeight: FontWeight.w800,
            color: Colors.white,
            letterSpacing: -0.6,
            height: 1.1,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          s.fieldOfficerPortal,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Colors.white.withValues(alpha: 0.82),
          ),
        ),
        const SizedBox(height: 16),
        const _LoginTrustPills(),
      ],
    );
  }
}

class _LoginTrustPills extends StatelessWidget {
  const _LoginTrustPills();

  @override
  Widget build(BuildContext context) {
    const pills = [
      (Icons.bolt_rounded, 'Fast'),
      (Icons.shield_rounded, 'Secure'),
      (Icons.verified_rounded, 'Verified'),
    ];

    return Wrap(
      alignment: WrapAlignment.center,
      spacing: 8,
      runSpacing: 8,
      children: pills
          .map(
            (p) => Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(999),
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.22),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(p.$1, size: 14, color: DesignTokens.loginCoralGlow),
                  const SizedBox(width: 6),
                  Text(
                    p.$2,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: Colors.white.withValues(alpha: 0.95),
                      letterSpacing: 0.2,
                    ),
                  ),
                ],
              ),
            ),
          )
          .toList(),
    );
  }
}

class _LoginField extends StatelessWidget {
  const _LoginField({
    required this.controller,
    required this.focusNode,
    required this.label,
    required this.hint,
    required this.icon,
    required this.fillColor,
    required this.borderColor,
    this.keyboardType,
    this.textInputAction,
    this.obscureText = false,
    this.suffix,
    this.onSubmitted,
    this.validator,
  });

  final TextEditingController controller;
  final FocusNode focusNode;
  final String label;
  final String hint;
  final IconData icon;
  final Color fillColor;
  final Color borderColor;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final bool obscureText;
  final Widget? suffix;
  final void Function(String)? onSubmitted;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: theme.textTheme.bodySmall?.color,
            letterSpacing: 0.3,
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          focusNode: focusNode,
          obscureText: obscureText,
          keyboardType: keyboardType,
          textInputAction: textInputAction,
          onFieldSubmitted: onSubmitted,
          validator: validator,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w600,
            color: theme.colorScheme.onSurface,
          ),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.plusJakartaSans(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: theme.textTheme.bodySmall?.color,
            ),
            filled: true,
            fillColor: fillColor,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 16,
            ),
            prefixIcon: Container(
              width: 48,
              alignment: Alignment.center,
              child: Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(11),
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      AppConstants.primary.withValues(alpha: 0.18),
                      DesignTokens.accentSky.withValues(alpha: 0.12),
                    ],
                  ),
                ),
                child: Icon(
                  icon,
                  size: 18,
                  color: AppConstants.primary,
                ),
              ),
            ),
            suffixIcon: suffix,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide.none,
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide(color: borderColor, width: 1),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(
                color: AppConstants.primary,
                width: 1.6,
              ),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: AppConstants.error),
            ),
            focusedErrorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(
                color: AppConstants.error,
                width: 1.4,
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _LoginSecurityStrip extends StatelessWidget {
  const _LoginSecurityStrip({
    required this.isDark,
    required this.label,
  });

  final bool isDark;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        gradient: LinearGradient(
          colors: isDark
              ? [
                  DesignTokens.darkSurface.withValues(alpha: 0.9),
                  AppConstants.primary.withValues(alpha: 0.08),
                ]
              : [
                  AppConstants.primarySurface,
                  DesignTokens.statBlueBg,
                ],
        ),
        border: Border.all(
          color: AppConstants.primary.withValues(alpha: 0.15),
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppConstants.primary.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.lock_clock_rounded,
              size: 18,
              color: AppConstants.primary,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              label,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                height: 1.4,
                color: Theme.of(context).textTheme.bodyMedium?.color,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LoginBackdrop extends StatelessWidget {
  const _LoginBackdrop({required this.isDark, required this.size});

  final bool isDark;
  final Size size;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          flex: 52,
          child: Container(
            width: double.infinity,
            decoration: BoxDecoration(
              gradient: isDark
                  ? DesignTokens.loginHeroMeshDark
                  : DesignTokens.loginHeroMesh,
            ),
            child: CustomPaint(
              painter: _LoginGridPainter(
                color: Colors.white.withValues(alpha: 0.04),
              ),
              size: Size.infinite,
            ),
          ),
        ),
        Expanded(
          flex: 48,
          child: Container(
            width: double.infinity,
            decoration: BoxDecoration(
              gradient: isDark
                  ? DesignTokens.appCanvasGradientDark
                  : DesignTokens.loginCanvasLight,
            ),
          ),
        ),
      ],
    );
  }
}

class _LoginOrbLayer extends StatelessWidget {
  const _LoginOrbLayer({required this.t, required this.size});

  final double t;
  final Size size;

  @override
  Widget build(BuildContext context) {
    final drift = math.sin(t * math.pi * 2) * 12;

    return IgnorePointer(
      child: Stack(
        children: [
          Positioned(
            top: size.height * 0.06 + drift,
            right: -30,
            child: _glowOrb(
              160,
              AppConstants.primary.withValues(alpha: 0.22),
            ),
          ),
          Positioned(
            top: size.height * 0.22 - drift,
            left: -40,
            child: _glowOrb(
              120,
              DesignTokens.loginSkyGlow.withValues(alpha: 0.16),
            ),
          ),
          Positioned(
            top: size.height * 0.38 + drift * 0.5,
            right: size.width * 0.2,
            child: _glowOrb(
              80,
              DesignTokens.loginCoralGlow.withValues(alpha: 0.14),
            ),
          ),
        ],
      ),
    );
  }

  Widget _glowOrb(double diameter, Color color) {
    return Container(
      width: diameter,
      height: diameter,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [color, color.withValues(alpha: 0)],
        ),
      ),
    );
  }
}

class _LoginGridPainter extends CustomPainter {
  _LoginGridPainter({required this.color});

  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1;

    const step = 28.0;
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
