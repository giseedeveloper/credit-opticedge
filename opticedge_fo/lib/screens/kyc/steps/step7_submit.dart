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
    final currentState = ref.read(kycProvider);

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

    final hasCustomerSignature =
        (customerSignature ?? currentState.customerSignatureData)
                .trim()
                .isNotEmpty ||
            (currentState.agreementContext?.customerSignatureUrl?.isNotEmpty ??
                false);
    final hasFoSignature = (foSignature ?? currentState.foSignatureData)
            .trim()
            .isNotEmpty ||
        (currentState.agreementContext?.foSignatureUrl?.isNotEmpty ?? false);

    if (!hasCustomerSignature || !hasFoSignature) {
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

    if (state.assetHandoverList == null &&
        !(state.agreementContext?.handoverListUrl?.isNotEmpty ?? false)) {
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
      builder: (context) => SafeArea(
        child: SingleChildScrollView(
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
              const Text(
                'Use this preview to guide the customer slowly before asking for the decision and signatures.',
                style: TextStyle(
                  fontSize: 12,
                  height: 1.5,
                  color: AppConstants.textSecondary,
                ),
              ),
              const SizedBox(height: 16),
              _agreementPreviewSheetCard(document),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppConstants.infoSurface,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: AppConstants.info.withValues(alpha: 0.14),
                  ),
                ),
                child: const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'What FO should confirm aloud',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: AppConstants.textPrimary,
                      ),
                    ),
                    SizedBox(height: 8),
                    Text(
                      '1. Starting payment has been received successfully.',
                      style: TextStyle(
                        fontSize: 12,
                        height: 1.5,
                        color: AppConstants.textSecondary,
                      ),
                    ),
                    Text(
                      '2. Customer understands the handset, repayment, and obligations.',
                      style: TextStyle(
                        fontSize: 12,
                        height: 1.5,
                        color: AppConstants.textSecondary,
                      ),
                    ),
                    Text(
                      '3. Customer is free to ask questions before choosing yes or no.',
                      style: TextStyle(
                        fontSize: 12,
                        height: 1.5,
                        color: AppConstants.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppConstants.borderLight,
                  borderRadius: BorderRadius.circular(18),
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
    final signatureReady = (state.customerSignatureData.isNotEmpty ||
            (agreementContext?.customerSignatureUrl?.isNotEmpty ?? false)) &&
        (state.foSignatureData.isNotEmpty ||
            (agreementContext?.foSignatureUrl?.isNotEmpty ?? false));

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
          _closingFlowCard(
            paymentReady: paymentReady,
            agreementReady:
                agreementContext?.activeDocument != null && paymentReady,
            signatureReady: signatureReady,
            handoverReady: state.assetHandoverList != null ||
                (agreementContext?.handoverListUrl?.isNotEmpty ?? false),
          ).animate().fadeIn(duration: 260.ms).slideY(begin: 0.08, end: 0),
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
                if (state.error != null) ...[
                  const SizedBox(height: 12),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppConstants.errorSurface,
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(
                        color: AppConstants.error.withValues(alpha: 0.18),
                      ),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(
                          Icons.info_outline_rounded,
                          color: AppConstants.error,
                          size: 18,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            state.error!,
                            style: const TextStyle(
                              fontSize: 12,
                              height: 1.45,
                              fontWeight: FontWeight.w600,
                              color: AppConstants.error,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
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
                  _agreementHero(
                    agreementContext: agreementContext!,
                    paymentReady: paymentReady,
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
                _signatureFlowLane(
                  hasCustomerSignature:
                      state.customerSignatureData.isNotEmpty ||
                          (agreementContext?.customerSignatureUrl?.isNotEmpty ??
                              false),
                  hasFoSignature: state.foSignatureData.isNotEmpty ||
                      (agreementContext?.foSignatureUrl?.isNotEmpty ?? false),
                  hasChecklist: state.assetHandoverList != null ||
                      (agreementContext?.handoverListUrl?.isNotEmpty ?? false),
                ),
                const SizedBox(height: 14),
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
                LayoutBuilder(
                  builder: (context, constraints) {
                    final useColumn = constraints.maxWidth < 380;

                    if (useColumn) {
                      return Column(
                        children: [
                          SizedBox(
                            width: double.infinity,
                            height: 124,
                            child: PhotoPickerTile(
                              label: 'Checklist',
                              file: state.assetHandoverList,
                              onPicked: (file) => ref
                                  .read(kycProvider.notifier)
                                  .setPhoto('handover', file),
                            ),
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
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
                        ],
                      );
                    }

                    return Row(
                      children: [
                        SizedBox(
                          width: 144,
                          height: 124,
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
                    );
                  },
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

  Widget _closingFlowCard({
    required bool paymentReady,
    required bool agreementReady,
    required bool signatureReady,
    required bool handoverReady,
  }) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF103454), Color(0xFF1F5A88)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppConstants.heroEnd.withValues(alpha: 0.2),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Final Mile Flow',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Deposit, agreement, signatures, na handover proof vikikaa sawa hapa, submit ya mwisho inakuwa safi na ya kuaminika.',
            style: TextStyle(
              fontSize: 12,
              height: 1.45,
              color: Colors.white.withValues(alpha: 0.82),
            ),
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _flowStageChip(
                label: 'Payment',
                ready: paymentReady,
                icon: Icons.payments_outlined,
              ),
              _flowStageChip(
                label: 'Agreement',
                ready: agreementReady,
                icon: Icons.picture_as_pdf_outlined,
              ),
              _flowStageChip(
                label: 'Signatures',
                ready: signatureReady,
                icon: Icons.draw_outlined,
              ),
              _flowStageChip(
                label: 'Handover',
                ready: handoverReady,
                icon: Icons.inventory_2_outlined,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _flowStageChip({
    required String label,
    required bool ready,
    required IconData icon,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: ready
            ? Colors.white.withValues(alpha: 0.16)
            : Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: 0.14)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            ready ? Icons.check_circle_rounded : icon,
            size: 14,
            color: Colors.white,
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
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

  Widget _agreementHero({
    required KycAgreementContext agreementContext,
    required bool paymentReady,
  }) {
    final document = agreementContext.activeDocument!;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppConstants.surfaceRaised, AppConstants.infoSurface],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
      ),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 48,
                height: 60,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppConstants.border),
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
                      document.title,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: AppConstants.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      paymentReady
                          ? 'Payment is successful. You can now show the agreement, guide the customer, and record the decision.'
                          : 'Finish the payment first. Agreement presentation unlocks immediately after successful payment.',
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
          const SizedBox(height: 12),
          _agreementPreviewSheetCard(document),
          const SizedBox(height: 12),
          AppButton(
            label: 'View Agreement Preview',
            outlined: true,
            icon: Icons.visibility_outlined,
            onPressed: () => _showAgreementDialog(agreementContext),
          ),
        ],
      ),
    );
  }

  Widget _agreementPreviewSheetCard(KycDocumentOption document) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 16,
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
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: AppConstants.primarySurface,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.description_outlined,
                  color: AppConstants.primary,
                  size: 18,
                ),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Agreement preview',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: AppConstants.textPrimary,
                  ),
                ),
              ),
              if ((document.mimeType ?? '').isNotEmpty)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppConstants.surfaceMuted,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    document.mimeType!,
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: AppConstants.textHint,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFF8FAFC), Color(0xFFF1F5F9)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppConstants.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  document.title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: AppConstants.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  width: 72,
                  height: 5,
                  decoration: BoxDecoration(
                    color: AppConstants.primary,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Preview focus',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: AppConstants.textHint,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  '• Device and deposit confirmation',
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
                const Text(
                  '• Customer obligations and payment expectations',
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
                const Text(
                  '• Signature and handover acknowledgement',
                  style: TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          if ((document.originalName ?? '').isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(
              document.originalName!,
              style: const TextStyle(
                fontSize: 11,
                color: AppConstants.textHint,
              ),
            ),
          ],
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
    final confirmedAmount = paymentContext?.amount ?? depositAmount;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.16)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  isComplete
                      ? Icons.check_circle_outline
                      : hasPrompt
                          ? Icons.hourglass_top_rounded
                          : Icons.phone_iphone_outlined,
                  color: color,
                  size: 20,
                ),
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
                          ? 'Deposit of TZS $confirmedAmount was confirmed successfully.'
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
          if (hasPrompt) ...[
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if ((paymentContext.phone ?? '').isNotEmpty)
                  _statusTag(
                    icon: Icons.phone_android_outlined,
                    label: paymentContext.phone!,
                    color: color,
                  ),
                if ((paymentContext.reference ?? '').isNotEmpty)
                  _statusTag(
                    icon: Icons.receipt_long_outlined,
                    label: paymentContext.reference!,
                    color: color,
                  ),
                _statusTag(
                  icon: Icons.payments_outlined,
                  label: 'TZS ${paymentContext.amount ?? depositAmount}',
                  color: color,
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _statusTag({
    required IconData icon,
    required String label,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.85),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: AppConstants.textSecondary,
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

  Widget _signatureFlowLane({
    required bool hasCustomerSignature,
    required bool hasFoSignature,
    required bool hasChecklist,
  }) {
    final stages = [
      (
        label: 'Customer signs',
        icon: Icons.draw_outlined,
        done: hasCustomerSignature,
      ),
      (
        label: 'FO signs',
        icon: Icons.edit_note_rounded,
        done: hasFoSignature,
      ),
      (
        label: 'Checklist attached',
        icon: Icons.inventory_outlined,
        done: hasChecklist,
      ),
    ];

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppConstants.surfaceMuted,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppConstants.border),
      ),
      child: Row(
        children: [
          for (var index = 0; index < stages.length; index++) ...[
            Expanded(
              child: Column(
                children: [
                  Icon(
                    stages[index].done
                        ? Icons.check_circle_rounded
                        : stages[index].icon,
                    size: 18,
                    color: stages[index].done
                        ? AppConstants.success
                        : AppConstants.textSecondary,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    stages[index].label,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: stages[index].done
                          ? FontWeight.w800
                          : FontWeight.w600,
                      color: stages[index].done
                          ? AppConstants.textPrimary
                          : AppConstants.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            if (index < stages.length - 1)
              Container(
                width: 18,
                height: 2,
                margin: const EdgeInsets.symmetric(horizontal: 6),
                color: stages[index].done
                    ? AppConstants.success
                    : AppConstants.border,
              ),
          ],
        ],
      ),
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
