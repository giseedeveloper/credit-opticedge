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
  const KycWizardScreen({super.key});

  @override
  ConsumerState<KycWizardScreen> createState() => _KycWizardScreenState();
}

class _KycWizardScreenState extends ConsumerState<KycWizardScreen> {
  final PageController _pageCtrl = PageController();

  static const _stepLabels = [
    'Device',
    'Identity',
    'Contact',
    'Income',
    'NOK',
    'Consent',
    'Submit',
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(kycProvider.notifier).reset();
    });
  }

  @override
  void dispose() {
    _pageCtrl.dispose();
    super.dispose();
  }

  void _toStep(int step) {
    // Avoid Android IME racing with disposed TextInputConnections when the page changes.
    FocusManager.instance.primaryFocus?.unfocus();
    _pageCtrl.animateToPage(
      step - 1,
      duration: const Duration(milliseconds: 350),
      curve: Curves.easeInOut,
    );
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
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Text('Exit Registration?'),
            content: const Text(
                'Progress saved on server will be kept, but unsaved local data will be lost.'),
            actions: [
              TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: const Text('Continue')),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Exit',
                    style: TextStyle(color: AppConstants.error)),
              ),
            ],
          ),
        ) ??
        false;
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);

    ref.listen(kycProvider, (_, next) {
      if (next.stepSaved && next.currentStep != state.currentStep) {
        _toStep(next.currentStep);
      }
      if (next.error != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next.error!),
            backgroundColor: AppConstants.error,
            behavior: SnackBarBehavior.floating,
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        );
      }
    });

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        await _onWillPop();
      },
      child: Scaffold(
        backgroundColor: AppConstants.background,
        appBar: AppBar(
          title: Text(
            'New Customer — Step ${state.currentStep} of 7',
            style: const TextStyle(fontSize: 15),
          ),
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 18),
            onPressed: () async {
              if (state.currentStep > 1) {
                _toStep(state.currentStep - 1);
              } else {
                FocusManager.instance.primaryFocus?.unfocus();
                final shouldExit = await _showExitDialog();
                if (!context.mounted) {
                  return;
                }
                if (shouldExit) {
                  context.pop();
                }
              }
            },
          ),
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(58),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              child: StepIndicator(
                totalSteps: 7,
                currentStep: state.currentStep,
                labels: _stepLabels,
              ),
            ),
          ),
        ),
        body: PageView(
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
    );
  }
}
