import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/l10n/app_strings.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/glass_card.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;

    final theme = Theme.of(context);
    final s = S.of(ref);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(s.profile),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.read(authProvider.notifier).refreshProfile(),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Avatar card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: DesignTokens.heroGradientWithPrimaryHint,
                borderRadius: BorderRadius.circular(26),
                boxShadow: [
                  BoxShadow(
                    color: DesignTokens.heroEnd.withValues(alpha: 0.35),
                    blurRadius: 28,
                    offset: const Offset(0, 14),
                  ),
                ],
              ),
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 36,
                    backgroundColor: Colors.white.withValues(alpha: 0.25),
                    child: Text(
                      user?.initials ?? 'FO',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 24,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    user?.name ?? '—',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    user?.role?.toUpperCase() ?? '',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.8),
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      letterSpacing: 0.8,
                    ),
                  ),
                  if (user?.branch != null) ...[
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 5),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.location_on_outlined,
                              color: Colors.white70, size: 13),
                          const SizedBox(width: 4),
                          Text(
                            user!.branch!.name,
                            style: const TextStyle(
                                color: Colors.white70, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                  ],
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
    );
  }

  Widget _infoCard(BuildContext context, List<_InfoRow> rows) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final borderColor =
        isDark ? DesignTokens.darkBorder : AppConstants.border;

    return GlassCard(
      tint: Colors.white,
      borderRadius: BorderRadius.circular(20),
      borderColor: borderColor,
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
