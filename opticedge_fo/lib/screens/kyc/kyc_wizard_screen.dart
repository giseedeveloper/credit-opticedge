import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../config/app_icon_assets.dart';
import '../../config/constants.dart';
import '../../core/providers/connectivity_provider.dart';
import '../../core/providers/kyc_provider.dart';
import '../../widgets/common/app_color_icon.dart';
import '../../widgets/common/premium_glass_background.dart';
import 'steps/step1_device.dart';
import 'steps/step2_identity.dart';
import 'steps/step3_contact.dart';
import 'steps/step4_income.dart';
import 'steps/step5_nok.dart';
import 'steps/step6_consent.dart';
import 'steps/step7_submit.dart';
import '../../widgets/kyc/step_indicator.dart';

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
    'Next of Kin',
    'Consent',
    'Submit',
  ];

  static const _stepIcons = [
    AppIconAssets.handset,
    AppIconAssets.identity,
    AppIconAssets.contact,
    AppIconAssets.income,
    AppIconAssets.nok,
    AppIconAssets.consent,
    AppIconAssets.receipt,
  ];

  static const _stepSummaries = [
    (
      title: 'Simu, picha 3, model, IMEI, bei',
      subtitle: '',
      outcome: '',
    ),
    (
      title: 'Utambulisho na picha za ID',
      subtitle: '',
      outcome: 'Jaza taarifa muhimu na picha wazi.',
    ),
    (
      title: 'Mawasiliano na eneo',
      subtitle: '',
      outcome: 'Namba, mkoa, anwani.',
    ),
    (
      title: 'Kazi na kipato',
      subtitle: '',
      outcome: 'Kipato, matumizi, picha ya biashara.',
    ),
    (
      title: 'Mtu wa karibu',
      subtitle: '',
      outcome: 'Jina, simu, uhusiano.',
    ),
    (
      title: 'Ridhaa (vitatu)',
      subtitle: '',
      outcome: 'Vigezo, faragha, mawasiliano.',
    ),
    (
      title: 'Malipo, mkataba, tuma',
      subtitle: '',
      outcome: 'ETR, saini, thibitisha.',
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
    return step.clamp(1, 7);
  }

  int _previousCanonicalStep(int step) {
    final s = _canonicalStep(step);
    return (s - 1).clamp(1, 7);
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
          builder: (dialogContext) => AlertDialog(
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
            title: const Text('Exit Registration?'),
            content: const Text(
              'Progress saved on the server stays safe, but local unsaved edits will be lost.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(dialogContext).pop(false),
                child: const Text('Stay here'),
              ),
              TextButton(
                onPressed: () => Navigator.of(dialogContext).pop(true),
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

  Widget _buildStageHeader(
    BuildContext context, {
    required int stepIndex,
    required ({String title, String subtitle, String outcome}) descriptor,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final titleColor =
        isDark ? AppConstants.kycWizardHeaderTitle : AppConstants.textPrimary;
    final metaColor = isDark
        ? AppConstants.kycWizardAccentLine.withValues(alpha: 0.95)
        : AppConstants.primary;
    final subtitleColor = isDark
        ? Colors.white.withValues(alpha: 0.72)
        : AppConstants.textSecondary;
    final iconBg = isDark
        ? Colors.white.withValues(alpha: 0.14)
        : Colors.white.withValues(alpha: 0.92);
    final iconBorder = isDark
        ? Colors.white.withValues(alpha: 0.14)
        : AppConstants.border.withValues(alpha: 0.55);

    return Container(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDark
              ? [
                  Colors.white.withValues(alpha: 0.08),
                  Colors.white.withValues(alpha: 0.02),
                ]
              : [
                  Colors.white.withValues(alpha: 0.72),
                  Colors.white.withValues(alpha: 0.35),
                ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border(
          bottom: BorderSide(
            color: isDark
                ? Colors.white.withValues(alpha: 0.08)
                : AppConstants.border.withValues(alpha: 0.35),
          ),
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
                  color: iconBg,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: iconBorder,
                  ),
                ),
                child: Center(
                  child: AppColorIcon(
                    assetName: _stepIcons[stepIndex - 1],
                    size: 27,
                    semanticsLabel: _stepLabels[stepIndex - 1],
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Step $stepIndex of 7',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 2.2,
                        color: metaColor,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _stepLabels[stepIndex - 1],
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 19,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.45,
                        color: titleColor,
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
                        color: subtitleColor,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              TextButton(
                style: TextButton.styleFrom(
                  foregroundColor:
                      isDark ? titleColor : AppConstants.primary,
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
                  if (!context.mounted) {
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
          StepIndicator(
            totalSteps: 7,
            currentStep: stepIndex,
            labels: _stepLabels,
            iconAssets: _stepIcons,
            compact: true,
            onDarkBackground: isDark,
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
        backgroundColor: Colors.transparent,
        body: PremiumGlassBackground(
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
                  context,
                  stepIndex: stepIndex,
                  descriptor: descriptor,
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
                        if (showOutcomeBanner &&
                            descriptor.outcome.trim().isNotEmpty)
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
