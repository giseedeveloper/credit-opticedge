import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../config/customer_colors.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/auth_provider.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);
    final c = auth.customer;
    final cc = CustomerColors.of(context);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: Text(
          'Profaili Yangu',
          style: TextStyle(
            fontWeight: FontWeight.w800,
            letterSpacing: -0.4,
            color: cc.textPrimary,
          ),
        ),
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
      ),
      body: PremiumGlassBackground(
        child: c == null
            ? const Center(
                child: CircularProgressIndicator(color: AppConstants.primary),
              )
            : ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
                children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(26),
                  child: BackdropFilter(
                    filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
                    child: Container(
                      padding: const EdgeInsets.all(28),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: cc.homeHeroGradientColors,
                        ),
                        borderRadius: BorderRadius.circular(26),
                        border: Border.all(
                          color: cc.isDark
                              ? cc.glassCardBorder.withValues(alpha: 0.55)
                              : Colors.white.withValues(alpha: 0.72),
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: AppConstants.primary.withValues(alpha: 0.1),
                            blurRadius: 32,
                            offset: const Offset(0, 18),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Container(
                            width: 80,
                            height: 80,
                            decoration: BoxDecoration(
                              color: cc.primarySurface,
                              borderRadius: BorderRadius.circular(24),
                              border: Border.all(
                                color: AppConstants.primary.withValues(alpha: 0.2),
                              ),
                            ),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(24),
                              child: c.headshotUrl != null &&
                                      c.headshotUrl!.trim().isNotEmpty
                                  ? Image.network(
                                      c.headshotUrl!,
                                      fit: BoxFit.cover,
                                      errorBuilder: (_, __, ___) => Center(
                                        child: Text(
                                          _initials(c.firstName, c.lastName),
                                          style: const TextStyle(
                                            fontSize: 30,
                                            fontWeight: FontWeight.w800,
                                            color: AppConstants.primaryDark,
                                          ),
                                        ),
                                      ),
                                    )
                                  : Center(
                                      child: Text(
                                        _initials(c.firstName, c.lastName),
                                        style: const TextStyle(
                                          fontSize: 30,
                                          fontWeight: FontWeight.w800,
                                          color: AppConstants.primaryDark,
                                        ),
                                      ),
                                    ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            c.fullName,
                            style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.w800,
                              color: cc.textPrimary,
                              letterSpacing: -0.4,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: cc.isDark
                                  ? cc.glassCardTint.withValues(alpha: 0.65)
                                  : Colors.white.withValues(alpha: 0.58),
                              borderRadius: BorderRadius.circular(999),
                              border: Border.all(
                                color: cc.border.withValues(alpha: 0.55),
                              ),
                            ),
                            child: Text(
                              c.phoneDisplay ?? c.phone,
                              style: TextStyle(
                                color: cc.textSecondary,
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 18),

                _SectionCard(
                  title: 'Taarifa Binafsi',
                  icon: Icons.person_rounded,
                  iconColor: AppConstants.primaryDark,
                  iconBg: cc.primarySurface,
                  children: [
                    _InfoRow('Jina Kamili', c.fullName),
                    _InfoRow('Simu', c.phoneDisplay ?? c.phone),
                    if (c.email != null) _InfoRow('Barua Pepe', c.email!),
                    if (c.gender != null)
                      _InfoRow(
                        'Jinsia',
                        c.gender == 'male' ? 'Mwanaume' : 'Mwanamke',
                      ),
                    if (c.nidaNumber != null) _InfoRow('NIDA', c.nidaNumber!),
                  ],
                ),
                const SizedBox(height: 14),

                // Vendor Info
                if (c.vendor != null)
                  _SectionCard(
                    title: 'Muuzaji',
                    icon: Icons.store_rounded,
                    iconColor: AppConstants.primary,
                    iconBg: cc.primarySurface,
                    children: [
                      _InfoRow('Jina', c.vendor!.name),
                      if (c.vendor!.phone != null)
                        _InfoRow('Simu', c.vendor!.phone!),
                      if (c.vendor!.address != null)
                        _InfoRow('Anwani', c.vendor!.address!),
                    ],
                  ),
                if (c.vendor != null) const SizedBox(height: 14),

                // Branch Info
                if (c.branch != null)
                  _SectionCard(
                    title: 'Tawi',
                    icon: Icons.location_on_rounded,
                    iconColor: AppConstants.success,
                    iconBg: cc.successSurface,
                    children: [
                      _InfoRow('Jina', c.branch!.name),
                      if (c.branch!.phone != null)
                        _InfoRow('Simu', c.branch!.phone!),
                      if (c.branch!.region != null)
                        _InfoRow('Mkoa', c.branch!.region!),
                      if (c.branch!.address != null)
                        _InfoRow('Anwani', c.branch!.address!),
                    ],
                  ),
                const SizedBox(height: 18),

                // Change PIN
                _buildActionTile(
                  context,
                  icon: Icons.lock_rounded,
                  iconColor: const Color(0xFF8B5CF6),
                  iconBg:
                      cc.isDark ? const Color(0xFF231A30) : const Color(0xFFF7F3FF),
                  title: 'Badilisha PIN',
                  onTap: () => _showChangePinDialog(context, ref),
                ),
                const SizedBox(height: 10),

                // Logout
                _buildActionTile(
                  context,
                  icon: Icons.logout_rounded,
                  iconColor: AppConstants.error,
                  iconBg: cc.errorSurface,
                  title: 'Ondoka',
                  titleColor: AppConstants.error,
                  onTap: () => _confirmLogout(context, ref),
                ),
                const SizedBox(height: 24),
                Center(
                  child: Text(
                    'Opticedge Customer v1.0',
                    style: TextStyle(
                      color: cc.textHint,
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
      ),
    );
  }

  Widget _buildActionTile(
    BuildContext context, {
    required IconData icon,
    required Color iconColor,
    required Color iconBg,
    required String title,
    Color? titleColor,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: GlassCard.surface(
        context,
        borderRadius: BorderRadius.circular(20),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: iconBg,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: iconColor, size: 20),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                  color: titleColor ?? CustomerColors.of(context).textPrimary,
                ),
              ),
            ),
            Icon(
              Icons.chevron_right_rounded,
              color: CustomerColors.of(context).textHint,
              size: 22,
            ),
          ],
        ),
      ),
    );
  }

  String _initials(String first, String last) {
    final f = first.isNotEmpty ? first[0].toUpperCase() : '';
    final l = last.isNotEmpty ? last[0].toUpperCase() : '';
    return '$f$l';
  }

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Ondoka'),
        content: const Text('Una uhakika unataka kuondoka?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Hapana'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              ref.read(authProvider.notifier).logout();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppConstants.error,
            ),
            child: const Text('Ndiyo, Ondoka'),
          ),
        ],
      ),
    );
  }

  void _showChangePinDialog(BuildContext context, WidgetRef ref) {
    final currentPinCtrl = TextEditingController();
    final newPinCtrl = TextEditingController();
    final confirmPinCtrl = TextEditingController();
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Badilisha PIN'),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: currentPinCtrl,
                obscureText: true,
                maxLength: 6,
                decoration: const InputDecoration(
                  labelText: 'PIN ya Sasa',
                  counterText: '',
                ),
                validator: (v) =>
                    v == null || v.length < 4 ? 'Weka PIN ya sasa' : null,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: newPinCtrl,
                obscureText: true,
                maxLength: 6,
                decoration: const InputDecoration(
                  labelText: 'PIN Mpya',
                  counterText: '',
                ),
                validator: (v) =>
                    v == null || v.length < 4 ? 'PIN lazima iwe 4-6' : null,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: confirmPinCtrl,
                obscureText: true,
                maxLength: 6,
                decoration: const InputDecoration(
                  labelText: 'Thibitisha PIN Mpya',
                  counterText: '',
                ),
                validator: (v) {
                  if (v != newPinCtrl.text) return 'PIN hazilingani';
                  return null;
                },
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Ghairi'),
          ),
          ElevatedButton(
            onPressed: () async {
              if (!formKey.currentState!.validate()) return;
              try {
                await _changePin(
                  currentPinCtrl.text,
                  newPinCtrl.text,
                  confirmPinCtrl.text,
                );
                if (ctx.mounted) Navigator.pop(ctx);
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('PIN imebadilishwa!'),
                      backgroundColor: AppConstants.success,
                    ),
                  );
                }
              } catch (e) {
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Imeshindikana: $e'),
                      backgroundColor: AppConstants.error,
                    ),
                  );
                }
              }
            },
            child: const Text('Badilisha'),
          ),
        ],
      ),
    );
  }

  Future<void> _changePin(String current, String newPin, String confirm) async {
    final res = await ApiClient.instance.put(
      '/pin',
      data: {
        'current_pin': current,
        'new_pin': newPin,
        'new_pin_confirmation': confirm,
      },
    );
    if (res.data['success'] != true) {
      throw res.data['message'] ?? 'Failed';
    }
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final Color iconColor;
  final Color iconBg;
  final List<Widget> children;
  const _SectionCard({
    required this.title,
    required this.icon,
    required this.iconColor,
    required this.iconBg,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(24),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: iconBg,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: iconColor, size: 18),
              ),
              const SizedBox(width: 10),
              Text(
                title,
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                  color: CustomerColors.of(context).textPrimary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ...children,
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              color: CustomerColors.of(context).textSecondary,
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: CustomerColors.of(context).textPrimary,
              ),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }
}
