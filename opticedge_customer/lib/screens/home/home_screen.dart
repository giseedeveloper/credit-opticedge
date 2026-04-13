import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:percent_indicator/circular_percent_indicator.dart';
import '../../config/constants.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/loan_provider.dart';

final _currencyFmt = NumberFormat('#,##0', 'en');

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _animController;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    );
    Future.microtask(() {
      ref.read(loanProvider.notifier).load();
      _animController.forward();
    });
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  String _greeting() {
    final h = DateTime.now().hour;
    if (h < 12) return 'Habari ya asubuhi';
    if (h < 17) return 'Habari ya mchana';
    return 'Habari ya jioni';
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authProvider);
    final loan = ref.watch(loanProvider);
    final name = auth.customer?.firstName ?? '';

    return Scaffold(
      backgroundColor: AppConstants.background,
      body: RefreshIndicator(
        color: AppConstants.primary,
        onRefresh: () => ref.read(loanProvider.notifier).load(),
        child: CustomScrollView(
          slivers: [
            _buildHeroAppBar(name, loan),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  if (loan.isLoading)
                    _buildShimmer()
                  else if (loan.error != null)
                    _ErrorBanner(
                      message: loan.error!,
                      onRetry: () => ref.read(loanProvider.notifier).load(),
                    )
                  else if (loan.portalState == 'released_pending_disbursement')
                    _buildPendingDisbursementState(loan)
                  else if (loan.loan == null)
                    _buildNoLoanState()
                  else ...[
                    _LoanProgressCard(loan: loan, anim: _animController),
                    const SizedBox(height: 18),
                    _NextPaymentCard(loan: loan),
                  ],
                  const SizedBox(height: 24),
                  _buildSectionTitle('Vitendo vya Haraka'),
                  const SizedBox(height: 14),
                  _buildQuickActions(context),
                  if (loan.loan != null) ...[
                    const SizedBox(height: 24),
                    _buildSectionTitle('Maelezo ya Mkopo'),
                    const SizedBox(height: 14),
                    _LoanDetailsCard(loan: loan),
                  ],
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  SliverAppBar _buildHeroAppBar(String name, LoanState loan) {
    return SliverAppBar(
      expandedHeight: 200,
      pinned: true,
      elevation: 0,
      backgroundColor: AppConstants.heroStart,
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          fit: StackFit.expand,
          children: [
            Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    AppConstants.heroStart,
                    AppConstants.heroEnd,
                    Color(0xFF10263F),
                  ],
                  stops: [0.0, 0.62, 1.0],
                ),
              ),
            ),
            Positioned(
              top: -30,
              right: -20,
              child: Container(
                width: 160,
                height: 160,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppConstants.primaryLight.withValues(alpha: 0.10),
                ),
              ),
            ),
            Positioned(
              left: -40,
              bottom: 10,
              child: Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: const Color(0xFF38BDF8).withValues(alpha: 0.08),
                ),
              ),
            ),
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _todayFormatted(),
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.white.withValues(alpha: 0.72),
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                '${_greeting()}, $name',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.w800,
                                  height: 1.1,
                                  color: Colors.white,
                                  letterSpacing: -0.6,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 16),
                        GestureDetector(
                          onTap: () => context.go('/profile'),
                          child: Container(
                            width: 48,
                            height: 48,
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.18),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.3),
                                width: 1.5,
                              ),
                            ),
                            child: Center(
                              child: Text(
                                _initials(
                                  ref.read(authProvider).customer?.firstName ??
                                      '',
                                  ref.read(authProvider).customer?.lastName ??
                                      '',
                                ),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const Spacer(),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.10),
                        ),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: _heroInsight(
                              label: 'Hali ya Mkopo',
                              value: switch (loan.portalState) {
                                'loan_active' => 'Unaendelea',
                                'released_pending_disbursement' =>
                                  'Inaandaliwa',
                                _ => 'Hakuna',
                              },
                              tone: switch (loan.portalState) {
                                'loan_active' => const Color(0xFF86EFAC),
                                'released_pending_disbursement' => const Color(
                                  0xFFFDE68A,
                                ),
                                _ => const Color(0xFFFDE68A),
                              },
                            ),
                          ),
                          Container(
                            width: 1,
                            height: 38,
                            color: Colors.white.withValues(alpha: 0.14),
                          ),
                          Expanded(
                            child: _heroInsight(
                              label: 'Tagline',
                              value: AppConstants.tagline,
                              tone: Colors.white.withValues(alpha: 0.85),
                              small: true,
                            ),
                          ),
                        ],
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

  String _todayFormatted() {
    final now = DateTime.now();
    final days = [
      'Jumatatu',
      'Jumanne',
      'Jumatano',
      'Alhamisi',
      'Ijumaa',
      'Jumamosi',
      'Jumapili',
    ];
    final months = [
      'Jan',
      'Feb',
      'Mac',
      'Apr',
      'Mei',
      'Jun',
      'Jul',
      'Ago',
      'Sep',
      'Okt',
      'Nov',
      'Des',
    ];
    return '${days[now.weekday - 1]}, ${now.day} ${months[now.month - 1]}';
  }

  String _initials(String first, String last) {
    final f = first.isNotEmpty ? first[0].toUpperCase() : '';
    final l = last.isNotEmpty ? last[0].toUpperCase() : '';
    return '$f$l';
  }

  Widget _heroInsight({
    required String label,
    required String value,
    required Color tone,
    bool small = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.65),
              fontSize: 11,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: tone,
              fontSize: small ? 12 : 16,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontSize: 18,
        fontWeight: FontWeight.w800,
        color: AppConstants.textPrimary,
        letterSpacing: -0.4,
      ),
    );
  }

  Widget _buildShimmer() {
    return Column(
      children: List.generate(
        2,
        (_) => Container(
          margin: const EdgeInsets.only(bottom: 14),
          height: 120,
          decoration: BoxDecoration(
            color: AppConstants.surface,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: AppConstants.border),
          ),
        ),
      ),
    );
  }

  Widget _buildNoLoanState() {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: AppConstants.primarySurface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppConstants.primary.withValues(alpha: 0.15)),
      ),
      child: Column(
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: AppConstants.primary.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(
              Icons.account_balance_wallet_outlined,
              size: 36,
              color: AppConstants.primary,
            ),
          ),
          const SizedBox(height: 18),
          const Text(
            'Hakuna Akaunti ya Mkopo',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Ukishapatiwa mkopo kwenye mfumo wa credit,\nmaelezo yake yataonekana hapa.',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13,
              color: AppConstants.textSecondary,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPendingDisbursementState(LoanState loan) {
    final release = loan.releaseContext;
    final repaymentLabel = switch (release?.preferredRepayment) {
      'weekly' => 'Kila wiki',
      'biweekly' => 'Kila baada ya wiki 2',
      'monthly' => 'Kila mwezi',
      _ => 'Imethibitishwa',
    };

    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF7ED),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppConstants.warning.withValues(alpha: 0.18)),
      ),
      child: Column(
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: AppConstants.warning.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(
              Icons.hourglass_top_rounded,
              size: 36,
              color: AppConstants.warning,
            ),
          ),
          const SizedBox(height: 18),
          const Text(
            'Mkopo Wako Unaandaliwa',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            loan.statusMessage ??
                'Kifaa kimeshatolewa. Mfumo wa credit unaandaa akaunti yako ya malipo.',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13,
              color: AppConstants.textSecondary,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            alignment: WrapAlignment.center,
            children: [
              _buildPendingChip(
                'Malipo',
                repaymentLabel,
                AppConstants.warning,
                const Color(0xFFFFF0D9),
              ),
              if (release?.assetReleasedAt != null)
                _buildPendingChip(
                  'Released',
                  release!.assetReleasedAt!.split(' ').first,
                  AppConstants.info,
                  const Color(0xFFEFF6FF),
                ),
              if ((release?.depositAmount ?? 0) > 0)
                _buildPendingChip(
                  'Amana',
                  'TZS ${_currencyFmt.format(release!.depositAmount)}',
                  AppConstants.success,
                  AppConstants.successSurface,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPendingChip(
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
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: color.withValues(alpha: 0.72),
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions(BuildContext context) {
    final actions = [
      _ActionData(
        label: 'Ratiba',
        caption: 'Angalia malipo',
        icon: Icons.calendar_month_rounded,
        color: const Color(0xFF2F80ED),
        surface: const Color(0xFFF1F7FF),
        onTap: () => context.go('/schedule'),
      ),
      _ActionData(
        label: 'Lipa Sasa',
        caption: 'Fanya malipo',
        icon: Icons.payments_rounded,
        color: AppConstants.success,
        surface: AppConstants.successSurface,
        onTap: () => context.go('/pay'),
      ),
      _ActionData(
        label: 'Kifaa',
        caption: 'Taarifa za simu',
        icon: Icons.phone_android_rounded,
        color: const Color(0xFF8B5CF6),
        surface: const Color(0xFFF7F3FF),
        onTap: () => context.go('/device'),
      ),
      _ActionData(
        label: 'Profaili',
        caption: 'Taarifa zako',
        icon: Icons.person_rounded,
        color: const Color(0xFFF59E0B),
        surface: const Color(0xFFFFF9ED),
        onTap: () => context.go('/profile'),
      ),
    ];

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 14,
      mainAxisSpacing: 14,
      childAspectRatio: 1.35,
      children: actions.map((a) => _buildActionCard(a)).toList(),
    );
  }

  Widget _buildActionCard(_ActionData action) {
    return GestureDetector(
      onTap: action.onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: action.surface,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: action.color.withValues(alpha: 0.14)),
          boxShadow: [
            BoxShadow(
              color: action.color.withValues(alpha: 0.08),
              blurRadius: 18,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: action.color.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(action.icon, color: action.color, size: 24),
            ),
            const Spacer(),
            Text(
              action.label,
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w700,
                color: action.color,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              action.caption,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w500,
                color: AppConstants.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ActionData {
  final String label;
  final String caption;
  final IconData icon;
  final Color color;
  final Color surface;
  final VoidCallback onTap;
  const _ActionData({
    required this.label,
    required this.caption,
    required this.icon,
    required this.color,
    required this.surface,
    required this.onTap,
  });
}

class _LoanProgressCard extends StatelessWidget {
  final LoanState loan;
  final AnimationController anim;
  const _LoanProgressCard({required this.loan, required this.anim});

  @override
  Widget build(BuildContext context) {
    final l = loan.loan!;
    final percent = (l.progressPercent / 100).clamp(0.0, 1.0);

    return AnimatedBuilder(
      animation: anim,
      builder: (_, _) => Opacity(
        opacity: anim.value.clamp(0.0, 1.0),
        child: Transform.translate(
          offset: Offset(0, 20 * (1 - anim.value)),
          child: Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: AppConstants.surface,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: AppConstants.border),
              boxShadow: [
                BoxShadow(
                  color: AppConstants.primary.withValues(alpha: 0.06),
                  blurRadius: 24,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: Column(
              children: [
                CircularPercentIndicator(
                  radius: 65,
                  lineWidth: 10,
                  percent: percent,
                  center: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        '${l.progressPercent.toStringAsFixed(0)}%',
                        style: const TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w800,
                          color: AppConstants.textPrimary,
                          letterSpacing: -0.5,
                        ),
                      ),
                      const Text(
                        'Umelipa',
                        style: TextStyle(
                          color: AppConstants.textSecondary,
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  progressColor: l.isOverdue
                      ? AppConstants.error
                      : AppConstants.success,
                  backgroundColor: AppConstants.border,
                  circularStrokeCap: CircularStrokeCap.round,
                  animation: true,
                  animationDuration: 1200,
                ),
                const SizedBox(height: 22),
                Row(
                  children: [
                    _AmountTile(
                      label: 'Umelipa',
                      amount: l.amountPaid,
                      color: AppConstants.success,
                      bg: AppConstants.successSurface,
                    ),
                    const SizedBox(width: 10),
                    _AmountTile(
                      label: 'Imebaki',
                      amount: l.remainingBalance,
                      color: AppConstants.warning,
                      bg: AppConstants.warningSurface,
                    ),
                    const SizedBox(width: 10),
                    _AmountTile(
                      label: 'Jumla',
                      amount: l.totalPayable,
                      color: AppConstants.primary,
                      bg: AppConstants.primarySurface,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _AmountTile extends StatelessWidget {
  final String label;
  final double amount;
  final Color color;
  final Color bg;
  const _AmountTile({
    required this.label,
    required this.amount,
    required this.color,
    required this.bg,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 6),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(
          children: [
            Text(
              label,
              style: TextStyle(
                color: color.withValues(alpha: 0.7),
                fontSize: 10,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 4),
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                'TZS ${_currencyFmt.format(amount)}',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 12,
                  color: color,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NextPaymentCard extends StatelessWidget {
  final LoanState loan;
  const _NextPaymentCard({required this.loan});

  @override
  Widget build(BuildContext context) {
    final next = loan.loan?.nextInstallment;
    if (next == null) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: AppConstants.successSurface,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(
            color: AppConstants.success.withValues(alpha: 0.2),
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: AppConstants.success.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(
                Icons.check_circle_rounded,
                color: AppConstants.success,
                size: 28,
              ),
            ),
            const SizedBox(width: 14),
            const Expanded(
              child: Text(
                'Malipo yote yamekamilika!',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                  color: AppConstants.success,
                ),
              ),
            ),
          ],
        ),
      );
    }

    final isOverdue = next.status == 'overdue';
    final accent = isOverdue ? AppConstants.error : AppConstants.primary;
    final bg = isOverdue
        ? AppConstants.errorSurface
        : AppConstants.primarySurface;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: accent.withValues(alpha: 0.18)),
        boxShadow: [
          BoxShadow(
            color: accent.withValues(alpha: 0.06),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  isOverdue
                      ? Icons.warning_rounded
                      : Icons.calendar_today_rounded,
                  color: accent,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                isOverdue ? 'Malipo Yamechelewa!' : 'Malipo Yajayo',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: accent,
                  fontSize: 16,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Kiasi',
                    style: TextStyle(
                      color: AppConstants.textSecondary,
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  Text(
                    'TZS ${_currencyFmt.format(next.amountDue)}',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: accent,
                      letterSpacing: -0.5,
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text(
                    'Tarehe',
                    style: TextStyle(
                      color: AppConstants.textSecondary,
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  Text(
                    next.dueDate ?? '-',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: isOverdue
                          ? AppConstants.error
                          : AppConstants.textPrimary,
                    ),
                  ),
                  if (isOverdue && next.daysOverdue > 0)
                    Text(
                      'Siku ${next.daysOverdue} zimepita',
                      style: const TextStyle(
                        color: AppConstants.error,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton.icon(
              onPressed: () => context.go('/pay'),
              icon: const Icon(Icons.payments_rounded, size: 20),
              label: const Text(
                'Lipa Sasa',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: accent,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                elevation: 0,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LoanDetailsCard extends StatelessWidget {
  final LoanState loan;
  const _LoanDetailsCard({required this.loan});

  @override
  Widget build(BuildContext context) {
    final l = loan.loan!;
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppConstants.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppConstants.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _DetailRow('Namba ya Mkopo', l.loanNumber),
          _DetailRow(
            'Aina ya Riba',
            l.interestType == 'flat' ? 'Sawa (Flat)' : 'Inashuka (Reducing)',
          ),
          _DetailRow('Riba', '${l.interestRate}%'),
          _DetailRow('Muda', '${l.durationWeeks} wiki'),
          _DetailRow(
            'Malipo',
            l.repaymentFrequency == 'weekly'
                ? 'Kila Wiki'
                : l.repaymentFrequency == 'biweekly'
                ? 'Kila Wiki 2'
                : 'Kila Mwezi',
          ),
          _DetailRow('Imetolewa', l.disbursedAt ?? '-'),
          _DetailRow(
            'Installments',
            '${l.paidInstallments} / ${l.totalInstallments}',
          ),
          if (l.penaltyAmount > 0)
            _DetailRow(
              'Adhabu',
              'TZS ${_currencyFmt.format(l.penaltyAmount)}',
              valueColor: AppConstants.error,
            ),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;
  const _DetailRow(this.label, this.value, {this.valueColor});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: const TextStyle(
              color: AppConstants.textSecondary,
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.w700,
              fontSize: 13,
              color: valueColor ?? AppConstants.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorBanner({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppConstants.errorSurface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: AppConstants.error.withValues(alpha: 0.2)),
      ),
      child: Column(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: AppConstants.error.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(18),
            ),
            child: const Icon(
              Icons.wifi_off_rounded,
              size: 28,
              color: AppConstants.error,
            ),
          ),
          const SizedBox(height: 14),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppConstants.textPrimary,
              fontSize: 14,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 40,
            child: ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('Jaribu Tena'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppConstants.error,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                elevation: 0,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
