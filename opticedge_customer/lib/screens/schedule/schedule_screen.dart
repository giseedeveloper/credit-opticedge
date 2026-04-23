import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/constants.dart';
import '../../config/customer_colors.dart';
import '../../core/providers/loan_provider.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

final _currencyFmt = NumberFormat('#,##0', 'en');

class ScheduleScreen extends ConsumerStatefulWidget {
  const ScheduleScreen({super.key});

  @override
  ConsumerState<ScheduleScreen> createState() => _ScheduleScreenState();
}

class _ScheduleScreenState extends ConsumerState<ScheduleScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(scheduleProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(scheduleProvider);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: const Text(
          'Ratiba ya Malipo',
          style: TextStyle(fontWeight: FontWeight.w800, letterSpacing: -0.4),
        ),
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
      ),
      body: PremiumGlassBackground(
        child: state.isLoading
            ? const Center(
                child: CircularProgressIndicator(color: AppConstants.primary),
              )
            : state.error != null
                ? _buildError(state.error!)
                : state.portalState == 'released_pending_disbursement'
                    ? _buildPendingDisbursement(context, state)
                    : state.schedule == null
                        ? _buildEmpty(state.statusMessage)
                        : RefreshIndicator(
                            color: AppConstants.primary,
                            onRefresh: () =>
                                ref.read(scheduleProvider.notifier).load(),
                            child: _buildList(context, state),
                          ),
      ),
    );
  }

  Widget _buildError(String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: AppConstants.error.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Icon(
                Icons.wifi_off_rounded,
                color: AppConstants.error,
                size: 32,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: CustomerColors.of(context).textPrimary,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () => ref.read(scheduleProvider.notifier).load(),
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('Jaribu Tena'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppConstants.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
                elevation: 0,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmpty(String? message) {
    return RefreshIndicator(
      color: AppConstants.primary,
      onRefresh: () => ref.read(scheduleProvider.notifier).load(),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          const SizedBox(height: 160),
          Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    color: CustomerColors.of(context).primarySurface,
                    borderRadius: BorderRadius.circular(22),
                  ),
                  child: const Icon(
                    Icons.calendar_month_rounded,
                    color: AppConstants.primary,
                    size: 36,
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  'Ratiba Haijapatikana',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: CustomerColors.of(context).textPrimary,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  message ??
                      'Ratiba ya malipo itaonekana hapa akaunti ya mkopo ikishatayarishwa.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 13,
                    color: CustomerColors.of(context).textSecondary,
                    height: 1.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPendingDisbursement(
    BuildContext context,
    ScheduleState state,
  ) {
    final cc = CustomerColors.of(context);
    final release = state.releaseContext;
    final repaymentLabel = switch (release?.preferredRepayment) {
      'weekly' => 'Kila wiki',
      'biweekly' => 'Kila baada ya wiki 2',
      'monthly' => 'Kila mwezi',
      _ => 'Inathibitishwa',
    };

    return RefreshIndicator(
      color: AppConstants.primary,
      onRefresh: () => ref.read(scheduleProvider.notifier).load(),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        children: [
          GlassCard.tinted(
            surfaceTint: CustomerColors.of(context).warningSurface,
            accent: AppConstants.warning,
            borderRadius: BorderRadius.circular(26),
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    color: AppConstants.warning.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Icon(
                    Icons.hourglass_top_rounded,
                    color: AppConstants.warning,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Ratiba Inaandaliwa',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: CustomerColors.of(context).textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  state.statusMessage ??
                      'Kifaa kimeshatolewa. Mfumo unaandaa ratiba yako ya malipo.',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: CustomerColors.of(context).textSecondary,
                    fontSize: 13,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 18),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  alignment: WrapAlignment.center,
                  children: [
                    _buildContextChip(
                      'Malipo',
                      repaymentLabel,
                      AppConstants.warning,
                      cc.warningSurface,
                    ),
                    if (release?.assetReleasedAt != null)
                      _buildContextChip(
                        'Released',
                        release!.assetReleasedAt!.split(' ').first,
                        AppConstants.info,
                        cc.primarySurface,
                      ),
                    if ((release?.cashPrice ?? 0) > 0)
                      _buildContextChip(
                        'Bei ya kifaa',
                        'TZS ${_currencyFmt.format(release!.cashPrice)}',
                        AppConstants.primary,
                        cc.primarySurface,
                      ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          GlassCard.surface(
            context,
            borderRadius: BorderRadius.circular(22),
            padding: const EdgeInsets.all(18),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.info_outline_rounded,
                  color: AppConstants.info,
                  size: 20,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Ratiba itaonekana hapa mara tu akaunti ya mkopo ikikamilika kwenye mfumo wa credit.',
                    style: TextStyle(
                      color: CustomerColors.of(context).textSecondary,
                      fontSize: 13,
                      height: 1.5,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContextChip(
    String label,
    String value,
    Color color,
    Color background,
  ) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: TextStyle(
              color: color.withValues(alpha: 0.72),
              fontSize: 11,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildList(BuildContext context, ScheduleState state) {
    final s = state.schedule!;

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      itemCount: s.schedule.length + 1,
      itemBuilder: (ctx, index) {
        if (index == 0) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 18),
            child: Row(
              children: [
                _StatChip(
                  label: 'Jumla',
                  value: '${s.totalInstallments}',
                  color: AppConstants.primary,
                  bg: AppConstants.primarySurface,
                ),
                const SizedBox(width: 10),
                _StatChip(
                  label: 'Zilipolipwa',
                  value: '${s.paidInstallments}',
                  color: AppConstants.success,
                  bg: AppConstants.successSurface,
                ),
                const SizedBox(width: 10),
                _StatChip(
                  label: 'Zimebaki',
                  value: '${s.totalInstallments - s.paidInstallments}',
                  color: AppConstants.warning,
                  bg: AppConstants.warningSurface,
                ),
              ],
            ),
          );
        }

        final item = s.schedule[index - 1];
        final cc = CustomerColors.of(context);
        final statusColor = cc.loanStatusColor(item.status);
        final statusBg = cc.loanStatusBg(item.status);

        return Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: GlassCard.surface(
            context,
            borderRadius: BorderRadius.circular(20),
            padding: const EdgeInsets.all(16),
            child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: statusBg,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Center(
                  child: Text(
                    '#${item.installmentNumber}',
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      color: statusColor,
                      fontSize: 14,
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
                      'TZS ${_currencyFmt.format(item.amountDue)}',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 15,
                        color: CustomerColors.of(context).textPrimary,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      item.dueDate ?? '-',
                      style: TextStyle(
                        color: CustomerColors.of(context).textSecondary,
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: statusBg,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      _statusLabel(item.status),
                      style: TextStyle(
                        color: statusColor,
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  if (item.isOverdue && item.daysOverdue > 0)
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        'Siku ${item.daysOverdue}',
                        style: const TextStyle(
                          color: AppConstants.error,
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  if (item.isPaid && item.paidAt != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        item.paidAt!,
                        style: TextStyle(
                          color: CustomerColors.of(context).textHint,
                          fontSize: 10,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                ],
              ),
            ],
          ),
          ),
        );
      },
    );
  }

  String _statusLabel(String status) {
    return switch (status) {
      'paid' => 'Imelipwa',
      'pending' => 'Inasubiri',
      'partial' => 'Sehemu',
      'overdue' => 'Imechelewa',
      _ => status,
    };
  }
}

class _StatChip extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final Color bg;
  const _StatChip({
    required this.label,
    required this.value,
    required this.color,
    required this.bg,
  });

  @override
  Widget build(BuildContext context) {
    final cc = CustomerColors.of(context);
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(
          color: cc.scheduleStatGlassFill,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.22)),
          boxShadow: [
            BoxShadow(
              color: color.withValues(alpha: 0.05),
              blurRadius: 14,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          children: [
            Text(
              value,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: color,
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: color.withValues(alpha: 0.7),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
