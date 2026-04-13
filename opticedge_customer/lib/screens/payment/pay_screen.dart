import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/constants.dart';
import '../../core/providers/loan_provider.dart';
import '../../core/providers/payment_provider.dart';

final _currencyFmt = NumberFormat('#,##0', 'en');

class PayScreen extends ConsumerStatefulWidget {
  const PayScreen({super.key});

  @override
  ConsumerState<PayScreen> createState() => _PayScreenState();
}

class _PayScreenState extends ConsumerState<PayScreen> {
  final _amountCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(loanProvider.notifier).load();
    });
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final amount = double.tryParse(_amountCtrl.text.trim());
    if (amount == null || amount < 1000) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Weka kiasi sahihi (angalau TZS 1,000)')),
      );
      return;
    }
    FocusScope.of(context).unfocus();
    final phone = _phoneCtrl.text.trim().isNotEmpty
        ? _phoneCtrl.text.trim()
        : null;
    final success = await ref
        .read(paymentProvider.notifier)
        .requestPayment(amount: amount, phone: phone);
    if (success && mounted) {
      _showStatusSheet();
    }
  }

  void _showStatusSheet() {
    showModalBottomSheet(
      context: context,
      isDismissible: false,
      enableDrag: false,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => const _PaymentStatusSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final loan = ref.watch(loanProvider);
    final payment = ref.watch(paymentProvider);
    final nextAmount = loan.loan?.nextInstallment?.amountDue;
    final isPendingDisbursement =
        loan.portalState == 'released_pending_disbursement';
    final canSubmitPayment =
        !payment.isRequesting && !isPendingDisbursement && loan.loan != null;

    return Scaffold(
      backgroundColor: AppConstants.background,
      appBar: AppBar(
        title: const Text(
          'Lipa Mkopo',
          style: TextStyle(fontWeight: FontWeight.w800, letterSpacing: -0.4),
        ),
        backgroundColor: AppConstants.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (loan.isLoading)
              const Padding(
                padding: EdgeInsets.only(top: 120),
                child: Center(
                  child: CircularProgressIndicator(color: AppConstants.primary),
                ),
              )
            else if (loan.error != null)
              _buildPortalError(loan.error!)
            else if (isPendingDisbursement)
              _buildPendingDisbursementState(loan)
            else if (loan.loan == null)
              _buildUnavailableState(loan.statusMessage)
            else ...[
              // Quick summary
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppConstants.primarySurface,
                  borderRadius: BorderRadius.circular(22),
                  border: Border.all(
                    color: AppConstants.primary.withValues(alpha: 0.14),
                  ),
                ),
                child: Column(
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: AppConstants.primary.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: const Icon(
                        Icons.account_balance_wallet_rounded,
                        color: AppConstants.primary,
                        size: 26,
                      ),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      'Deni Lililobaki',
                      style: TextStyle(
                        color: AppConstants.textSecondary,
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'TZS ${_currencyFmt.format(loan.loan!.remainingBalance)}',
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w800,
                        color: AppConstants.primary,
                        letterSpacing: -0.6,
                      ),
                    ),
                    if (nextAmount != null) ...[
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: AppConstants.surface,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          'Malipo yajayo: TZS ${_currencyFmt.format(nextAmount)}',
                          style: const TextStyle(
                            color: AppConstants.textSecondary,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Amount
              const Text(
                'Kiasi cha Kulipa (TZS)',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  color: AppConstants.textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _amountCtrl,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: InputDecoration(
                  hintText: 'Mfano: 15000',
                  prefixIcon: const Icon(
                    Icons.payments_rounded,
                    color: AppConstants.primary,
                  ),
                  filled: true,
                  fillColor: AppConstants.surface,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: const BorderSide(color: AppConstants.border),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: const BorderSide(color: AppConstants.border),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: const BorderSide(
                      color: AppConstants.primary,
                      width: 1.5,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 10),

              // Quick amount buttons
              if (nextAmount != null)
                Wrap(
                  spacing: 8,
                  children: [
                    _QuickAmountChip(
                      label: 'Installment',
                      amount: nextAmount,
                      controller: _amountCtrl,
                    ),
                    if (loan.loan!.remainingBalance != nextAmount)
                      _QuickAmountChip(
                        label: 'Yote',
                        amount: loan.loan!.remainingBalance,
                        controller: _amountCtrl,
                      ),
                  ],
                ),
              const SizedBox(height: 20),

              // Phone (optional)
              const Text(
                'Namba ya M-Pesa (hiari)',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  color: AppConstants.textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _phoneCtrl,
                keyboardType: TextInputType.phone,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: InputDecoration(
                  hintText: 'Acha tupu kutumia namba yako',
                  prefixIcon: const Icon(
                    Icons.phone_rounded,
                    color: Color(0xFF8B5CF6),
                  ),
                  filled: true,
                  fillColor: AppConstants.surface,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: const BorderSide(color: AppConstants.border),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: const BorderSide(color: AppConstants.border),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: const BorderSide(
                      color: AppConstants.primary,
                      width: 1.5,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Error
              if (payment.error != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppConstants.errorSurface,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: AppConstants.error.withValues(alpha: 0.2),
                      ),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.error_outline,
                          color: AppConstants.error,
                          size: 18,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            payment.error!,
                            style: const TextStyle(
                              color: AppConstants.error,
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

              // Submit
              SizedBox(
                height: 52,
                child: ElevatedButton.icon(
                  onPressed: canSubmitPayment ? _submit : null,
                  icon: payment.isRequesting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send_rounded, size: 20),
                  label: Text(
                    payment.isRequesting ? 'Inatuma...' : 'Tuma Malipo',
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 16,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppConstants.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    elevation: 0,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildPortalError(String message) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppConstants.errorSurface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppConstants.error.withValues(alpha: 0.18)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline_rounded, color: AppConstants.error),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: AppConstants.error,
                fontSize: 13,
                fontWeight: FontWeight.w600,
                height: 1.5,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildUnavailableState(String? message) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: AppConstants.primarySurface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppConstants.primary.withValues(alpha: 0.15)),
      ),
      child: Column(
        children: [
          Container(
            width: 68,
            height: 68,
            decoration: BoxDecoration(
              color: AppConstants.primary.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(
              Icons.account_balance_wallet_outlined,
              color: AppConstants.primary,
              size: 34,
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'Malipo Hayajafunguliwa',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            message ??
                'Ukishapatiwa akaunti ya mkopo kwenye mfumo wa credit, utaweza kutuma malipo hapa.',
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppConstants.textSecondary,
              fontSize: 13,
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
      _ => 'Inathibitishwa',
    };

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: AppConstants.warningSurface,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(
              color: AppConstants.warning.withValues(alpha: 0.16),
            ),
          ),
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
              const Text(
                'Akaunti ya Malipo Inaandaliwa',
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
                    'Kifaa kimeshatolewa. Subiri mfumo wa credit ukamilishe akaunti yako kabla ya kutuma malipo.',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppConstants.textSecondary,
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
                    const Color(0xFFFFF0D9),
                  ),
                  if ((release?.depositAmount ?? 0) > 0)
                    _buildContextChip(
                      'Amana',
                      'TZS ${_currencyFmt.format(release!.depositAmount)}',
                      AppConstants.success,
                      AppConstants.successSurface,
                    ),
                  if (release?.assetReleasedAt != null)
                    _buildContextChip(
                      'Released',
                      release!.assetReleasedAt!.split(' ').first,
                      AppConstants.info,
                      const Color(0xFFEFF6FF),
                    ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: AppConstants.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppConstants.border),
          ),
          child: const Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                Icons.info_outline_rounded,
                color: AppConstants.info,
                size: 20,
              ),
              SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Ukishaanza kuona salio na installment inayofuata kwenye app, hapo ndipo malipo yatakuwa yamefunguliwa.',
                  style: TextStyle(
                    color: AppConstants.textSecondary,
                    fontSize: 13,
                    height: 1.5,
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
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
}

class _QuickAmountChip extends StatelessWidget {
  final String label;
  final double amount;
  final TextEditingController controller;
  const _QuickAmountChip({
    required this.label,
    required this.amount,
    required this.controller,
  });

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      label: Text('$label: ${_currencyFmt.format(amount)}'),
      onPressed: () => controller.text = amount.toStringAsFixed(0),
    );
  }
}

class _PaymentStatusSheet extends ConsumerWidget {
  const _PaymentStatusSheet();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final payment = ref.watch(paymentProvider);

    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (payment.isCompleted) ...[
            const Icon(
              Icons.check_circle,
              color: AppConstants.success,
              size: 64,
            ),
            const SizedBox(height: 16),
            const Text(
              'Malipo Yamefanikiwa!',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: AppConstants.success,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'TZS ${_currencyFmt.format(payment.amount ?? 0)}',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
          ] else if (payment.isPolling) ...[
            const CircularProgressIndicator(),
            const SizedBox(height: 16),
            const Text(
              'Inasubiri uthibitisho...',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: 8),
            Text(
              'Angalia simu yako na uthibitishe malipo',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ] else ...[
            Text(
              payment.statusMessage ?? '',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
              onPressed: () {
                ref.read(paymentProvider.notifier).reset();
                ref.read(loanProvider.notifier).load();
                Navigator.pop(context);
              },
              child: Text(payment.isCompleted ? 'Rudi Nyumbani' : 'Funga'),
            ),
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}
