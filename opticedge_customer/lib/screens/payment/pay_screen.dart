import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/constants.dart';
import '../../config/customer_colors.dart';
import '../../core/providers/loan_provider.dart';
import '../../core/providers/payment_provider.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

final _currencyFmt = NumberFormat('#,##0', 'en');

class PayScreen extends ConsumerStatefulWidget {
  const PayScreen({super.key});

  @override
  ConsumerState<PayScreen> createState() => _PayScreenState();
}

class _PayScreenState extends ConsumerState<PayScreen> {
  final _amountCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  double? _sliderAmount;
  bool _syncingAmount = false;

  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(loanProvider.notifier).load();
    });

    _amountCtrl.addListener(() {
      if (_syncingAmount) {
        return;
      }

      final raw = _amountCtrl.text.trim();
      final parsed = double.tryParse(raw);
      if (parsed == null) {
        return;
      }

      setState(() {
        _sliderAmount = parsed;
      });
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
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: const Text(
          'Lipa Mkopo',
          style: TextStyle(fontWeight: FontWeight.w800, letterSpacing: -0.4),
        ),
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
      ),
      body: PremiumGlassBackground(
        child: SingleChildScrollView(
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
              Builder(builder: (context) {
                final remaining = loan.loan!.remainingBalance;
                final minPay = 1000.0;
                final maxPay = remaining >= minPay ? remaining : minPay;

                _sliderAmount ??= (nextAmount != null && nextAmount >= minPay)
                    ? nextAmount.clamp(minPay, maxPay).toDouble()
                    : minPay;

                final fraction = maxPay <= 0 ? 0.0 : (_sliderAmount! / maxPay);
                final accent = Color.lerp(
                      AppConstants.warning,
                      AppConstants.success,
                      fraction.clamp(0.0, 1.0),
                    ) ??
                    AppConstants.primary;

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
              GlassCard.tinted(
                surfaceTint: CustomerColors.of(context).primarySurface,
                accent: AppConstants.primary,
                borderRadius: BorderRadius.circular(26),
                padding: const EdgeInsets.all(24),
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
                        color: CustomerColors.of(context).textSecondary,
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
                          color: CustomerColors.of(context).glassInputFill,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: AppConstants.border.withValues(alpha: 0.55),
                          ),
                        ),
                        child: Text(
                          'Malipo yajayo: TZS ${_currencyFmt.format(nextAmount)}',
                          style: TextStyle(
                            color: CustomerColors.of(context).textSecondary,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 18),
                    Container(
                      padding: const EdgeInsets.fromLTRB(14, 12, 14, 10),
                      decoration: BoxDecoration(
                        color: CustomerColors.of(context).glassInputFill,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                          color: AppConstants.border.withValues(alpha: 0.55),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'Chagua kiasi cha kulipa',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: CustomerColors.of(context).textSecondary,
                                  ),
                                ),
                              ),
                              Text(
                                'TZS ${_currencyFmt.format(_sliderAmount)}',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w900,
                                  color: accent,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          SliderTheme(
                            data: SliderTheme.of(context).copyWith(
                              activeTrackColor: accent,
                              inactiveTrackColor: accent.withValues(alpha: 0.18),
                              thumbColor: accent,
                              overlayColor: accent.withValues(alpha: 0.12),
                              trackHeight: 4,
                            ),
                            child: Slider(
                              min: minPay,
                              max: maxPay,
                              value: _sliderAmount!.clamp(minPay, maxPay),
                              divisions: 20,
                              onChanged: (v) {
                                setState(() {
                                  _sliderAmount = v;
                                });
                                _syncingAmount = true;
                                _amountCtrl.text = v.round().toString();
                                _syncingAmount = false;
                              },
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
                  ],
                );
              }),

              Text(
                'Kiasi cha Kulipa (TZS)',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  color: CustomerColors.of(context).textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _amountCtrl,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                onChanged: (value) {
                  final parsed = double.tryParse(value.trim());
                  if (parsed == null) {
                    return;
                  }
                  setState(() {
                    _sliderAmount = parsed;
                  });
                },
                decoration: InputDecoration(
                  hintText: 'Mfano: 15000',
                  prefixIcon: const Icon(
                    Icons.payments_rounded,
                    color: AppConstants.primary,
                  ),
                  filled: true,
                  fillColor: CustomerColors.of(context).glassInputFill,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide(
                      color: CustomerColors.of(context).border,
                    ),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide(
                      color: CustomerColors.of(context).border,
                    ),
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
              Text(
                'Namba ya M-Pesa (hiari)',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  color: CustomerColors.of(context).textPrimary,
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
                    color: AppConstants.primary,
                  ),
                  filled: true,
                  fillColor: CustomerColors.of(context).glassInputFill,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide(
                      color: CustomerColors.of(context).border,
                    ),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide(
                      color: CustomerColors.of(context).border,
                    ),
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
                      color: CustomerColors.of(context).errorSurface,
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
      ),
    );
  }

  Widget _buildPortalError(String message) {
    return GlassCard.tinted(
      surfaceTint: CustomerColors.of(context).errorSurface,
      accent: AppConstants.error,
      borderRadius: BorderRadius.circular(22),
      padding: const EdgeInsets.all(18),
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
    return GlassCard.tinted(
      surfaceTint: CustomerColors.of(context).primarySurface,
      accent: AppConstants.primary,
      borderRadius: BorderRadius.circular(26),
      padding: const EdgeInsets.all(24),
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
          Text(
            'Malipo Hayajafunguliwa',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: CustomerColors.of(context).textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            message ??
                'Ukishapatiwa akaunti ya mkopo kwenye mfumo wa credit, utaweza kutuma malipo hapa.',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: CustomerColors.of(context).textSecondary,
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
                'Akaunti ya Malipo Inaandaliwa',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: CustomerColors.of(context).textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                loan.statusMessage ??
                    'Kifaa kimeshatolewa. Subiri mfumo wa credit ukamilishe akaunti yako kabla ya kutuma malipo.',
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
                    CustomerColors.of(context).warningSurface,
                  ),
                  if ((release?.depositAmount ?? 0) > 0)
                    _buildContextChip(
                      'Amana',
                      'TZS ${_currencyFmt.format(release!.depositAmount)}',
                      AppConstants.success,
                      CustomerColors.of(context).successSurface,
                    ),
                  if (release?.assetReleasedAt != null)
                    _buildContextChip(
                      'Released',
                      release!.assetReleasedAt!.split(' ').first,
                      AppConstants.info,
                      CustomerColors.of(context).primarySurface,
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
                  'Ukishaanza kuona salio na installment inayofuata kwenye app, hapo ndipo malipo yatakuwa yamefunguliwa.',
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
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => controller.text = amount.toStringAsFixed(0),
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: CustomerColors.of(context).glassInputFill,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: AppConstants.primary.withValues(alpha: 0.28),
            ),
            boxShadow: [
              BoxShadow(
                color: AppConstants.primary.withValues(alpha: 0.06),
                blurRadius: 12,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Text(
            '$label: ${_currencyFmt.format(amount)}',
            style: TextStyle(
              color: CustomerColors.of(context).isDark
                  ? AppConstants.primaryLight
                  : AppConstants.primaryDark,
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ),
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
              style: TextStyle(color: CustomerColors.of(context).textSecondary),
            ),
          ] else ...[
            Text(
              payment.statusMessage ?? '',
              style: TextStyle(color: CustomerColors.of(context).textSecondary),
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
