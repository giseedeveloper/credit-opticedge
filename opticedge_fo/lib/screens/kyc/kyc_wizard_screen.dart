import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../config/app_icon_assets.dart';
import '../../config/constants.dart';
import '../../core/providers/connectivity_provider.dart';
import '../../core/providers/kyc_provider.dart';
import '../../widgets/common/app_color_icon.dart';
import 'steps/stage2_customer_verification.dart';
import 'steps/step1_device.dart';
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

  static const _stageLabels = [
    'Device & Offer',
    'Customer & Verification',
    'Payment & Handover',
  ];

  static const _stageShortLabels = [
    'Device',
    'Customer',
    'Finalize',
  ];

  static const _stageIcons = [
    AppIconAssets.handset,
    AppIconAssets.identity,
    AppIconAssets.receipt,
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

  int _canonicalStep(int step) {
    final s = step.clamp(1, 7);
    if (s <= 1) {
      return 1;
    }
    if (s < 7) {
      return 2;
    }
    return 7;
  }

  int _previousCanonicalStep(int step) {
    final s = _canonicalStep(step);
    if (s == 7) {
      return 2;
    }
    if (s == 2) {
      return 1;
    }
    return 1;
  }

  void _goToStep(int step, String? customerId) {
    final s = _canonicalStep(step);
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
        final current = _canonicalStep(ref.read(kycProvider).currentStep);
        if (current != _canonicalStep(widget.routeStep)) {
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
    final requested = _canonicalStep(widget.routeStep);
    final max = _canonicalStep(kyc.maxReachableStep.clamp(1, 7));
    if (requested > max) {
      _goToStep(max, kyc.customerId);
      return;
    }
    if (widget.routeStep != requested) {
      _goToStep(requested, kyc.customerId);
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
    final currentStep = _canonicalStep(widget.routeStep);
    if (currentStep > 1) {
      final id = ref.read(kycProvider).customerId;
      final previousStep = _previousCanonicalStep(currentStep);
      ref.read(kycProvider.notifier).setActiveStep(previousStep);
      _goToStep(previousStep, id);
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
      case 3:
      case 4:
      case 5:
      case 6:
        return const Stage2CustomerVerificationScreen();
      case 7:
      default:
        return const Step7SubmitScreen();
    }
  }

  int _stageForStep(int step) {
    if (step <= 1) {
      return 1;
    }
    if (step <= 6) {
      return 2;
    }
    return 3;
  }

  double _stageProgressForStep(int step) {
    final stage = _stageForStep(step);
    if (stage == 1) {
      return step > 1 ? 1 : 0.55;
    }
    if (stage == 2) {
      return 0.72;
    }
    return 1;
  }

  String _stageSubstepForStep(int step) {
    final stage = _stageForStep(step);
    if (stage == 1) {
      return 'Scan and offer setup';
    }
    if (stage == 2) {
      return 'Identity, contact, income, NOK and consent';
    }
    return 'Payment, agreement and handover';
  }

  Widget _buildStageHeader({
    required int stepIndex,
    required ({String title, String subtitle, String outcome}) descriptor,
    required KycDraftState kycState,
  }) {
    final activeStage = _stageForStep(stepIndex);
    final activeStageIndex = activeStage - 1;
    final stageProgress = _stageProgressForStep(stepIndex);

    return Container(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            Colors.white.withValues(alpha: 0.08),
            Colors.white.withValues(alpha: 0.02),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border(
          bottom: BorderSide(color: Colors.white.withValues(alpha: 0.08)),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.14),
                  ),
                ),
                child: Center(
                  child: AppColorIcon(
                    assetName: _stageIcons[activeStageIndex],
                    size: 27,
                    semanticsLabel: _stageLabels[activeStageIndex],
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Stage $activeStage of 3',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 2.2,
                        color: AppConstants.kycWizardAccentLine
                            .withValues(alpha: 0.95),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _stageLabels[activeStageIndex],
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 19,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.45,
                        color: AppConstants.kycWizardHeaderTitle,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      descriptor.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        height: 1.28,
                        color: Colors.white.withValues(alpha: 0.72),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              TextButton(
                style: TextButton.styleFrom(
                  foregroundColor: AppConstants.kycWizardHeaderTitle,
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  minimumSize: const Size(0, 38),
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                onPressed: () async {
                  if (_canonicalStep(stepIndex) > 1) {
                    final id = ref.read(kycProvider).customerId;
                    final previousStep = _previousCanonicalStep(stepIndex);
                    ref.read(kycProvider.notifier).setActiveStep(previousStep);
                    _goToStep(previousStep, id);
                    return;
                  }
                  FocusManager.instance.primaryFocus?.unfocus();
                  final shouldExit = await _showExitDialog();
                  if (!mounted) {
                    return;
                  }
                  if (shouldExit) {
                    context.go('/dashboard');
                  }
                },
                child: Text(
                  stepIndex > 1 ? 'Back' : 'Exit',
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: List.generate(3, (index) {
              final stageNumber = index + 1;
              final done = activeStage > stageNumber;
              final active = activeStage == stageNumber;
              final progress = done ? 1.0 : (active ? stageProgress : 0.0);

              return Expanded(
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 260),
                  margin: EdgeInsets.only(right: index == 2 ? 0 : 8),
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: active
                        ? Colors.white.withValues(alpha: 0.15)
                        : done
                            ? AppConstants.success.withValues(alpha: 0.16)
                            : Colors.white.withValues(alpha: 0.07),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: active
                          ? Colors.white.withValues(alpha: 0.18)
                          : done
                              ? AppConstants.success.withValues(alpha: 0.24)
                              : Colors.white.withValues(alpha: 0.08),
                    ),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          if (done)
                            const Icon(
                              Icons.check_circle_rounded,
                              color: AppConstants.success,
                              size: 18,
                            )
                          else
                            AppColorIcon(
                              assetName: _stageIcons[index],
                              size: 18,
                              opacity: active ? 1 : 0.64,
                              semanticsLabel: _stageShortLabels[index],
                            ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              _stageShortLabels[index],
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                                color: active || done
                                    ? Colors.white
                                    : Colors.white.withValues(alpha: 0.62),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(999),
                        child: LinearProgressIndicator(
                          minHeight: 4,
                          value: progress,
                          backgroundColor: Colors.white.withValues(alpha: 0.11),
                          valueColor: AlwaysStoppedAnimation<Color>(
                            done
                                ? AppConstants.success
                                : AppConstants.kycWizardAccentLine,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: Text(
                  _stageSubstepForStep(stepIndex),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Colors.white.withValues(alpha: 0.72),
                  ),
                ),
              ),
              if (kycState.maxReachableStep > stepIndex)
                Text(
                  'Resume ready',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: AppConstants.success.withValues(alpha: 0.95),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final stepIndex = _canonicalStep(widget.routeStep);
    final descriptor = _stepSummaries[stepIndex - 1];
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
                                color: AppConstants.textPrimary
                                    .withValues(alpha: 0.92),
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
                                color: AppConstants.textPrimary
                                    .withValues(alpha: 0.9),
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
                _buildStageHeader(
                  stepIndex: stepIndex,
                  descriptor: descriptor,
                  kycState: kycState,
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
                          color:
                              const Color(0xFF0B1220).withValues(alpha: 0.08),
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
