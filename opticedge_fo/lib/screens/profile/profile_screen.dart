import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/l10n/app_strings.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;

    final theme = Theme.of(context);
    final s = S.of(ref);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: Text(s.profile),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.read(authProvider.notifier).refreshProfile(),
          ),
        ],
      ),
      body: PremiumGlassBackground(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              // Avatar card
              GlassCard.surface(
                context,
                borderRadius: BorderRadius.circular(26),
                padding: const EdgeInsets.all(18),
                child: Row(
                  children: [
                    Container(
                      width: 64,
                      height: 64,
                      decoration: BoxDecoration(
                        gradient: DesignTokens.heroGradientWithPrimaryHint,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.55),
                          width: 1.2,
                        ),
                      ),
                      child: Center(
                        child: Text(
                          user?.initials ?? 'FO',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
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
                            user?.name ?? '—',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: theme.textTheme.bodyLarge?.color,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              _pill(
                                context,
                                label: (user?.role?.toUpperCase() ?? ''),
                                icon: Icons.verified_user_outlined,
                              ),
                              if (user?.branch != null)
                                _pill(
                                  context,
                                  label: user!.branch!.name,
                                  icon: Icons.location_on_outlined,
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Info card
              _infoCard(context, [
                _InfoRow(Icons.email_outlined, s.email, user?.email ?? '—'),
                _InfoRow(Icons.phone_outlined, s.phone, user?.phone ?? '—'),
                _InfoRow(Icons.verified_user_outlined, s.status,
                    user?.isActive == true ? s.active : s.inactive),
              ]),

              const SizedBox(height: 12),

              // Permissions card
              _infoCard(context, [
                _InfoRow(
                  Icons.person_add_outlined,
                  s.canRegisterCustomers,
                  user?.canRegisterCustomers == true ? s.yes : s.no,
                  valueColor: user?.canRegisterCustomers == true
                      ? AppConstants.success
                      : AppConstants.error,
                ),
                _InfoRow(
                  Icons.admin_panel_settings_outlined,
                  s.adminAccess,
                  user?.isAdmin == true ? s.yes : s.no,
                  valueColor: user?.isAdmin == true
                      ? AppConstants.success
                      : AppConstants.textSecondary,
                ),
              ]),

              const SizedBox(height: 32),

              AppButton(
                label: s.signOut,
                width: double.infinity,
                outlined: true,
                color: AppConstants.error,
                icon: Icons.logout_rounded,
                onPressed: () async {
                  final confirm = await showDialog<bool>(
                    context: context,
                    builder: (dialogContext) => AlertDialog(
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16)),
                      title: Text(s.signOut),
                      content: Text(s.signOutConfirm),
                      actions: [
                        TextButton(
                            onPressed: () => Navigator.pop(dialogContext, false),
                            child: Text(s.cancel)),
                        TextButton(
                          onPressed: () => Navigator.pop(dialogContext, true),
                          child: Text(s.signOut,
                              style: const TextStyle(color: AppConstants.error)),
                        ),
                      ],
                    ),
                  );
                  if (confirm == true && context.mounted) {
                    context.go('/login');
                    ref.read(authProvider.notifier).logout();
                  }
                },
              ),
              const SizedBox(height: 8),
              Text(
                'Opticedge FO v1.0.0',
                style: TextStyle(
                    fontSize: 11, color: theme.textTheme.bodySmall?.color),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _pill(BuildContext context,
      {required String label, required IconData icon}) {
    if (label.trim().isEmpty) {
      return const SizedBox.shrink();
    }
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final fg = theme.textTheme.bodyMedium?.color;
    final bg = isDark
        ? DesignTokens.darkBorder.withValues(alpha: 0.35)
        : AppConstants.borderLight.withValues(alpha: 0.75);
    final bd = isDark ? DesignTokens.darkBorder : AppConstants.border;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: bd),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: fg),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: fg,
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoCard(BuildContext context, List<_InfoRow> rows) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final borderColor =
        isDark ? DesignTokens.darkBorder : AppConstants.border;

    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(20),
      padding: EdgeInsets.zero,
      child: Column(
        children: rows.asMap().entries.map((e) {
          final row = e.value;
          final isLast = e.key == rows.length - 1;
          return Column(
            children: [
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
                child: Row(
                  children: [
                    Icon(row.icon,
                        size: 18, color: theme.textTheme.bodyMedium?.color),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        row.label,
                        style: TextStyle(
                            fontSize: 13,
                            color: theme.textTheme.bodyMedium?.color),
                      ),
                    ),
                    Text(
                      row.value,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color:
                            row.valueColor ?? theme.textTheme.bodyLarge?.color,
                      ),
                    ),
                  ],
                ),
              ),
              if (!isLast) Divider(height: 1, color: borderColor),
            ],
          );
        }).toList(),
      ),
    );
  }
}

class _InfoRow {
  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;
  const _InfoRow(this.icon, this.label, this.value, {this.valueColor});
}
