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
    final phone = _phoneCtrl.text.trim().isNotEmpty ? _phoneCtrl.text.trim() : null;
    final success = await ref.read(paymentProvider.notifier).requestPayment(
          amount: amount,
          phone: phone,
        );
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

    return Scaffold(
      appBar: AppBar(title: const Text('Lipa Mkopo')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Quick summary
            if (loan.loan != null)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Text(
                        'Deni Lililobaki',
                        style: TextStyle(color: Colors.grey[600], fontSize: 13),
                      ),
                      Text(
                        'TZS ${_currencyFmt.format(loan.loan!.remainingBalance)}',
                        style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: AppConstants.primary),
                      ),
                      if (nextAmount != null) ...[
                        const Divider(height: 20),
                        Text(
                          'Malipo yajayo: TZS ${_currencyFmt.format(nextAmount)}',
                          style: TextStyle(color: Colors.grey[700], fontSize: 13),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 24),

            // Amount
            Text('Kiasi cha Kulipa (TZS)', style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            TextFormField(
              controller: _amountCtrl,
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: const InputDecoration(
                hintText: 'Mfano: 15000',
                prefixIcon: Icon(Icons.money_rounded),
              ),
            ),
            const SizedBox(height: 8),

            // Quick amount buttons
            if (nextAmount != null)
              Wrap(
                spacing: 8,
                children: [
                  _QuickAmountChip(label: 'Installment', amount: nextAmount, controller: _amountCtrl),
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
            Text('Namba ya M-Pesa (hiari)', style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            TextFormField(
              controller: _phoneCtrl,
              keyboardType: TextInputType.phone,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: const InputDecoration(
                hintText: 'Acha tupu kutumia namba yako',
                prefixIcon: Icon(Icons.phone_rounded),
              ),
            ),
            const SizedBox(height: 24),

            // Error
            if (payment.error != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppConstants.danger.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(payment.error!, style: const TextStyle(color: AppConstants.danger, fontSize: 13)),
                ),
              ),

            // Submit
            ElevatedButton.icon(
              onPressed: payment.isRequesting ? null : _submit,
              icon: payment.isRequesting
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.send_rounded),
              label: Text(payment.isRequesting ? 'Inatuma...' : 'Tuma Malipo'),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickAmountChip extends StatelessWidget {
  final String label;
  final double amount;
  final TextEditingController controller;
  const _QuickAmountChip({required this.label, required this.amount, required this.controller});

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
            const Icon(Icons.check_circle, color: AppConstants.accent, size: 64),
            const SizedBox(height: 16),
            const Text('Malipo Yamefanikiwa!',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppConstants.accent)),
            const SizedBox(height: 8),
            Text('TZS ${_currencyFmt.format(payment.amount ?? 0)}',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
          ] else if (payment.isPolling) ...[
            const CircularProgressIndicator(),
            const SizedBox(height: 16),
            const Text('Inasubiri uthibitisho...', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500)),
            const SizedBox(height: 8),
            Text('Angalia simu yako na uthibitishe malipo', style: TextStyle(color: Colors.grey[600])),
          ] else ...[
            Text(payment.statusMessage ?? '', style: TextStyle(color: Colors.grey[600])),
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
