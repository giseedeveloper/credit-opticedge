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

class _HomeScreenState extends ConsumerState<HomeScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(loanProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authProvider);
    final loan = ref.watch(loanProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppConstants.appName),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.read(loanProvider.notifier).load(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(loanProvider.notifier).load(),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Greeting
            Text(
              'Habari, ${auth.customer?.firstName ?? ''}!',
              style: Theme.of(
                context,
              ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 4),
            Text(
              'Mtililiko wa mkopo wako',
              style: TextStyle(color: AppConstants.textSecondary, fontSize: 14),
            ),
            const SizedBox(height: 20),

            // Loan Summary Card
            if (loan.isLoading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(40),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (loan.error != null)
              _ErrorCard(
                message: loan.error!,
                onRetry: () => ref.read(loanProvider.notifier).load(),
              )
            else if (loan.loan == null)
              _EmptyCard()
            else ...[
              _LoanProgressCard(loan: loan),
              const SizedBox(height: 16),
              _NextPaymentCard(loan: loan),
              const SizedBox(height: 16),
              _QuickActions(context: context),
              const SizedBox(height: 16),
              _LoanDetailsCard(loan: loan),
            ],
          ],
        ),
      ),
    );
  }
}

class _LoanProgressCard extends StatelessWidget {
  final LoanState loan;
  const _LoanProgressCard({required this.loan});

  @override
  Widget build(BuildContext context) {
    final l = loan.loan!;
    final percent = (l.progressPercent / 100).clamp(0.0, 1.0);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            CircularPercentIndicator(
              radius: 70,
              lineWidth: 10,
              percent: percent,
              center: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    '${l.progressPercent.toStringAsFixed(0)}%',
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    'Umelipa',
                    style: TextStyle(color: Colors.grey[600], fontSize: 12),
                  ),
                ],
              ),
              progressColor: l.isOverdue
                  ? AppConstants.error
                  : AppConstants.success,
              backgroundColor: Colors.grey.shade200,
              circularStrokeCap: CircularStrokeCap.round,
              animation: true,
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                _AmountTile(
                  label: 'Umelipa',
                  amount: l.amountPaid,
                  color: AppConstants.success,
                ),
                _AmountTile(
                  label: 'Imebaki',
                  amount: l.remainingBalance,
                  color: AppConstants.warning,
                ),
                _AmountTile(
                  label: 'Jumla',
                  amount: l.totalPayable,
                  color: AppConstants.primary,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _AmountTile extends StatelessWidget {
  final String label;
  final double amount;
  final Color color;
  const _AmountTile({
    required this.label,
    required this.amount,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(label, style: TextStyle(color: Colors.grey[600], fontSize: 11)),
          const SizedBox(height: 4),
          Text(
            'TZS ${_currencyFmt.format(amount)}',
            style: TextStyle(
              fontWeight: FontWeight.w700,
              fontSize: 13,
              color: color,
            ),
          ),
        ],
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
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              const Icon(
                Icons.check_circle,
                color: AppConstants.success,
                size: 32,
              ),
              const SizedBox(width: 12),
              Text(
                'Malipo yote yamekamilika!',
                style: Theme.of(
                  context,
                ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      );
    }

    final isOverdue = next.status == 'overdue';
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  isOverdue
                      ? Icons.warning_rounded
                      : Icons.calendar_today_rounded,
                  color: isOverdue ? AppConstants.error : AppConstants.primary,
                ),
                const SizedBox(width: 8),
                Text(
                  isOverdue ? 'Malipo Yamechelewa!' : 'Malipo Yajayo',
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: isOverdue
                        ? AppConstants.error
                        : AppConstants.primary,
                    fontSize: 16,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Kiasi',
                      style: TextStyle(color: Colors.grey[600], fontSize: 12),
                    ),
                    Text(
                      'TZS ${_currencyFmt.format(next.amountDue)}',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      'Tarehe',
                      style: TextStyle(color: Colors.grey[600], fontSize: 12),
                    ),
                    Text(
                      next.dueDate ?? '-',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: isOverdue ? AppConstants.error : null,
                      ),
                    ),
                    if (isOverdue && next.daysOverdue > 0)
                      Text(
                        'Siku ${next.daysOverdue} zimepita',
                        style: const TextStyle(
                          color: AppConstants.error,
                          fontSize: 11,
                        ),
                      ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => context.go('/pay'),
                icon: const Icon(Icons.payment_rounded),
                label: const Text('Lipa Sasa'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickActions extends StatelessWidget {
  final BuildContext context;
  const _QuickActions({required this.context});

  @override
  Widget build(BuildContext ctx) {
    return Row(
      children: [
        _ActionButton(
          icon: Icons.calendar_month_rounded,
          label: 'Ratiba',
          onTap: () => context.go('/schedule'),
        ),
        const SizedBox(width: 12),
        _ActionButton(
          icon: Icons.phone_android_rounded,
          label: 'Kifaa',
          onTap: () => context.go('/device'),
        ),
        const SizedBox(width: 12),
        _ActionButton(
          icon: Icons.person_rounded,
          label: 'Profaili',
          onTap: () => context.go('/profile'),
        ),
      ],
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  const _ActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Card(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 16),
            child: Column(
              children: [
                Icon(icon, color: AppConstants.primary, size: 28),
                const SizedBox(height: 4),
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ),
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
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Maelezo ya Mkopo',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
            ),
            const Divider(height: 20),
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
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Colors.grey[600], fontSize: 13)),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 13,
              color: valueColor,
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorCard extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorCard({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const Icon(
              Icons.error_outline,
              size: 48,
              color: AppConstants.error,
            ),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            TextButton(onPressed: onRetry, child: const Text('Jaribu Tena')),
          ],
        ),
      ),
    );
  }
}

class _EmptyCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          children: [
            const Icon(
              Icons.info_outline,
              size: 48,
              color: AppConstants.primary,
            ),
            const SizedBox(height: 12),
            Text(
              'Hakuna mkopo wa sasa',
              style: Theme.of(context).textTheme.titleMedium,
            ),
          ],
        ),
      ),
    );
  }
}
