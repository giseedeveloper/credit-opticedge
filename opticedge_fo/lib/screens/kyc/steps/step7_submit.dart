import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../config/constants.dart';
import '../../../core/models/kyc_flow_model.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';
import '../../../widgets/common/photo_picker_tile.dart';
import '../../../widgets/kyc/phone_number_field.dart';
import '../../../widgets/kyc/signature_pad.dart';

class Step7SubmitScreen extends ConsumerStatefulWidget {
  const Step7SubmitScreen({super.key});

  @override
  ConsumerState<Step7SubmitScreen> createState() => _Step7State();
}

class _Step7State extends ConsumerState<Step7SubmitScreen>
    with SingleTickerProviderStateMixin {
  final _notesCtrl = TextEditingController();
  final _paymentPhoneCtrl = TextEditingController();
  final _handoverNotesCtrl = TextEditingController();
  final _customerSignatureController = SignaturePadController();
  final _foSignatureController = SignaturePadController();

  bool _submitted = false;
  Map<String, dynamic>? _result;
  bool _loadedContext = false;

  late final AnimationController _successAnim;
  late final Animation<double> _ringScale;
  late final Animation<double> _ringOpacity;

  final _sourceOptions = const [
    ('walk_in', 'Walk In', Icons.storefront_outlined),
    ('referral', 'Referral', Icons.share_outlined),
    ('vendor', 'Vendor', Icons.handshake_outlined),
    ('social_media', 'Social Media', Icons.campaign_outlined),
    ('agent', 'Agent', Icons.support_agent_outlined),
  ];

  @override
  void initState() {
    super.initState();
    final state = ref.read(kycProvider);
    _notesCtrl.text = state.foNotes;
    _paymentPhoneCtrl.text =
        state.paymentPhone.isNotEmpty ? state.paymentPhone : state.phone;
    _handoverNotesCtrl.text = state.assetHandoverNotes;

    _successAnim = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
    _ringScale = Tween<double>(begin: 0.3, end: 1).animate(
      CurvedAnimation(parent: _successAnim, curve: Curves.elasticOut),
    );
    _ringOpacity = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _successAnim, curve: Curves.easeOut),
    );

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (_loadedContext) {
        return;
      }

      _loadedContext = true;
      await ref.read(kycProvider.notifier).loadFinalContext();
      if (!mounted) {
        return;
      }

      final nextState = ref.read(kycProvider);
      if (_paymentPhoneCtrl.text.trim().isEmpty &&
          nextState.paymentPhone.isNotEmpty) {
        _paymentPhoneCtrl.text = nextState.paymentPhone;
      }
    });
  }

  @override
  void dispose() {
    _notesCtrl.dispose();
    _paymentPhoneCtrl.dispose();
    _handoverNotesCtrl.dispose();
    _customerSignatureController.dispose();
    _foSignatureController.dispose();
    _successAnim.dispose();
    super.dispose();
  }

  void _syncControllers(KycDraftState state) {
    if (_notesCtrl.text != state.foNotes) {
      _notesCtrl.text = state.foNotes;
    }

    final paymentPhone =
        state.paymentPhone.isNotEmpty ? state.paymentPhone : state.phone;
    if (_paymentPhoneCtrl.text != paymentPhone &&
        _paymentPhoneCtrl.text.isEmpty) {
      _paymentPhoneCtrl.text = paymentPhone;
    }

    if (_handoverNotesCtrl.text != state.assetHandoverNotes) {
      _handoverNotesCtrl.text = state.assetHandoverNotes;
    }
  }

  Future<void> _requestPayment() async {
    ref.read(kycProvider.notifier).update(
          (state) => state.copyWith(
            paymentPhone: _paymentPhoneCtrl.text.trim(),
            foNotes: _notesCtrl.text.trim(),
            assetHandoverNotes: _handoverNotesCtrl.text.trim(),
          ),
        );
    await ref.read(kycProvider.notifier).requestPaymentPrompt();
  }

  Future<void> _refreshPayment() async {
    await ref.read(kycProvider.notifier).refreshPaymentStatus();
  }

  Future<bool> _prepareSignatures() async {
    final messenger = ScaffoldMessenger.of(context);
    final customerSignature =
        await _customerSignatureController.exportAsDataUrl();
    final foSignature = await _foSignatureController.exportAsDataUrl();

    ref.read(kycProvider.notifier).update(
          (state) => state.copyWith(
            foNotes: _notesCtrl.text.trim(),
            paymentPhone: _paymentPhoneCtrl.text.trim(),
            assetHandoverNotes: _handoverNotesCtrl.text.trim(),
            customerSignatureData:
                customerSignature ?? state.customerSignatureData,
            foSignatureData: foSignature ?? state.foSignatureData,
          ),
        );

    if ((customerSignature ?? ref.read(kycProvider).customerSignatureData)
            .trim()
            .isEmpty ||
        (foSignature ?? ref.read(kycProvider).foSignatureData).trim().isEmpty) {
      if (!context.mounted) {
        return false;
      }

      messenger.showSnackBar(
        const SnackBar(
          content: Text('Capture both customer and FO signatures first.'),
          backgroundColor: AppConstants.error,
        ),
      );

      return false;
    }

    return true;
  }

  Future<void> _submit() async {
    final state = ref.read(kycProvider);
    final paymentCompleted = state.paymentContext?.isCompleted == true;
    final agreementAccepted = state.agreementDecision == 'yes';

    if (!paymentCompleted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content:
              Text('Complete the deposit payment before final submission.'),
          backgroundColor: AppConstants.error,
        ),
      );
      return;
    }

    if (!agreementAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Record agreement acceptance before submitting.'),
          backgroundColor: AppConstants.error,
        ),
      );
      return;
    }

    if (state.assetHandoverList == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Attach the asset handover checklist first.'),
          backgroundColor: AppConstants.error,
        ),
      );
      return;
    }

    if (!await _prepareSignatures()) {
      return;
    }

    final result = await ref.read(kycProvider.notifier).submitStep7();
    if (result == null || !mounted) {
      return;
    }

    setState(() {
      _submitted = true;
      _result = result;
    });
    _successAnim.forward();
  }

  Future<void> _showAgreementDialog(KycAgreementContext agreement) async {
    final document = agreement.activeDocument;
    if (document == null) {
      return;
    }

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (context) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Customer Agreement',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppConstants.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              document.title,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: AppConstants.textPrimary,
              ),
            ),
            if ((document.originalName ?? '').isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                document.originalName!,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppConstants.textSecondary,
                ),
              ),
            ],
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppConstants.borderLight,
                borderRadius: BorderRadius.circular(14),
              ),
              child: SelectableText(
                document.url,
                style: const TextStyle(
                  fontSize: 12,
                  height: 1.45,
                  color: AppConstants.textSecondary,
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Text(
              'Current app build can copy and share the agreement link cleanly. For full in-app PDF preview we need a PDF viewer package added to the mobile app.',
              style: TextStyle(
                fontSize: 12,
                height: 1.5,
                color: AppConstants.textSecondary,
              ),
            ),
            const SizedBox(height: 16),
            AppButton(
              label: 'Copy Agreement Link',
              width: double.infinity,
              icon: Icons.copy_rounded,
              onPressed: () async {
                await Clipboard.setData(ClipboardData(text: document.url));
                if (!context.mounted) {
                  return;
                }
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Agreement link copied to clipboard.'),
                    backgroundColor: AppConstants.success,
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    final countriesAsync = ref.watch(phoneCountriesProvider);

    _syncControllers(state);

    if (_submitted) {
      return _buildSuccessView(context);
    }

    final paymentContext = state.paymentContext;
    final agreementContext = state.agreementContext;
    final releaseContext = state.releaseContext;
    final paymentReady = paymentContext?.isCompleted == true;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionHeader(
            'Payment, Agreement & Final Submit',
            'Finish the customer journey in one smooth flow: collect the deposit, present the agreement, capture signatures, then submit.',
          ),
          const SizedBox(height: 16),
          _foScriptCard(),
          const SizedBox(height: 16),
          _summaryCard(state),
          const SizedBox(height: 16),
          _card(
            title: '1. Collect the starting deposit',
            subtitle:
                'Send the wallet push only after confirming the amount with the customer.',
            child: Column(
              children: [
                countriesAsync.when(
                  loading: () => const LinearProgressIndicator(
                    color: AppConstants.primary,
                  ),
                  error: (_, __) => const Text(
                    'Failed to load phone countries',
                    style: TextStyle(color: AppConstants.error),
                  ),
                  data: (countries) => PhoneNumberField(
                    label: 'Payment Phone Number',
                    required: true,
                    controller: _paymentPhoneCtrl,
                    countries: countries,
                    selectedCountry: state.paymentPhoneCountry,
                    helperText:
                        'Use the number that will receive the Selcom wallet prompt right now.',
                    onCountryChanged: (value) {
                      if (value == null) {
                        return;
                      }

                      ref.read(kycProvider.notifier).update(
                            (current) => current.copyWith(
                              paymentPhoneCountry: value,
                            ),
                          );
                    },
                  ),
                ),
                const SizedBox(height: 14),
                _paymentStatusCard(paymentContext, state.depositAmount),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: AppButton(
                        label: paymentReady ? 'Payment Confirmed' : 'Send Push',
                        icon: paymentReady
                            ? Icons.check_circle_outline
                            : Icons.payment_rounded,
                        color: paymentReady
                            ? AppConstants.success
                            : AppConstants.primary,
                        isLoading: state.isSubmitting,
                        onPressed: paymentReady ? null : _requestPayment,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: AppButton(
                        label: 'Refresh Status',
                        outlined: true,
                        icon: Icons.refresh_rounded,
                        isLoading: false,
                        onPressed:
                            paymentContext == null ? null : _refreshPayment,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _card(
            title: '2. Present the agreement clearly',
            subtitle:
                'Once payment succeeds, show the customer the agreement and record the decision honestly.',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (agreementContext?.activeDocument == null)
                  const Text(
                    'No active agreement is uploaded by admin yet.',
                    style: TextStyle(
                      fontSize: 12,
                      color: AppConstants.error,
                    ),
                  )
                else
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppConstants.borderLight,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(
                            Icons.picture_as_pdf_outlined,
                            color: AppConstants.primary,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                agreementContext!.activeDocument!.title,
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: AppConstants.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                paymentReady
                                    ? 'Payment is already successful. You can now present the agreement to the customer.'
                                    : 'Complete payment first, then present this agreement.',
                                style: const TextStyle(
                                  fontSize: 12,
                                  height: 1.45,
                                  color: AppConstants.textSecondary,
                                ),
                              ),
                              const SizedBox(height: 10),
                              AppButton(
                                label: 'View Agreement Details',
                                outlined: true,
                                icon: Icons.visibility_outlined,
                                onPressed: () => _showAgreementDialog(
                                  agreementContext,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                const SizedBox(height: 14),
                _decisionTile(
                  selected: state.agreementDecision == 'yes',
                  title: 'Customer accepted the agreement',
                  subtitle:
                      'Use this after the customer has read or been clearly guided through the terms.',
                  icon: Icons.check_circle_outline,
                  color: AppConstants.success,
                  onTap: paymentReady
                      ? () {
                          ref.read(kycProvider.notifier).update(
                                (current) => current.copyWith(
                                  agreementDecision: 'yes',
                                ),
                              );
                        }
                      : null,
                ),
                const SizedBox(height: 10),
                _decisionTile(
                  selected: state.agreementDecision == 'no',
                  title: 'Customer did not accept',
                  subtitle:
                      'Use this only if the customer declines after the explanation.',
                  icon: Icons.cancel_outlined,
                  color: AppConstants.error,
                  onTap: paymentReady
                      ? () {
                          ref.read(kycProvider.notifier).update(
                                (current) => current.copyWith(
                                  agreementDecision: 'no',
                                ),
                              );
                        }
                      : null,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _card(
            title: '3. Capture signatures and handover proof',
            subtitle:
                'The customer signs first, then the field officer signs, then attach the handover checklist.',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _signatureCard(
                  title: 'Customer Signature',
                  subtitle:
                      'Ask the customer to sign after confirming they understand the agreement.',
                  controller: _customerSignatureController,
                  onClear: () {
                    _customerSignatureController.clear();
                    ref.read(kycProvider.notifier).clearSignature('customer');
                  },
                ),
                const SizedBox(height: 14),
                _signatureCard(
                  title: 'FO Signature',
                  subtitle:
                      'The field officer signs to confirm the explanation and document handover were completed.',
                  controller: _foSignatureController,
                  onClear: () {
                    _foSignatureController.clear();
                    ref.read(kycProvider.notifier).clearSignature('fo');
                  },
                ),
                const SizedBox(height: 14),
                const Text(
                  'Asset handover checklist',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppConstants.textPrimary,
                  ),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Current app build supports attaching the checklist as a clear photo from camera or gallery.',
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    SizedBox(
                      width: 132,
                      child: PhotoPickerTile(
                        label: 'Checklist',
                        file: state.assetHandoverList,
                        onPicked: (file) => ref
                            .read(kycProvider.notifier)
                            .setPhoto('handover', file),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _handoverNotesCtrl,
                        maxLines: 4,
                        onChanged: (value) {
                          ref.read(kycProvider.notifier).update(
                                (current) => current.copyWith(
                                  assetHandoverNotes: value.trim(),
                                ),
                              );
                        },
                        decoration: const InputDecoration(
                          labelText: 'Handover Notes',
                          hintText:
                              'List what was handed to the customer or note any special explanation given.',
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _card(
            title: '4. Final application review',
            subtitle:
                'These notes help reviewers understand the customer context without repeating the interview.',
            child: Column(
              children: [
                GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisSpacing: 10,
                  mainAxisSpacing: 10,
                  childAspectRatio: 2.45,
                  children: _sourceOptions.map((option) {
                    final selected = state.applicationSource == option.$1;

                    return GestureDetector(
                      onTap: () {
                        ref.read(kycProvider.notifier).update(
                              (current) => current.copyWith(
                                applicationSource: option.$1,
                              ),
                            );
                      },
                      child: AnimatedContainer(
                        duration: 220.ms,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 10,
                        ),
                        decoration: BoxDecoration(
                          color: selected
                              ? AppConstants.primarySurface
                              : AppConstants.borderLight,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: selected
                                ? AppConstants.primary
                                : AppConstants.border,
                            width: selected ? 1.4 : 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              option.$3,
                              size: 18,
                              color: selected
                                  ? AppConstants.primary
                                  : AppConstants.textSecondary,
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                option.$2,
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: selected
                                      ? FontWeight.w700
                                      : FontWeight.w500,
                                  color: selected
                                      ? AppConstants.primary
                                      : AppConstants.textSecondary,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _notesCtrl,
                  maxLines: 4,
                  onChanged: (value) {
                    ref.read(kycProvider.notifier).update(
                          (current) => current.copyWith(
                            foNotes: value.trim(),
                          ),
                        );
                  },
                  decoration: const InputDecoration(
                    labelText: 'FO Notes',
                    hintText:
                        'Mention any useful observation, customer concern, or clarification given during the visit.',
                  ),
                ),
                const SizedBox(height: 14),
                _readinessCard(
                  paymentContext: paymentContext,
                  agreementContext: agreementContext,
                  releaseContext: releaseContext,
                  state: state,
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          AppButton(
            label: 'Submit Application',
            width: double.infinity,
            icon: Icons.send_rounded,
            isLoading: state.isSubmitting,
            onPressed: _submit,
          ),
          const SizedBox(height: 12),
          AppButton(
            label: 'Save and Review Later',
            width: double.infinity,
            outlined: true,
            icon: Icons.save_outlined,
            onPressed: () => context.go('/customers'),
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _foScriptCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFFFFFBEB), Color(0xFFFFF7ED)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: AppConstants.primary.withValues(alpha: 0.12),
        ),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.forum_outlined, color: AppConstants.primary, size: 20),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              'Helpful script: “Kabla sijatuma maombi, nitakutumia ombi la malipo ya kuanzia, kisha nitakuonesha makubaliano ya simu hii, tukisaini wote nitawasilisha taarifa zako kwa ukamilifu.”',
              style: TextStyle(
                fontSize: 12,
                height: 1.55,
                color: AppConstants.textSecondary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _summaryCard(KycDraftState state) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppConstants.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Quick Application Snapshot',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          _summaryRow(
              'Customer', '${state.firstName} ${state.lastName}'.trim()),
          _summaryRow('Phone', state.phone),
          _summaryRow('Device', state.deviceSpecs),
          _summaryRow('IMEI', state.imeiNumber),
          _summaryRow('Deposit', 'TZS ${state.depositAmount}'),
          _summaryRow('Repayment', state.preferredRepayment),
        ],
      ),
    );
  }

  Widget _card({
    required String title,
    required String subtitle,
    required Widget child,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppConstants.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: const TextStyle(
              fontSize: 12,
              height: 1.45,
              color: AppConstants.textSecondary,
            ),
          ),
          const SizedBox(height: 16),
          child,
        ],
      ),
    );
  }

  Widget _paymentStatusCard(
    KycPaymentContext? paymentContext,
    String depositAmount,
  ) {
    final isComplete = paymentContext?.isCompleted == true;
    final hasPrompt = paymentContext != null;
    final color = isComplete
        ? AppConstants.success
        : hasPrompt
            ? AppConstants.warning
            : AppConstants.info;
    final background = isComplete
        ? const Color(0xFFF0FDF4)
        : hasPrompt
            ? const Color(0xFFFFFBEB)
            : const Color(0xFFEFF6FF);
    final label = isComplete
        ? 'Payment successful'
        : hasPrompt
            ? 'Waiting for customer payment'
            : 'Payment prompt not started';
    final pendingSummary = hasPrompt
        ? 'Prompt sent to ${paymentContext.phone ?? '-'}${paymentContext.reference != null ? ' • Ref ${paymentContext.reference}' : ''}.'
        : 'Deposit required before final submission.';

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.16)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            isComplete
                ? Icons.check_circle_outline
                : hasPrompt
                    ? Icons.hourglass_top_rounded
                    : Icons.phone_iphone_outlined,
            color: color,
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  isComplete
                      ? 'Deposit of TZS ${paymentContext?.amount ?? depositAmount} was confirmed successfully.'
                      : pendingSummary,
                  style: const TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _decisionTile({
    required bool selected,
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: AnimatedContainer(
        duration: 220.ms,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: selected
              ? color.withValues(alpha: 0.08)
              : AppConstants.borderLight,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected ? color : AppConstants.border,
            width: selected ? 1.4 : 1,
          ),
        ),
        child: Row(
          children: [
            Icon(icon, color: selected ? color : AppConstants.textSecondary),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppConstants.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      fontSize: 11,
                      height: 1.45,
                      color: AppConstants.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _signatureCard({
    required String title,
    required String subtitle,
    required SignaturePadController controller,
    required VoidCallback onClear,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: AppConstants.textPrimary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: const TextStyle(
            fontSize: 12,
            height: 1.45,
            color: AppConstants.textSecondary,
          ),
        ),
        const SizedBox(height: 10),
        SignaturePad(controller: controller),
        const SizedBox(height: 10),
        Align(
          alignment: Alignment.centerRight,
          child: TextButton.icon(
            onPressed: onClear,
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: const Text('Clear Signature'),
          ),
        ),
      ],
    );
  }

  Widget _readinessCard({
    required KycPaymentContext? paymentContext,
    required KycAgreementContext? agreementContext,
    required KycReleaseContext? releaseContext,
    required KycDraftState state,
  }) {
    final checks = [
      (
        label: 'Deposit payment confirmed',
        done: paymentContext?.isCompleted == true,
      ),
      (
        label: 'Agreement accepted',
        done: state.agreementDecision == 'yes',
      ),
      (
        label: 'Customer signature captured',
        done: state.customerSignatureData.isNotEmpty ||
            (agreementContext?.customerSignatureUrl?.isNotEmpty ?? false),
      ),
      (
        label: 'FO signature captured',
        done: state.foSignatureData.isNotEmpty ||
            (agreementContext?.foSignatureUrl?.isNotEmpty ?? false),
      ),
      (
        label: 'Handover checklist attached',
        done: state.assetHandoverList != null ||
            (agreementContext?.handoverListUrl?.isNotEmpty ?? false),
      ),
      (
        label: 'Asset release will unlock after approval',
        done: releaseContext?.status == 'released' ||
            releaseContext?.canReleaseAsset == true,
      ),
    ];

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppConstants.borderLight,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: checks
            .map(
              (check) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  children: [
                    Icon(
                      check.done
                          ? Icons.check_circle_rounded
                          : Icons.radio_button_unchecked_rounded,
                      size: 18,
                      color: check.done
                          ? AppConstants.success
                          : AppConstants.textHint,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        check.label,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight:
                              check.done ? FontWeight.w700 : FontWeight.w500,
                          color: check.done
                              ? AppConstants.textPrimary
                              : AppConstants.textSecondary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            )
            .toList(),
      ),
    );
  }

  Widget _buildSuccessView(BuildContext context) {
    final customerId = _result?['customer_id']?.toString();

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedBuilder(
              animation: _successAnim,
              builder: (context, _) {
                return Opacity(
                  opacity: _ringOpacity.value,
                  child: Transform.scale(
                    scale: _ringScale.value,
                    child: Container(
                      width: 108,
                      height: 108,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF0FDF4),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: AppConstants.success.withValues(alpha: 0.22),
                          width: 6,
                        ),
                      ),
                      child: const Icon(
                        Icons.check_rounded,
                        size: 56,
                        color: AppConstants.success,
                      ),
                    ),
                  ),
                );
              },
            ),
            const SizedBox(height: 24),
            const Text(
              'Application Submitted Successfully',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: AppConstants.textPrimary,
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              'The customer file has moved to review with payment and agreement details attached cleanly.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 13,
                height: 1.5,
                color: AppConstants.textSecondary,
              ),
            ),
            const SizedBox(height: 24),
            if (customerId != null)
              AppButton(
                label: 'Open Customer Detail',
                width: double.infinity,
                icon: Icons.visibility_outlined,
                onPressed: () => context.go('/customers/$customerId'),
              ),
            const SizedBox(height: 12),
            AppButton(
              label: 'Back to Customers',
              width: double.infinity,
              outlined: true,
              icon: Icons.people_outline_rounded,
              onPressed: () => context.go('/customers'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionHeader(String title, String subtitle) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: AppConstants.textPrimary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: const TextStyle(
            fontSize: 12,
            height: 1.5,
            color: AppConstants.textSecondary,
          ),
        ),
      ],
    );
  }

  Widget _summaryRow(String label, String value) {
    if (value.trim().isEmpty) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          SizedBox(
            width: 90,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppConstants.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppConstants.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
