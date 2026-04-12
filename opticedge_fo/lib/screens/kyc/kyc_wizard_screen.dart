import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../config/constants.dart';
import '../../core/providers/kyc_provider.dart';
import '../../widgets/kyc/step_indicator.dart';
import 'steps/step1_device.dart';
import 'steps/step2_identity.dart';
import 'steps/step3_contact.dart';
import 'steps/step4_income.dart';
import 'steps/step5_nok.dart';
import 'steps/step6_consent.dart';
import 'steps/step7_submit.dart';

class KycWizardScreen extends ConsumerStatefulWidget {
  final String? draftCustomerId;

  const KycWizardScreen({
    super.key,
    this.draftCustomerId,
  });

  @override
  ConsumerState<KycWizardScreen> createState() => _KycWizardScreenState();
}

class _KycWizardScreenState extends ConsumerState<KycWizardScreen> {
  final PageController _pageCtrl = PageController();
  bool _bootstrappingDraft = true;
  int? _pendingPageIndex;

  static const _stepLabels = [
    'Device',
    'Identity',
    'Contact',
    'Income',
    'NOK',
    'Consent',
    'Submit',
  ];

  static const _stepIcons = [
    Icons.qr_code_scanner_rounded,
    Icons.badge_rounded,
    Icons.call_rounded,
    Icons.payments_outlined,
    Icons.shield_outlined,
    Icons.gavel_rounded,
    Icons.verified_user_rounded,
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

  Future<void> _bootstrapWizard() async {
    int? resumePageIndex;

    if (widget.draftCustomerId != null && widget.draftCustomerId!.isNotEmpty) {
      final loaded = await ref
          .read(kycProvider.notifier)
          .loadExistingDraft(widget.draftCustomerId!);

      if (!mounted) {
        return;
      }

      final currentState = ref.read(kycProvider);
      if (loaded) {
        resumePageIndex = currentState.currentStep - 1;
      }
    } else {
      ref.read(kycProvider.notifier).reset();
    }

    if (mounted) {
      setState(() {
        _bootstrappingDraft = false;
        _pendingPageIndex = resumePageIndex;
      });
    }
  }

  @override
  void dispose() {
    _pageCtrl.dispose();
    super.dispose();
  }

  void _toStep(int step) {
    FocusManager.instance.primaryFocus?.unfocus();
    _moveToPage(step - 1, animated: true);
  }

  void _moveToPage(int pageIndex, {required bool animated}) {
    if (!_pageCtrl.hasClients) {
      _pendingPageIndex = pageIndex;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _flushPendingPage(animated: animated);
        }
      });
      return;
    }

    final currentPage = _pageCtrl.page?.round() ?? _pageCtrl.initialPage;
    if (currentPage == pageIndex) {
      return;
    }

    if (animated) {
      _pageCtrl.animateToPage(
        pageIndex,
        duration: const Duration(milliseconds: 420),
        curve: Curves.easeOutCubic,
      );
      return;
    }

    _pageCtrl.jumpToPage(pageIndex);
  }

  void _flushPendingPage({required bool animated}) {
    final pageIndex = _pendingPageIndex;
    if (pageIndex == null || !_pageCtrl.hasClients) {
      return;
    }

    _pendingPageIndex = null;
    _moveToPage(pageIndex, animated: animated);
  }

  Future<bool> _onWillPop() async {
    final draft = ref.read(kycProvider);
    if (draft.currentStep > 1) {
      _toStep(draft.currentStep - 1);
      return false;
    }
    FocusManager.instance.primaryFocus?.unfocus();
    return _showExitDialog();
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

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);
    if (_bootstrappingDraft) {
      return const Scaffold(
        backgroundColor: AppConstants.background,
        body: Center(
          child: CircularProgressIndicator(color: AppConstants.primary),
        ),
      );
    }

    final descriptor = _stepSummaries[state.currentStep - 1];
    final progress = state.currentStep / 7;
    final media = MediaQuery.of(context);
    final isKeyboardVisible = media.viewInsets.bottom > 0;
    final isCompactHeader = isKeyboardVisible || media.size.height < 860;
    final showOutcomeBanner = !isKeyboardVisible && media.size.height >= 700;
    final titleFontSize = isCompactHeader ? 20.0 : 24.0;
    final headerPadding = isCompactHeader ? 15.0 : 18.0;
    final currentStepIcon = _stepIcons[state.currentStep - 1];

    if (_pendingPageIndex != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          _flushPendingPage(animated: false);
        }
      });
    }

    ref.listen(kycProvider, (previous, next) {
      if (next.stepSaved && next.currentStep != state.currentStep) {
        _toStep(next.currentStep);
      }

      if (next.error != null && next.error != previous?.error) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next.error!),
            backgroundColor: AppConstants.error,
          ),
        );
      }
    });

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) {
          return;
        }
        await _onWillPop();
      },
      child: Scaffold(
        backgroundColor: AppConstants.background,
        body: DecoratedBox(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [
                AppConstants.heroStart,
                AppConstants.heroEnd,
                AppConstants.background,
              ],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              stops: [0, 0.23, 0.24],
            ),
          ),
          child: SafeArea(
            child: Column(
              children: [
                Padding(
                  padding: EdgeInsets.fromLTRB(
                    18,
                    isCompactHeader ? 8 : 12,
                    18,
                    12,
                  ),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 240),
                    curve: Curves.easeOutCubic,
                    width: double.infinity,
                    padding: EdgeInsets.all(headerPadding),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.white.withValues(alpha: 0.14),
                          Colors.white.withValues(alpha: 0.06),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(28),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.10),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            InkWell(
                              borderRadius: BorderRadius.circular(18),
                              onTap: () async {
                                if (state.currentStep > 1) {
                                  _toStep(state.currentStep - 1);
                                  return;
                                }

                                FocusManager.instance.primaryFocus?.unfocus();
                                final shouldExit = await _showExitDialog();
                                if (!context.mounted) {
                                  return;
                                }
                                if (shouldExit) {
                                  context.pop();
                                }
                              },
                              child: Ink(
                                width: 42,
                                height: 42,
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(18),
                                ),
                                child: const Icon(
                                  Icons.arrow_back_ios_new_rounded,
                                  size: 18,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                            const Spacer(),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 8,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: Text(
                                'Step ${state.currentStep} of 7',
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        const Text(
                          'OpticEdge FO KYC',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.3,
                            color: Color(0xFFDCE7F3),
                          ),
                        ),
                        SizedBox(height: isCompactHeader ? 4 : 6),
                        Text(
                          descriptor.title,
                          maxLines: isCompactHeader ? 2 : 3,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: titleFontSize,
                            fontWeight: FontWeight.w800,
                            height: 1.1,
                            color: Colors.white,
                          ),
                        ),
                        SizedBox(height: isCompactHeader ? 6 : 8),
                        Text(
                          descriptor.subtitle,
                          maxLines: isCompactHeader ? 2 : 3,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                            height: 1.45,
                            color: Color(0xFFE5EEF8),
                          ),
                        ),
                        SizedBox(height: isCompactHeader ? 12 : 16),
                        Row(
                          children: [
                            Expanded(
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(999),
                                child: LinearProgressIndicator(
                                  minHeight: 8,
                                  value: progress,
                                  backgroundColor:
                                      Colors.white.withValues(alpha: 0.14),
                                  valueColor:
                                      const AlwaysStoppedAnimation<Color>(
                                    AppConstants.primaryLight,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Text(
                              '${(progress * 100).round()}%',
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: isCompactHeader ? 12 : 14),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 10,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.08),
                            ),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 36,
                                height: 36,
                                decoration: BoxDecoration(
                                  color: AppConstants.primaryLight
                                      .withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: Icon(
                                  currentStepIcon,
                                  size: 18,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      _stepLabels[state.currentStep - 1],
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w800,
                                        color: Colors.white,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      'Guided field capture for step ${state.currentStep}',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w600,
                                        color: Colors.white.withValues(
                                          alpha: 0.72,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(18, 0, 18, 12),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
                    decoration: BoxDecoration(
                      color: AppConstants.surface,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.05),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 6,
                              ),
                              decoration: BoxDecoration(
                                color: AppConstants.primarySurface,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Text(
                                'Step journey',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                  color: AppConstants.primaryDark,
                                ),
                              ),
                            ),
                            const Spacer(),
                            Text(
                              '${7 - state.currentStep} remaining',
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: AppConstants.textSecondary,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        StepIndicator(
                          totalSteps: 7,
                          currentStep: state.currentStep,
                          labels: _stepLabels,
                          compact: isCompactHeader,
                        ),
                      ],
                    ),
                  ),
                ),
                Expanded(
                  child: Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: AppConstants.surface,
                      borderRadius: const BorderRadius.vertical(
                        top: Radius.circular(32),
                      ),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.06),
                          blurRadius: 22,
                          offset: const Offset(0, -10),
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
                                    Color(0xFFFFF7ED),
                                    Color(0xFFFFFBF5),
                                  ],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(
                                  color: AppConstants.primary.withValues(
                                    alpha: 0.12,
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
                                      color: AppConstants.primarySurface,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Icon(
                                      Icons.auto_awesome_rounded,
                                      size: 18,
                                      color: AppConstants.primary,
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
                        Expanded(
                          child: PageView(
                            controller: _pageCtrl,
                            physics: const NeverScrollableScrollPhysics(),
                            children: const [
                              Step1DeviceScreen(),
                              Step2IdentityScreen(),
                              Step3ContactScreen(),
                              Step4IncomeScreen(),
                              Step5NokScreen(),
                              Step6ConsentScreen(),
                              Step7SubmitScreen(),
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
        ),
      ),
    );
  }
}
