import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../config/constants.dart';
import '../../core/providers/connectivity_provider.dart';
import '../../core/providers/kyc_provider.dart';
import 'steps/step1_device.dart';
import 'steps/step2_identity.dart';
import 'steps/step3_contact.dart';
import 'steps/step4_income.dart';
import 'steps/step5_nok.dart';
import 'steps/step6_consent.dart';
import 'steps/step7_submit.dart';

class KycWizardScreen extends ConsumerStatefulWidget {
  final int routeStep;
  final String? draftCustomerId;

  const KycWizardScreen({
    super.key,
    required this.routeStep,
    this.draftCustomerId,
  });

  @override
  ConsumerState<KycWizardScreen> createState() => _KycWizardScreenState();
}

class _KycWizardScreenState extends ConsumerState<KycWizardScreen> {
  bool _bootstrappingDraft = true;

  static const _stepLabels = [
    'Device',
    'Identity',
    'Contact',
    'Income',
    'NOK',
    'Consent',
    'Submit',
  ];

  static const _stepSummaries = [
    (
      title: 'Match the right handset',
      subtitle:
          'Lock the exact device, confirm the deposit, and capture trusted evidence from the very first tap.',
      outcome: 'Build confidence before the customer shares deeper details.',
    ),
    (
      title: 'Confirm identity cleanly',
      subtitle:
          'Collect names, DOB, ID type, and sharp supporting photos without making the moment feel bureaucratic.',
      outcome: 'Reduce rework by capturing complete identity evidence once.',
    ),
    (
      title: 'Capture the best contact path',
      subtitle:
          'Record the number, branch, and location details that will keep reminders, payment prompts, and follow-up aligned.',
      outcome: 'Make the rest of the journey easier for both FO and customer.',
    ),
    (
      title: 'Understand repayment ability',
      subtitle:
          'Use simple language to document income and work context while the customer still feels in control.',
      outcome:
          'Support faster lending decisions with cleaner affordability data.',
    ),
    (
      title: 'Secure reliable next-of-kin details',
      subtitle:
          'Guide the customer gently to the most trusted NOK contacts instead of rushing through the relationship fields.',
      outcome: 'Strengthen recovery readiness without creating tension.',
    ),
    (
      title: 'Record consent with clarity',
      subtitle:
          'Keep the legal step warm and understandable so the customer knows exactly what they are agreeing to.',
      outcome: 'Clear consent lowers disputes later in the journey.',
    ),
    (
      title: 'Close the journey with assurance',
      subtitle:
          'Collect payment, present the agreement, capture signatures, and submit with a calm finish that feels premium.',
      outcome: 'The customer leaves feeling onboarded, not processed.',
    ),
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _bootstrapWizard();
    });
  }

  String _draftQuery(String? customerId) {
    if (customerId == null || customerId.isEmpty) {
      return '';
    }
    return '?draft=${Uri.encodeComponent(customerId)}';
  }

  void _goToStep(int step, String? customerId) {
    final s = step.clamp(1, 7);
    context.go('/kyc/new/step/$s${_draftQuery(customerId)}');
  }

  Future<void> _bootstrapWizard() async {
    if (widget.draftCustomerId != null && widget.draftCustomerId!.isNotEmpty) {
      final loaded = await ref
          .read(kycProvider.notifier)
          .loadExistingDraft(widget.draftCustomerId!);

      if (!mounted) {
        return;
      }

      if (loaded) {
        final current = ref.read(kycProvider).currentStep.clamp(1, 7);
        if (current != widget.routeStep) {
          _goToStep(current, ref.read(kycProvider).customerId);
        }
      }
    } else {
      ref.read(kycProvider.notifier).reset();
    }

    if (mounted) {
      setState(() {
        _bootstrappingDraft = false;
      });
      WidgetsBinding.instance
          .addPostFrameCallback((_) => _enforceRouteLimits());
    }
  }

  void _enforceRouteLimits() {
    if (!mounted) {
      return;
    }
    final kyc = ref.read(kycProvider);
    final max = kyc.maxReachableStep.clamp(1, 7);
    if (widget.routeStep > max) {
      _goToStep(max, kyc.customerId);
    }
  }

  @override
  void didUpdateWidget(covariant KycWizardScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.routeStep != widget.routeStep) {
      WidgetsBinding.instance
          .addPostFrameCallback((_) => _enforceRouteLimits());
    }
  }

  Future<void> _onWillPop() async {
    if (widget.routeStep > 1) {
      final id = ref.read(kycProvider).customerId;
      ref.read(kycProvider.notifier).setActiveStep(widget.routeStep - 1);
      _goToStep(widget.routeStep - 1, id);
      return;
    }
    FocusManager.instance.primaryFocus?.unfocus();
    final shouldExit = await _showExitDialog();
    if (shouldExit && mounted) {
      context.go('/dashboard');
    }
  }

  Future<bool> _showExitDialog() async {
    return await showDialog<bool>(
          context: context,
          builder: (_) => AlertDialog(
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
            title: const Text('Exit Registration?'),
            content: const Text(
              'Progress saved on the server stays safe, but local unsaved edits will be lost.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Stay here'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text(
                  'Exit',
                  style: TextStyle(color: AppConstants.error),
                ),
              ),
            ],
          ),
        ) ??
        false;
  }

  Widget _buildStepBody(int step) {
    switch (step) {
      case 1:
        return const Step1DeviceScreen();
      case 2:
        return const Step2IdentityScreen();
      case 3:
        return const Step3ContactScreen();
      case 4:
        return const Step4IncomeScreen();
      case 5:
        return const Step5NokScreen();
      case 6:
        return const Step6ConsentScreen();
      case 7:
      default:
        return const Step7SubmitScreen();
    }
  }

  @override
  Widget build(BuildContext context) {
    final stepIndex = widget.routeStep.clamp(1, 7);
    final descriptor = _stepSummaries[stepIndex - 1];
    final progress = stepIndex / 7;
    final media = MediaQuery.of(context);
    final isKeyboardVisible = media.viewInsets.bottom > 0;
    final showOutcomeBanner = !isKeyboardVisible && media.size.height >= 700;

    if (_bootstrappingDraft) {
      return const Scaffold(
        backgroundColor: AppConstants.kycWizardSurface,
        body: Center(
          child: CircularProgressIndicator(color: AppConstants.primary),
        ),
      );
    }

    ref.listen<KycDraftState>(kycProvider, (previous, next) {
      if (next.error != null && next.error != previous?.error) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next.error!),
            backgroundColor: AppConstants.error,
          ),
        );
      }

      if (next.stepSaved &&
          previous != null &&
          next.currentStep != previous.currentStep) {
        _goToStep(next.currentStep, next.customerId);
      }
    });

    final online = ref.watch(onlineStatusProvider);
    final kycState = ref.watch(kycProvider);

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) {
          return;
        }
        await _onWillPop();
      },
      child: Scaffold(
        backgroundColor: AppConstants.kycWizardSurface,
        body: DecoratedBox(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [
                AppConstants.kycWizardHeroTop,
                AppConstants.kycWizardHeroMid,
                AppConstants.kycWizardHeroBottom,
                AppConstants.kycWizardSurface,
              ],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              stops: [0, 0.18, 0.42, 0.43],
            ),
          ),
          child: SafeArea(
            child: Column(
              children: [
                if (online.maybeWhen(data: (v) => !v, orElse: () => false))
                  Material(
                    color: AppConstants.warningSurface,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 16, vertical: 10),
                      child: Row(
                        children: [
                          const Icon(Icons.wifi_off_rounded,
                              color: AppConstants.warning, size: 22),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'You appear offline. Check your connection before saving — uploads will resume when you retry.',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppConstants.textPrimary.withValues(
                                    alpha: 0.92),
                                height: 1.35,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                if (kycState.pendingRetryStep != null)
                  Material(
                    color: AppConstants.errorSurface,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 8),
                      child: Row(
                        children: [
                          const Icon(Icons.cloud_off_outlined,
                              color: AppConstants.error, size: 22),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Last send failed (network). You can retry without losing your entries.',
                              style: TextStyle(
                                fontSize: 12.5,
                                fontWeight: FontWeight.w600,
                                color: AppConstants.textPrimary.withValues(
                                    alpha: 0.9),
                              ),
                            ),
                          ),
                          TextButton(
                            onPressed: kycState.isSubmitting
                                ? null
                                : () => ref
                                    .read(kycProvider.notifier)
                                    .retryLastFailedSubmission(),
                            child: const Text('Retry'),
                          ),
                        ],
                      ),
                    ),
                  ),
                Container(
                  padding: const EdgeInsets.fromLTRB(14, 6, 18, 0),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _stepLabels[stepIndex - 1],
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                    color: AppConstants.kycWizardHeaderTitle,
                                    letterSpacing: -0.2,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  _stepSummaries[stepIndex - 1].title,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w500,
                                    color: Colors.white.withValues(alpha: 0.72),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          TextButton(
                            style: TextButton.styleFrom(
                              foregroundColor:
                                  AppConstants.kycWizardHeaderTitle,
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 4),
                              minimumSize: const Size(48, 40),
                            ),
                            onPressed: () async {
                              if (widget.routeStep > 1) {
                                final id = ref.read(kycProvider).customerId;
                                ref
                                    .read(kycProvider.notifier)
                                    .setActiveStep(widget.routeStep - 1);
                                _goToStep(widget.routeStep - 1, id);
                                return;
                              }
                              FocusManager.instance.primaryFocus?.unfocus();
                              final shouldExit = await _showExitDialog();
                              if (!context.mounted) {
                                return;
                              }
                              if (shouldExit) {
                                context.go('/dashboard');
                              }
                            },
                            child: Text(
                              widget.routeStep > 1 ? 'Previous' : 'Exit',
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 13,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(999),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.14),
                              ),
                            ),
                            child: Text(
                              'Step $stepIndex/7',
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: AppConstants.kycWizardHeaderTitle,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(999),
                        child: LinearProgressIndicator(
                          minHeight: 4,
                          value: progress,
                          backgroundColor:
                              Colors.white.withValues(alpha: 0.12),
                          valueColor: const AlwaysStoppedAnimation<Color>(
                            AppConstants.kycWizardAccentLine,
                          ),
                        ),
                      ),
                      const SizedBox(height: 4),
                    ],
                  ),
                ),
                Expanded(
                  child: Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: AppConstants.kycWizardSurface,
                      borderRadius: const BorderRadius.vertical(
                        top: Radius.circular(28),
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF0B1220).withValues(alpha: 0.08),
                          blurRadius: 28,
                          offset: const Offset(0, -12),
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        if (showOutcomeBanner)
                          Padding(
                            padding: const EdgeInsets.fromLTRB(18, 18, 18, 0),
                            child: Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(
                                  colors: [
                                    AppConstants.kycWizardInsightTop,
                                    AppConstants.kycWizardInsightBottom,
                                  ],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(
                                  color: AppConstants.primary.withValues(
                                    alpha: 0.14,
                                  ),
                                ),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Container(
                                    width: 34,
                                    height: 34,
                                    decoration: BoxDecoration(
                                      gradient: const LinearGradient(
                                        colors: [
                                          AppConstants.primary,
                                          AppConstants.primaryDark,
                                        ],
                                      ),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Icon(
                                      Icons.auto_awesome_rounded,
                                      size: 18,
                                      color: Colors.white,
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      descriptor.outcome,
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w700,
                                        height: 1.45,
                                        color: AppConstants.textPrimary,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        Expanded(child: _buildStepBody(stepIndex)),
                      ],
                    ),
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
