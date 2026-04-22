import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/settings_provider.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/l10n/app_strings.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(settingsProvider);
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final surfaceColor = theme.cardTheme.color ?? theme.colorScheme.surface;
    final borderColor =
        isDark ? DesignTokens.darkBorder : AppConstants.border;
    final s = S.of(ref);

    return Scaffold(
      appBar: AppBar(
        title: Text(s.settings),
        actions: [
          IconButton(
            icon: Icon(
              isDark ? Icons.light_mode_rounded : Icons.dark_mode_rounded,
              size: 22,
            ),
            onPressed: () {
              ref.read(settingsProvider.notifier).setThemeMode(
                    isDark ? ThemeMode.light : ThemeMode.dark,
                  );
            },
          ),
          const SizedBox(width: 8),
        ],
      ),
      backgroundColor: Colors.transparent,
      body: PremiumGlassBackground(
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          children: [
          // ─── Appearance ──────────────────────────────────
          _SectionHeader(label: s.appearance, icon: Icons.palette_outlined),
          const SizedBox(height: 10),
          _SettingsCard(
            borderColor: borderColor,
            surfaceColor: surfaceColor,
            children: [
              _ThemeSelector(
                current: settings.themeMode,
                isDark: isDark,
                onChanged: (mode) =>
                    ref.read(settingsProvider.notifier).setThemeMode(mode),
              ),
            ],
          ),

          const SizedBox(height: 28),

          // ─── Language ────────────────────────────────────
          _SectionHeader(label: s.language, icon: Icons.translate_rounded),
          const SizedBox(height: 10),
          _SettingsCard(
            borderColor: borderColor,
            surfaceColor: surfaceColor,
            children: [
              _LanguageSelector(
                current: settings.language,
                onChanged: (lang) =>
                    ref.read(settingsProvider.notifier).setLanguage(lang),
              ),
            ],
          ),

          const SizedBox(height: 28),

          // ─── Security ────────────────────────────────────
          _SectionHeader(label: s.security, icon: Icons.shield_outlined),
          const SizedBox(height: 10),
          _SettingsCard(
            borderColor: borderColor,
            surfaceColor: surfaceColor,
            children: [
              _SwitchTile(
                icon: Icons.fingerprint_rounded,
                title: s.biometricLogin,
                subtitle: s.biometricSubtitle,
                value: settings.biometricEnabled,
                onChanged: (v) async {
                  final err = await ref
                      .read(settingsProvider.notifier)
                      .toggleBiometric(v);
                  if (err != null && context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(err),
                        behavior: SnackBarBehavior.floating,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10)),
                      ),
                    );
                  }
                },
              ),
              Divider(height: 1, color: borderColor),
              _SwitchTile(
                icon: Icons.notifications_outlined,
                title: s.pushNotifications,
                subtitle: s.pushNotificationsSubtitle,
                value: settings.notificationsEnabled,
                onChanged: (v) =>
                    ref.read(settingsProvider.notifier).toggleNotifications(v),
              ),
            ],
          ),

          const SizedBox(height: 28),

          // ─── About ───────────────────────────────────────
          _SectionHeader(label: s.about, icon: Icons.info_outline_rounded),
          const SizedBox(height: 10),
          _SettingsCard(
            borderColor: borderColor,
            surfaceColor: surfaceColor,
            children: [
              _TapTile(
                icon: Icons.description_outlined,
                title: s.privacyPolicy,
                onTap: () => _showSheet(context, s.privacyPolicy, _privacyText),
              ),
              Divider(height: 1, color: borderColor),
              _TapTile(
                icon: Icons.gavel_rounded,
                title: s.termsOfService,
                onTap: () => _showSheet(context, s.termsOfService, _termsText),
              ),
              Divider(height: 1, color: borderColor),
              _TapTile(
                icon: Icons.help_outline_rounded,
                title: s.helpAndSupport,
                onTap: () => _showSheet(context, s.helpAndSupport, _helpText),
              ),
              Divider(height: 1, color: borderColor),
              _TapTile(
                icon: Icons.info_outline_rounded,
                title: s.aboutApp,
                trailing: Text(
                  'v1.0.0',
                  style: TextStyle(
                    fontSize: 12,
                    color: theme.textTheme.bodySmall?.color,
                  ),
                ),
                onTap: () => showAboutDialog(
                  context: context,
                  applicationName: AppConstants.appName,
                  applicationVersion: '1.0.0',
                  applicationLegalese:
                      '\u00a9 2026 Opticedge Africa. All rights reserved.',
                  children: [
                    const SizedBox(height: 16),
                    const Text(
                      'Opticedge FO is a premium field officer app designed for '
                      'fast, secure customer onboarding and KYC processing.',
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 28),

          // ─── Danger Zone ─────────────────────────────────
          _SectionHeader(
            label: s.account,
            icon: Icons.manage_accounts_outlined,
          ),
          const SizedBox(height: 10),
          _SettingsCard(
            borderColor: borderColor,
            surfaceColor: surfaceColor,
            children: [
              _TapTile(
                icon: Icons.delete_outline_rounded,
                title: s.clearLocalData,
                iconColor: AppConstants.warning,
                onTap: () => _confirmClear(context, ref),
              ),
              Divider(height: 1, color: borderColor),
              _TapTile(
                icon: Icons.logout_rounded,
                title: s.logOut,
                iconColor: AppConstants.error,
                onTap: () => _confirmLogout(context, ref),
              ),
            ],
          ),

          const SizedBox(height: 40),

          // Footer
          Center(
            child: Column(
              children: [
                Text(
                  s.madeWithLoveInTanzania,
                  style: TextStyle(
                    fontSize: 12,
                    color: theme.textTheme.bodySmall?.color,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Opticedge Africa \u00a9 2026',
                  style: TextStyle(
                    fontSize: 11,
                    color: theme.textTheme.bodySmall?.color,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  void _showSheet(BuildContext context, String title, String body) {
    final theme = Theme.of(context);
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        maxChildSize: 0.9,
        minChildSize: 0.4,
        expand: false,
        builder: (_, controller) => Padding(
          padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: theme.dividerColor,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text(title, style: theme.textTheme.headlineMedium),
              const SizedBox(height: 16),
              Expanded(
                child: SingleChildScrollView(
                  controller: controller,
                  child: Text(body, style: theme.textTheme.bodyMedium),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context, WidgetRef ref) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Log Out'),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Log Out',
                style: TextStyle(color: AppConstants.error)),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      context.go('/login');
      ref.read(authProvider.notifier).logout();
    }
  }

  void _confirmClear(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Clear Local Data'),
        content: const Text(
          'This will remove cached data and KYC drafts. '
          'You will not be logged out.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: const Text('Local data cleared'),
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10)),
                ),
              );
            },
            child: const Text('Clear',
                style: TextStyle(color: AppConstants.warning)),
          ),
        ],
      ),
    );
  }
}

// ─── Reusable Widgets ────────────────────────────────────────────────────────

class _SectionHeader extends StatelessWidget {
  final String label;
  final IconData icon;
  const _SectionHeader({required this.label, required this.icon});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18, color: AppConstants.primary),
        const SizedBox(width: 8),
        Text(
          label.toUpperCase(),
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.8,
            color: Theme.of(context).textTheme.bodySmall?.color,
          ),
        ),
      ],
    );
  }
}

class _SettingsCard extends StatelessWidget {
  final List<Widget> children;
  final Color borderColor;
  final Color surfaceColor;
  const _SettingsCard({
    required this.children,
    required this.borderColor,
    required this.surfaceColor,
  });

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      tint: surfaceColor,
      borderRadius: BorderRadius.circular(18),
      borderColor: borderColor,
      padding: EdgeInsets.zero,
      child: Column(children: children),
    );
  }
}

class _ThemeSelector extends StatelessWidget {
  final ThemeMode current;
  final bool isDark;
  final ValueChanged<ThemeMode> onChanged;
  const _ThemeSelector({
    required this.current,
    required this.isDark,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          _ThemeChip(
            icon: Icons.brightness_auto_rounded,
            label: 'System',
            selected: current == ThemeMode.system,
            onTap: () => onChanged(ThemeMode.system),
          ),
          const SizedBox(width: 10),
          _ThemeChip(
            icon: Icons.light_mode_rounded,
            label: 'Light',
            selected: current == ThemeMode.light,
            onTap: () => onChanged(ThemeMode.light),
          ),
          const SizedBox(width: 10),
          _ThemeChip(
            icon: Icons.dark_mode_rounded,
            label: 'Dark',
            selected: current == ThemeMode.dark,
            onTap: () => onChanged(ThemeMode.dark),
          ),
        ],
      ),
    );
  }
}

class _ThemeChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;
  const _ThemeChip({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: selected
                ? AppConstants.primary.withValues(alpha: 0.1)
                : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: selected ? AppConstants.primary : Colors.transparent,
              width: 1.5,
            ),
          ),
          child: Column(
            children: [
              Icon(icon,
                  size: 22,
                  color: selected
                      ? AppConstants.primary
                      : Theme.of(context).textTheme.bodySmall?.color),
              const SizedBox(height: 6),
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
                  color: selected
                      ? AppConstants.primary
                      : Theme.of(context).textTheme.bodyMedium?.color,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LanguageSelector extends StatelessWidget {
  final AppLanguage current;
  final ValueChanged<AppLanguage> onChanged;
  const _LanguageSelector({required this.current, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: AppLanguage.values.map((lang) {
        final selected = current == lang;
        return InkWell(
          onTap: () => onChanged(lang),
          borderRadius: lang == AppLanguage.en
              ? const BorderRadius.vertical(top: Radius.circular(16))
              : const BorderRadius.vertical(bottom: Radius.circular(16)),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                Text(lang.flag, style: const TextStyle(fontSize: 22)),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        lang.label,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight:
                              selected ? FontWeight.w600 : FontWeight.w400,
                          color: Theme.of(context).textTheme.bodyLarge?.color,
                        ),
                      ),
                      Text(
                        lang == AppLanguage.en
                            ? 'English language'
                            : 'Lugha ya Kiswahili',
                        style: TextStyle(
                          fontSize: 12,
                          color: Theme.of(context).textTheme.bodySmall?.color,
                        ),
                      ),
                    ],
                  ),
                ),
                AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  width: 22,
                  height: 22,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: selected ? AppConstants.primary : Colors.transparent,
                    border: Border.all(
                      color: selected
                          ? AppConstants.primary
                          : (Theme.of(context).textTheme.bodySmall?.color ??
                              Colors.grey),
                      width: 2,
                    ),
                  ),
                  child: selected
                      ? const Icon(Icons.check, size: 14, color: Colors.white)
                      : const SizedBox.shrink(),
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _SwitchTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;

  const _SwitchTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: AppConstants.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 18, color: AppConstants.primary),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: Theme.of(context).textTheme.bodyLarge?.color,
                    )),
                Text(subtitle,
                    style: TextStyle(
                      fontSize: 12,
                      color: Theme.of(context).textTheme.bodySmall?.color,
                    )),
              ],
            ),
          ),
          Switch.adaptive(
            value: value,
            onChanged: onChanged,
            activeThumbColor: AppConstants.primary,
            activeTrackColor: AppConstants.primary.withValues(alpha: 0.35),
          ),
        ],
      ),
    );
  }
}

class _TapTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color? iconColor;
  final Widget? trailing;
  final VoidCallback onTap;

  const _TapTile({
    required this.icon,
    required this.title,
    required this.onTap,
    this.iconColor,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    final color = iconColor ?? AppConstants.primary;
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, size: 18, color: color),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(title,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: Theme.of(context).textTheme.bodyLarge?.color,
                  )),
            ),
            trailing ??
                Icon(Icons.chevron_right_rounded,
                    size: 20,
                    color: Theme.of(context).textTheme.bodySmall?.color),
          ],
        ),
      ),
    );
  }
}

// ─── Static Content ──────────────────────────────────────────────────────────

const _privacyText = '''
Opticedge Africa Privacy Policy

Last updated: April 2026

1. Information We Collect
We collect personal information that you provide during the KYC process, including but not limited to: full name, national ID, phone number, email, photographs, employment details, and next of kin information.

2. How We Use Your Information
Your information is used solely for the purpose of customer onboarding, credit assessment, and regulatory compliance. We do not sell your data to third parties.

3. Data Storage & Security
All data is encrypted in transit and at rest. We use industry-standard security measures including TLS 1.3, AES-256 encryption, and role-based access controls.

4. Data Retention
Customer data is retained for the duration of the service agreement plus any regulatory hold period. You may request data deletion subject to legal requirements.

5. Your Rights
You have the right to access, correct, or request deletion of your personal data. Contact our Data Protection Officer at privacy@opticedgeafrica.net.

6. Contact
For privacy concerns, email: privacy@opticedgeafrica.net
''';

const _termsText = '''
Opticedge Africa Terms of Service

Last updated: April 2026

1. Acceptance
By using the Opticedge FO application, you agree to these terms and conditions.

2. Authorized Use
This application is intended solely for authorized field officers of Opticedge Africa. Unauthorized access is prohibited and may result in legal action.

3. Data Accuracy
Field officers are responsible for ensuring the accuracy of data collected during the KYC process. Falsification of records is grounds for termination and legal prosecution.

4. Confidentiality
All customer information accessed through this app is strictly confidential. Sharing customer data outside authorized channels is prohibited.

5. Device Security
You are responsible for securing the device on which this app is installed. Report lost or stolen devices immediately to your supervisor.

6. Service Availability
We strive to maintain 99.9% uptime but do not guarantee uninterrupted service. Scheduled maintenance windows will be communicated in advance.

7. Limitation of Liability
Opticedge Africa shall not be liable for indirect, incidental, or consequential damages arising from app usage.
''';

const _helpText = '''
Help & Support

Need assistance? We're here to help.

📞 Support Hotline
Call: +255 700 000 000
Available Mon-Sat, 8:00 AM – 6:00 PM (EAT)

📧 Email Support
Send your queries to: support@opticedgeafrica.net
Response time: within 24 hours

🏢 Office
Opticedge Africa Ltd
Dar es Salaam, Tanzania

💡 Quick Tips
• Make sure you have a stable internet connection before submitting KYC data
• Use good lighting when taking ID and customer photos
• Save your work as drafts if you need to continue later
• Sync your data regularly to avoid losing progress

🐛 Report a Bug
If you encounter a bug, email: dev@opticedgeafrica.net with a screenshot and description.
''';
