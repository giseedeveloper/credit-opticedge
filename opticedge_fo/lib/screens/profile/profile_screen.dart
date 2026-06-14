import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/l10n/app_strings.dart';
import '../../core/models/user_model.dart';
import '../../core/providers/auth_provider.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';
import '../../widgets/kyc/kyc_wizard_ui.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);
    final user = auth.user;
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final s = S.of(ref);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              s.profile,
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w800,
                fontSize: 18,
              ),
            ),
            Text(
              'Account & permissions',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: isDark
                    ? Colors.white.withValues(alpha: 0.62)
                    : AppConstants.textSecondary,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Settings',
            icon: const Icon(Icons.settings_outlined, size: 22),
            onPressed: () => context.go('/settings'),
          ),
          IconButton(
            tooltip: 'Refresh',
            icon: auth.isLoading
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.refresh_rounded),
            onPressed: auth.isLoading
                ? null
                : () => ref.read(authProvider.notifier).refreshProfile(),
          ),
        ],
      ),
      body: PremiumGlassBackground(
        child: RefreshIndicator(
          color: AppConstants.primary,
          onRefresh: () => ref.read(authProvider.notifier).refreshProfile(),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
            children: [
              _ProfileHero(user: user, isDark: isDark),
              const SizedBox(height: 14),
              _SectionHeader(
                label: 'Account',
                icon: Icons.person_outline_rounded,
                isDark: isDark,
              ),
              const SizedBox(height: 8),
              _AccountCard(
                isDark: isDark,
                rows: [
                  _ProfileDetail(
                    icon: Icons.email_outlined,
                    label: s.email,
                    value: user?.email ?? '—',
                  ),
                  _ProfileDetail(
                    icon: Icons.phone_outlined,
                    label: s.phone,
                    value: user?.phone?.trim().isNotEmpty == true
                        ? user!.phone!
                        : '—',
                  ),
                  if (user?.branch != null)
                    _ProfileDetail(
                      icon: Icons.storefront_outlined,
                      label: 'Branch',
                      value: user!.branch!.name,
                    ),
                  if (user?.dealer != null)
                    _ProfileDetail(
                      icon: Icons.handshake_outlined,
                      label: 'Dealer',
                      value: user!.dealer!.name,
                    ),
                ],
              ),
              const SizedBox(height: 14),
              _SectionHeader(
                label: 'Access',
                icon: Icons.shield_outlined,
                isDark: isDark,
              ),
              const SizedBox(height: 8),
              _PermissionsGrid(user: user, s: s, isDark: isDark),
              const SizedBox(height: 14),
              _SectionHeader(
                label: 'Actions',
                icon: Icons.touch_app_outlined,
                isDark: isDark,
              ),
              const SizedBox(height: 8),
              _ActionTile(
                icon: Icons.settings_outlined,
                title: s.settings,
                subtitle: 'Theme, language & security',
                onTap: () => context.go('/settings'),
                isDark: isDark,
              ),
              const SizedBox(height: 20),
              AppButton(
                compact: true,
                label: s.signOut,
                width: double.infinity,
                outlined: true,
                color: AppConstants.error,
                icon: Icons.logout_rounded,
                onPressed: () => _confirmSignOut(context, ref, s),
              ),
              const SizedBox(height: 12),
              Center(
                child: Text(
                  'Opticedge FO v1.0.0',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 11,
                    color: theme.textTheme.bodySmall?.color,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _confirmSignOut(
    BuildContext context,
    WidgetRef ref,
    S s,
  ) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text(s.signOut),
        content: Text(s.signOutConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(s.cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(
              s.signOut,
              style: const TextStyle(color: AppConstants.error),
            ),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      context.go('/login');
      ref.read(authProvider.notifier).logout();
    }
  }
}

class _ProfileHero extends StatelessWidget {
  const _ProfileHero({required this.user, required this.isDark});

  final UserModel? user;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    final isActive = user?.isActive == true;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 14),
      decoration: BoxDecoration(
        gradient: DesignTokens.heroGradient,
        borderRadius: KycWizardUi.cardRadius,
        boxShadow: [
          BoxShadow(
            color: DesignTokens.heroEnd.withValues(alpha: 0.2),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _Avatar(initials: user?.initials ?? 'FO'),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      (user?.name ?? '').trim().isNotEmpty
                          ? user!.name
                          : 'Field Officer',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                        height: 1.2,
                      ),
                    ),
                    if ((user?.email ?? '').trim().isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        user!.email,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: Colors.white.withValues(alpha: 0.78),
                        ),
                      ),
                    ],
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: [
                        _HeroChip(
                          icon: Icons.verified_user_outlined,
                          label: (user?.role ?? 'Field Officer').toUpperCase(),
                        ),
                        _HeroChip(
                          icon: isActive
                              ? Icons.circle
                              : Icons.pause_circle_outline,
                          label: isActive ? 'Active' : 'Inactive',
                          iconColor: isActive
                              ? const Color(0xFF86EFAC)
                              : const Color(0xFFFDA4AF),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (user?.branch != null) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.14),
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.location_on_outlined,
                    size: 16,
                    color: Colors.white.withValues(alpha: 0.9),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      user!.branch!.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: Colors.white.withValues(alpha: 0.92),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.initials});

  final String initials;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 52,
      height: 52,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: LinearGradient(
          colors: [
            Colors.white.withValues(alpha: 0.22),
            Colors.white.withValues(alpha: 0.08),
          ],
        ),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.45),
          width: 1.2,
        ),
      ),
      child: Center(
        child: Text(
          initials,
          style: GoogleFonts.plusJakartaSans(
            color: Colors.white,
            fontSize: 16,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
  }
}

class _HeroChip extends StatelessWidget {
  const _HeroChip({
    required this.icon,
    required this.label,
    this.iconColor,
  });

  final IconData icon;
  final String label;
  final Color? iconColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: icon == Icons.circle ? 7 : 12,
            color: iconColor ?? Colors.white.withValues(alpha: 0.92),
          ),
          const SizedBox(width: 5),
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              color: Colors.white.withValues(alpha: 0.95),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({
    required this.label,
    required this.icon,
    required this.isDark,
  });

  final String label;
  final IconData icon;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(
            color: AppConstants.primary.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, size: 15, color: AppConstants.primary),
        ),
        const SizedBox(width: 8),
        Text(
          label.toUpperCase(),
          style: GoogleFonts.plusJakartaSans(
            fontSize: 11,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.7,
            color: isDark
                ? Colors.white.withValues(alpha: 0.55)
                : AppConstants.textSecondary,
          ),
        ),
      ],
    );
  }
}

class _AccountCard extends StatelessWidget {
  const _AccountCard({
    required this.rows,
    required this.isDark,
  });

  final List<_ProfileDetail> rows;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    final borderColor =
        isDark ? DesignTokens.darkBorder : AppConstants.border;

    return GlassCard.surface(
      context,
      padding: EdgeInsets.zero,
      borderRadius: KycWizardUi.cardRadius,
      child: Column(
        children: rows.asMap().entries.map((entry) {
          final row = entry.value;
          final isLast = entry.key == rows.length - 1;

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 11,
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: AppConstants.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(9),
                      ),
                      child: Icon(
                        row.icon,
                        size: 16,
                        color: AppConstants.primary,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            row.label,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: isDark
                                  ? Colors.white.withValues(alpha: 0.55)
                                  : AppConstants.textSecondary,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            row.value,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: Theme.of(context)
                                  .textTheme
                                  .bodyLarge
                                  ?.color,
                              height: 1.35,
                            ),
                          ),
                        ],
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

class _PermissionsGrid extends StatelessWidget {
  const _PermissionsGrid({
    required this.user,
    required this.s,
    required this.isDark,
  });

  final UserModel? user;
  final S s;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    final items = <_PermissionItem>[
      _PermissionItem(
        icon: Icons.person_add_outlined,
        label: s.canRegisterCustomers,
        enabled: user?.canRegisterCustomers == true,
      ),
      _PermissionItem(
        icon: Icons.admin_panel_settings_outlined,
        label: s.adminAccess,
        enabled: user?.isAdmin == true,
      ),
      _PermissionItem(
        icon: Icons.inventory_2_outlined,
        label: 'Stock',
        enabled: user?.canViewStock == true,
      ),
      _PermissionItem(
        icon: Icons.trending_up_rounded,
        label: 'Metrics',
        enabled: user?.canViewStaffMetrics == true,
      ),
      _PermissionItem(
        icon: Icons.support_agent_outlined,
        label: 'Recovery',
        enabled: user?.canViewRecovery == true,
      ),
      _PermissionItem(
        icon: Icons.assessment_outlined,
        label: 'Reports',
        enabled: user?.canViewReports == true,
      ),
    ];

    return LayoutBuilder(
      builder: (context, constraints) {
        final tileWidth = (constraints.maxWidth - 8) / 2;

        return Wrap(
          spacing: 8,
          runSpacing: 8,
          children: items
              .map(
                (item) => SizedBox(
                  width: tileWidth,
                  child: _PermissionTile(item: item, isDark: isDark, s: s),
                ),
              )
              .toList(),
        );
      },
    );
  }
}

class _PermissionTile extends StatelessWidget {
  const _PermissionTile({
    required this.item,
    required this.isDark,
    required this.s,
  });

  final _PermissionItem item;
  final bool isDark;
  final S s;

  @override
  Widget build(BuildContext context) {
    final enabled = item.enabled;
    final accent = enabled ? AppConstants.success : AppConstants.textHint;
    final surface = enabled
        ? (isDark
            ? DesignTokens.statGreenBgDark
            : const Color(0xFFECFDF5))
        : (isDark
            ? DesignTokens.darkBorder.withValues(alpha: 0.25)
            : AppConstants.borderLight);

    return GlassCard(
      tint: surface,
      borderRadius: KycWizardUi.cardRadius,
      borderColor: enabled
          ? AppConstants.success.withValues(alpha: 0.28)
          : (isDark ? DesignTokens.darkBorder : AppConstants.border),
      padding: const EdgeInsets.all(10),
      child: Row(
        children: [
          Container(
            width: 30,
            height: 30,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(item.icon, size: 15, color: accent),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.label,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Theme.of(context).textTheme.bodyLarge?.color,
                    height: 1.2,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  enabled ? s.yes : s.no,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: accent,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionTile extends StatelessWidget {
  const _ActionTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    required this.isDark,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: KycWizardUi.cardRadius,
        child: GlassCard.surface(
          context,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
          borderRadius: KycWizardUi.cardRadius,
          child: Row(
            children: [
              Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: AppConstants.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, size: 18, color: AppConstants.primary),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Theme.of(context).textTheme.bodyLarge?.color,
                      ),
                    ),
                    Text(
                      subtitle,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        color: isDark
                            ? Colors.white.withValues(alpha: 0.55)
                            : AppConstants.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_right_rounded,
                size: 20,
                color: Theme.of(context).textTheme.bodySmall?.color,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProfileDetail {
  const _ProfileDetail({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;
}

class _PermissionItem {
  const _PermissionItem({
    required this.icon,
    required this.label,
    required this.enabled,
  });

  final IconData icon;
  final String label;
  final bool enabled;
}
