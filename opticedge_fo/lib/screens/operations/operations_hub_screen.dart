import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/staff_ops_provider.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class OperationsHubScreen extends ConsumerStatefulWidget {
  const OperationsHubScreen({super.key});

  @override
  ConsumerState<OperationsHubScreen> createState() =>
      _OperationsHubScreenState();
}

class _OperationsHubScreenState extends ConsumerState<OperationsHubScreen> {
  final _imeiCtrl = TextEditingController();
  String _imeiQuery = '';
  Timer? _imeiDebounce;

  @override
  void dispose() {
    _imeiDebounce?.cancel();
    _imeiCtrl.dispose();
    super.dispose();
  }

  Future<void> _refresh() async {
    ref.invalidate(staffMetricsProvider);
    ref.invalidate(recoveryTicketsProvider);
    if (_imeiQuery.trim().length >= 14) {
      ref.invalidate(stockSearchProvider(_imeiQuery));
    }
    await Future<void>.delayed(const Duration(milliseconds: 300));
  }

  void _onImeiChanged(String value) {
    _imeiDebounce?.cancel();
    _imeiDebounce = Timer(const Duration(milliseconds: 450), () {
      if (!mounted) {
        return;
      }
      setState(() => _imeiQuery = value.trim());
    });
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).user;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final canMetrics = user?.canViewStaffMetrics ?? false;
    final canStock = user?.canViewStock ?? false;
    final canRecovery = user?.canViewRecovery ?? false;
    final hasAnyModule = canMetrics || canStock || canRecovery;

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.go('/dashboard'),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Field Operations',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w800,
                fontSize: 18,
              ),
            ),
            Text(
              'Stock, performance & recovery',
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
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _refresh,
          ),
        ],
      ),
      body: PremiumGlassBackground(
        child: RefreshIndicator(
          color: AppConstants.primary,
          onRefresh: _refresh,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
            children: [
              if (hasAnyModule) ...[
                _ModuleAccessRow(
                  canMetrics: canMetrics,
                  canStock: canStock,
                  canRecovery: canRecovery,
                  isDark: isDark,
                ),
                const SizedBox(height: 20),
              ],
              if (canMetrics) ...[
                const _SectionHeader(
                  title: 'My performance',
                  subtitle: 'Your onboarding impact',
                  color: DesignTokens.statGreen,
                  icon: Icons.trending_up_rounded,
                ),
                const SizedBox(height: 10),
                ref.watch(staffMetricsProvider).when(
                      loading: () => const _LoadingCard(),
                      error: (e, _) => _ErrorBanner(
                        message: e.toString(),
                        onRetry: () => ref.invalidate(staffMetricsProvider),
                      ),
                      data: (m) => Row(
                        children: [
                          Expanded(
                            child: _StatTile(
                              label: 'Customers onboarded',
                              value:
                                  '${m['total_customers_acquired'] ?? 0}',
                              color: DesignTokens.statGreen,
                              icon: Icons.people_alt_rounded,
                              isDark: isDark,
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _StatTile(
                              label: 'Active loans',
                              value: '${m['active_loans_managed'] ?? 0}',
                              color: DesignTokens.statBlue,
                              icon: Icons.account_balance_wallet_rounded,
                              isDark: isDark,
                            ),
                          ),
                        ],
                      ),
                    ),
                const SizedBox(height: 24),
              ],
              if (canStock) ...[
                const _SectionHeader(
                  title: 'Stock lookup',
                  subtitle: 'Find device by IMEI',
                  color: DesignTokens.statBlue,
                  icon: Icons.qr_code_scanner_rounded,
                ),
                const SizedBox(height: 10),
                GlassCard(
                  tint: isDark
                      ? DesignTokens.darkSurfaceElevated
                      : Colors.white,
                  borderRadius: BorderRadius.circular(18),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
                  borderColor:
                      isDark ? DesignTokens.darkBorder : AppConstants.border,
                  child: TextField(
                    controller: _imeiCtrl,
                    keyboardType: TextInputType.number,
                    onChanged: _onImeiChanged,
                    style: GoogleFonts.plusJakartaSans(fontSize: 14),
                    decoration: InputDecoration(
                      hintText: 'Weka IMEI (tarakimu 15)',
                      hintStyle: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        color: isDark
                            ? Colors.white.withValues(alpha: 0.45)
                            : AppConstants.textHint,
                      ),
                      prefixIcon: const Icon(
                        Icons.smartphone_rounded,
                        size: 22,
                        color: DesignTokens.statBlue,
                      ),
                      suffixIcon: _imeiCtrl.text.isNotEmpty
                          ? IconButton(
                              icon: Icon(
                                Icons.close_rounded,
                                size: 20,
                                color: isDark
                                    ? Colors.white.withValues(alpha: 0.7)
                                    : AppConstants.textSecondary,
                              ),
                              onPressed: () {
                                _imeiDebounce?.cancel();
                                _imeiCtrl.clear();
                                setState(() => _imeiQuery = '');
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                if (_imeiQuery.length < 14)
                  _HintCard(
                    icon: Icons.info_outline_rounded,
                    text: 'Ingiza angalau tarakimu 14 za IMEI kuanza utafutaji.',
                    color: DesignTokens.statBlue,
                    isDark: isDark,
                  )
                else
                  ref.watch(stockSearchProvider(_imeiQuery)).when(
                        loading: () => const _LoadingCard(),
                        error: (e, _) => _ErrorBanner(
                          message: e.toString(),
                          onRetry: () =>
                              ref.invalidate(stockSearchProvider(_imeiQuery)),
                        ),
                        data: (unit) {
                          if (unit == null) {
                            return _HintCard(
                              icon: Icons.search_off_rounded,
                              text: 'Hakuna kifaa kilichopatikana kwa IMEI hii.',
                              color: DesignTokens.statAmber,
                              isDark: isDark,
                            );
                          }

                          final modelName = unit['phone_model']?['name']
                                  ?.toString() ??
                              unit['model_name']?.toString() ??
                              'Device';
                          final status =
                              unit['status']?.toString() ?? 'unknown';

                          return _StockResultCard(
                            modelName: modelName,
                            status: status,
                            imei: unit['imei_1']?.toString() ?? '—',
                            isDark: isDark,
                          );
                        },
                      ),
                const SizedBox(height: 24),
              ],
              if (canRecovery) ...[
                const _SectionHeader(
                  title: 'Recovery tickets',
                  subtitle: 'Open assignments',
                  color: DesignTokens.statAmber,
                  icon: Icons.assignment_return_rounded,
                ),
                const SizedBox(height: 10),
                ref.watch(recoveryTicketsProvider).when(
                      loading: () => const _LoadingCard(),
                      error: (e, _) => _ErrorBanner(
                        message: e.toString(),
                        onRetry: () =>
                            ref.invalidate(recoveryTicketsProvider),
                      ),
                      data: (page) {
                        final items = _extractTicketList(page);

                        if (items.isEmpty) {
                          return _EmptyModuleCard(
                            icon: Icons.inbox_rounded,
                            title: 'Hakuna kazi za recovery',
                            subtitle:
                                'Tiketi mpya zitaonekana hapa unapopewa.',
                            color: DesignTokens.statAmber,
                            isDark: isDark,
                          );
                        }

                        return Column(
                          children: [
                            Align(
                              alignment: Alignment.centerRight,
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: DesignTokens.statAmber
                                      .withValues(alpha: isDark ? 0.2 : 0.12),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  '${items.length} open',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                    color: DesignTokens.statAmber,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),
                            ...items.take(10).map((raw) {
                              final ticket =
                                  Map<String, dynamic>.from(raw as Map);
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 10),
                                child: _RecoveryTicketCard(
                                  ticket: ticket,
                                  isDark: isDark,
                                ),
                              );
                            }),
                          ],
                        );
                      },
                    ),
              ],
              if (!hasAnyModule)
                _EmptyModuleCard(
                  icon: Icons.lock_outline_rounded,
                  title: 'Hakuna modules za operations',
                  subtitle:
                      'Jukumu lako halijumuishi stock, metrics au recovery. '
                      'Wasiliana na HQ ikiwa unahitaji ufikiaji.',
                  color: AppConstants.primary,
                  isDark: isDark,
                ),
            ],
          ),
        ),
      ),
    );
  }
}

List<dynamic> _extractTicketList(Map<String, dynamic> page) {
  if (page['data'] is List) {
    return page['data'] as List;
  }
  if (page['data'] is Map && page['data']['data'] is List) {
    return page['data']['data'] as List;
  }
  return const [];
}

class _ModuleAccessRow extends StatelessWidget {
  const _ModuleAccessRow({
    required this.canMetrics,
    required this.canStock,
    required this.canRecovery,
    required this.isDark,
  });

  final bool canMetrics;
  final bool canStock;
  final bool canRecovery;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    final chips = <Widget>[
      if (canMetrics)
        _AccessChip(
          label: 'Performance',
          color: DesignTokens.statGreen,
          isDark: isDark,
        ),
      if (canStock)
        _AccessChip(
          label: 'Stock',
          color: DesignTokens.statBlue,
          isDark: isDark,
        ),
      if (canRecovery)
        _AccessChip(
          label: 'Recovery',
          color: DesignTokens.statAmber,
          isDark: isDark,
        ),
    ];

    return SizedBox(
      height: 36,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: chips.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (_, i) => chips[i],
      ),
    );
  }
}

class _AccessChip extends StatelessWidget {
  const _AccessChip({
    required this.label,
    required this.color,
    required this.isDark,
  });

  final String label;
  final Color color;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: isDark ? 0.18 : 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.35)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.check_circle_rounded, size: 14, color: color),
          const SizedBox(width: 6),
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({
    required this.title,
    required this.subtitle,
    required this.color,
    required this.icon,
  });

  final String title;
  final String subtitle;
  final Color color;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Row(
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: color.withValues(alpha: isDark ? 0.2 : 0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: color, size: 22),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: color,
                ),
              ),
              Text(
                subtitle,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: isDark
                      ? Colors.white.withValues(alpha: 0.55)
                      : AppConstants.textSecondary,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
    required this.isDark,
  });

  final String label;
  final String value;
  final Color color;
  final IconData icon;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      tint: isDark ? DesignTokens.darkSurfaceElevated : Colors.white,
      borderRadius: BorderRadius.circular(18),
      padding: const EdgeInsets.all(14),
      borderColor: color.withValues(alpha: 0.25),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 10),
          Text(
            value,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: color,
              height: 1,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: isDark
                  ? Colors.white.withValues(alpha: 0.6)
                  : AppConstants.textSecondary,
              height: 1.25,
            ),
          ),
        ],
      ),
    );
  }
}

class _StockResultCard extends StatelessWidget {
  const _StockResultCard({
    required this.modelName,
    required this.status,
    required this.imei,
    required this.isDark,
  });

  final String modelName;
  final String status;
  final String imei;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(18),
      padding: EdgeInsets.zero,
      child: Row(
        children: [
          Container(
            width: 5,
            height: 88,
            decoration: const BoxDecoration(
              color: DesignTokens.statBlue,
              borderRadius: BorderRadius.horizontal(
                left: Radius.circular(18),
              ),
            ),
          ),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    modelName,
                    style: GoogleFonts.plusJakartaSans(
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 10),
                  _DetailLine(label: 'Status', value: status),
                  const SizedBox(height: 4),
                  _DetailLine(label: 'IMEI 1', value: imei),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  const _DetailLine({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Row(
      children: [
        Text(
          '$label: ',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 12,
            color: isDark
                ? Colors.white.withValues(alpha: 0.55)
                : AppConstants.textSecondary,
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ],
    );
  }
}

class _RecoveryTicketCard extends StatelessWidget {
  const _RecoveryTicketCard({
    required this.ticket,
    required this.isDark,
  });

  final Map<String, dynamic> ticket;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    final loan = ticket['loan'] is Map
        ? Map<String, dynamic>.from(ticket['loan'] as Map)
        : null;
    final customer = loan?['customer'] is Map
        ? loan!['customer']['full_name']?.toString()
        : null;
    final status = ticket['status']?.toString() ?? 'open';

    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(18),
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: DesignTokens.statAmber
                  .withValues(alpha: isDark ? 0.22 : 0.14),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.assignment_return_rounded,
              color: DesignTokens.statAmber,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  customer ?? 'Recovery ticket',
                  style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Status: $status',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 12,
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
            color: isDark
                ? Colors.white.withValues(alpha: 0.4)
                : AppConstants.textHint,
          ),
        ],
      ),
    );
  }
}

class _HintCard extends StatelessWidget {
  const _HintCard({
    required this.icon,
    required this.text,
    required this.color,
    required this.isDark,
  });

  final IconData icon;
  final String text;
  final Color color;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: isDark ? 0.14 : 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.22)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDark
                    ? Colors.white.withValues(alpha: 0.78)
                    : AppConstants.textSecondary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyModuleCard extends StatelessWidget {
  const _EmptyModuleCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.isDark,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final bool isDark;

  @override
  Widget build(BuildContext context) {
    return GlassCard.surface(
      context,
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
      child: Column(
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: color.withValues(alpha: isDark ? 0.18 : 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 34, color: color),
          ),
          const SizedBox(height: 16),
          Text(
            title,
            textAlign: TextAlign.center,
            style: GoogleFonts.plusJakartaSans(
              fontWeight: FontWeight.w800,
              fontSize: 16,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 13,
              height: 1.45,
              color: isDark
                  ? Colors.white.withValues(alpha: 0.58)
                  : AppConstants.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

class _LoadingCard extends StatelessWidget {
  const _LoadingCard();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 20),
      child: Center(
        child: CircularProgressIndicator(
          color: AppConstants.primary,
          strokeWidth: 2.5,
        ),
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppConstants.error.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: AppConstants.error.withValues(alpha: 0.2),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            message,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 13,
              color: AppConstants.error,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 10),
          TextButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: const Text('Jaribu tena'),
          ),
        ],
      ),
    );
  }
}
