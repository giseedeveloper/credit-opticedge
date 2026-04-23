import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../config/customer_colors.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/auth_provider.dart';
import '../../widgets/common/app_brand_logo.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

/// Two-step login: (1) Enter phone → (2) Enter or Set PIN.
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen>
    with SingleTickerProviderStateMixin {
  final _phoneKey = GlobalKey<FormState>();
  final _pinKey = GlobalKey<FormState>();

  final _phoneCtrl = TextEditingController();
  final _pinCtrl = TextEditingController();
  final _pinConfirmCtrl = TextEditingController();

  bool _obscurePin = true;
  bool _obscureConfirm = true;

  // Step state
  bool _isPhoneStep = true;
  bool? _hasPin;
  String _customerName = '';

  late AnimationController _animController;
  late Animation<double> _cardSlide;
  late Animation<double> _cardOpacity;
  late Animation<double> _headerOpacity;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _cardSlide = Tween<double>(begin: 60, end: 0).animate(
      CurvedAnimation(parent: _animController, curve: Curves.easeOutCubic),
    );
    _cardOpacity = Tween<double>(
      begin: 0,
      end: 1,
    ).animate(CurvedAnimation(parent: _animController, curve: Curves.easeOut));
    _headerOpacity = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(
        parent: _animController,
        curve: const Interval(0.0, 0.6, curve: Curves.easeOut),
      ),
    );
    Future.delayed(const Duration(milliseconds: 100), () {
      if (mounted) _animController.forward();
    });
  }

  @override
  void dispose() {
    _animController.dispose();
    _phoneCtrl.dispose();
    _pinCtrl.dispose();
    _pinConfirmCtrl.dispose();
    super.dispose();
  }

  Future<void> _checkPhone() async {
    if (!_phoneKey.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    try {
      final result = await ref
          .read(authProvider.notifier)
          .checkPhone(_phoneCtrl.text.trim());
      setState(() {
        _isPhoneStep = false;
        _hasPin = result.hasPin;
        _customerName = result.customerName;
      });
      // Re-animate card
      _animController.reset();
      _animController.forward();
    } catch (_) {
      // Error is shown via auth state
    }
  }

  Future<void> _submitPin() async {
    if (!_pinKey.currentState!.validate()) return;
    FocusScope.of(context).unfocus();

    final phone = _phoneCtrl.text.trim();
    final pin = _pinCtrl.text.trim();

    if (_hasPin == true) {
      await ref.read(authProvider.notifier).login(phone, pin);
    } else {
      final confirm = _pinConfirmCtrl.text.trim();
      await ref.read(authProvider.notifier).setPin(phone, pin, confirm);
    }
  }

  void _goBackToPhone() {
    setState(() {
      _isPhoneStep = true;
      _pinCtrl.clear();
      _pinConfirmCtrl.clear();
    });
    _animController.reset();
    _animController.forward();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authProvider);
    final size = MediaQuery.of(context).size;
    final theme = Theme.of(context);
    final cc = CustomerColors.of(context);
    final heroGradient = theme.brightness == Brightness.dark
        ? LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: cc.homeHeroGradientColors,
            stops: const [0.0, 0.52, 1.0],
          )
        : DesignTokens.heroGradientWithPrimaryHint;
    final sheetTint = theme.brightness == Brightness.dark
        ? cc.glassCardTint.withValues(alpha: 0.72)
        : Colors.white.withValues(alpha: 0.45);

    ref.listen(authProvider, (_, next) {
      if (next.error != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next.error!),
            backgroundColor: AppConstants.error,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
          ),
        );
      }
    });

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: SizedBox(
          width: double.infinity,
          height: double.infinity,
          child: Stack(
            children: [
              const PremiumGlassBackground(child: SizedBox.shrink()),
              Container(
                width: double.infinity,
                height: size.height * 0.42,
                decoration: BoxDecoration(
                  gradient: heroGradient,
                ),
              ),
              Positioned(
                top: -40,
                right: -30,
                child: Container(
                  width: 160,
                  height: 160,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: AppConstants.primaryLight.withValues(alpha: 0.12),
                  ),
                ),
              ),
              Positioned(
                top: size.height * 0.36,
                left: 0,
                right: 0,
                child: Container(
                  height: 48,
                  decoration: BoxDecoration(
                    color: sheetTint,
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(36),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(
                          alpha: theme.brightness == Brightness.dark
                              ? 0.35
                              : 0.04,
                        ),
                        blurRadius: 24,
                        offset: const Offset(0, -6),
                      ),
                    ],
                  ),
                ),
              ),
              SafeArea(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: Column(
                    children: [
                      const SizedBox(height: 28),
                      AnimatedBuilder(
                        animation: _headerOpacity,
                        builder: (_, _) => Opacity(
                          opacity: _headerOpacity.value,
                          child: _buildHeader(),
                        ),
                      ),
                      const SizedBox(height: 36),
                      AnimatedBuilder(
                        animation: _animController,
                        builder: (_, _) => Opacity(
                          opacity: _cardOpacity.value,
                          child: Transform.translate(
                            offset: Offset(0, _cardSlide.value),
                            child: _isPhoneStep
                                ? _buildPhoneCard(auth, theme, cc)
                                : _buildPinCard(auth, theme, cc),
                          ),
                        ),
                      ),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      children: [
        const AppBrandLogo(size: 88),
        const SizedBox(height: 16),
        const Text(
          AppConstants.appName,
          style: TextStyle(
            fontSize: 26,
            fontWeight: FontWeight.w800,
            color: Colors.white,
            letterSpacing: -0.3,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          AppConstants.tagline,
          style: TextStyle(
            fontSize: 14,
            color: Colors.white.withValues(alpha: 0.8),
            fontWeight: FontWeight.w400,
          ),
        ),
      ],
    );
  }

  Widget _buildPhoneCard(AuthState auth, ThemeData theme, CustomerColors cc) {
    final fillColor = cc.chromeMuted;

    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(26),
      padding: const EdgeInsets.all(24),
      blurSigma: 24,
      child: Form(
        key: _phoneKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Karibu!',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w700,
                color: theme.colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Weka namba yako ya simu kuendelea',
              style: TextStyle(
                fontSize: 13,
                color: theme.textTheme.bodyMedium?.color,
              ),
            ),
            const SizedBox(height: 24),

            TextFormField(
              controller: _phoneCtrl,
              keyboardType: TextInputType.phone,
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => _checkPhone(),
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: InputDecoration(
                labelText: 'Namba ya Simu',
                hintText: '07XXXXXXXX',
                prefixIcon: const Icon(Icons.phone_rounded, size: 20),
                filled: true,
                fillColor: fillColor,
              ),
              validator: (v) {
                if (v == null || v.trim().length < 9) {
                  return 'Weka namba sahihi ya simu';
                }
                return null;
              },
            ),
            const SizedBox(height: 24),

            AppButton(
              label: 'Endelea',
              width: double.infinity,
              isLoading: auth.isLoading,
              icon: Icons.arrow_forward_rounded,
              onPressed: _checkPhone,
            ),

            const SizedBox(height: 20),
            _securityNote(theme, cc),
          ],
        ),
      ),
    );
  }

  Widget _buildPinCard(AuthState auth, ThemeData theme, CustomerColors cc) {
    final fillColor = cc.chromeMuted;
    final isNewPin = _hasPin != true;

    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(26),
      padding: const EdgeInsets.all(24),
      blurSigma: 24,
      child: Form(
        key: _pinKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Back button + title
            Row(
              children: [
                GestureDetector(
                  onTap: _goBackToPhone,
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: cc.primarySurface.withValues(alpha: 0.8),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: cc.border.withValues(alpha: 0.6),
                      ),
                    ),
                    child: Icon(
                      Icons.arrow_back_rounded,
                      size: 18,
                      color: theme.colorScheme.onSurface,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    isNewPin ? 'Weka PIN Yako' : 'Karibu, $_customerName!',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: theme.colorScheme.onSurface,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Padding(
              padding: const EdgeInsets.only(left: 42),
              child: Text(
                isNewPin
                    ? 'Tengeneza PIN ya tarakimu 4-6 ya kuingia'
                    : 'Weka PIN yako ya kuingia',
                style: TextStyle(
                  fontSize: 13,
                  color: theme.textTheme.bodyMedium?.color,
                ),
              ),
            ),
            const SizedBox(height: 24),

            // PIN field
            TextFormField(
              controller: _pinCtrl,
              keyboardType: TextInputType.number,
              obscureText: _obscurePin,
              maxLength: 6,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              textInputAction: isNewPin
                  ? TextInputAction.next
                  : TextInputAction.done,
              onFieldSubmitted: isNewPin ? null : (_) => _submitPin(),
              decoration: InputDecoration(
                labelText: isNewPin ? 'PIN Mpya' : 'PIN',
                hintText: '••••',
                counterText: '',
                prefixIcon: const Icon(Icons.lock_rounded, size: 20),
                filled: true,
                fillColor: fillColor,
                suffixIcon: GestureDetector(
                  onTap: () => setState(() => _obscurePin = !_obscurePin),
                  child: Icon(
                    _obscurePin
                        ? Icons.visibility_off_outlined
                        : Icons.visibility_outlined,
                    size: 20,
                    color: theme.textTheme.bodyMedium?.color,
                  ),
                ),
              ),
              validator: (v) {
                if (v == null || v.length < 4) {
                  return 'PIN lazima iwe tarakimu 4-6';
                }
                return null;
              },
            ),

            // Confirm PIN (only for new PIN)
            if (isNewPin) ...[
              const SizedBox(height: 14),
              TextFormField(
                controller: _pinConfirmCtrl,
                keyboardType: TextInputType.number,
                obscureText: _obscureConfirm,
                maxLength: 6,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                textInputAction: TextInputAction.done,
                onFieldSubmitted: (_) => _submitPin(),
                decoration: InputDecoration(
                  labelText: 'Thibitisha PIN',
                  hintText: '••••',
                  counterText: '',
                  prefixIcon: const Icon(Icons.lock_outline_rounded, size: 20),
                  filled: true,
                  fillColor: fillColor,
                  suffixIcon: GestureDetector(
                    onTap: () =>
                        setState(() => _obscureConfirm = !_obscureConfirm),
                    child: Icon(
                      _obscureConfirm
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined,
                      size: 20,
                      color: theme.textTheme.bodyMedium?.color,
                    ),
                  ),
                ),
                validator: (v) {
                  if (v != _pinCtrl.text) return 'PIN hazilingani';
                  return null;
                },
              ),
            ],

            const SizedBox(height: 24),

            AppButton(
              label: isNewPin ? 'Weka PIN na Uingie' : 'Ingia',
              width: double.infinity,
              isLoading: auth.isLoading,
              icon: isNewPin ? Icons.lock_open_rounded : Icons.login_rounded,
              onPressed: _submitPin,
            ),

            const SizedBox(height: 20),
            _securityNote(theme, cc),
          ],
        ),
      ),
    );
  }

  Widget _securityNote(ThemeData theme, CustomerColors cc) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: cc.chromeMuted.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: cc.border.withValues(alpha: 0.5)),
      ),
      child: Row(
        children: [
          Icon(
            Icons.security_rounded,
            size: 16,
            color: theme.textTheme.bodyMedium?.color,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              'Taarifa zako zinalindwa kwa usalama.',
              style: TextStyle(
                fontSize: 11,
                color: theme.textTheme.bodyMedium?.color,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
