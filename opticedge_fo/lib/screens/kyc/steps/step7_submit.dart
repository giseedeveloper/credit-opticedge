import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../config/constants.dart';
import '../../../core/providers/kyc_provider.dart';
import '../../../widgets/common/app_button.dart';

class Step7SubmitScreen extends ConsumerStatefulWidget {
  const Step7SubmitScreen({super.key});
  @override
  ConsumerState<Step7SubmitScreen> createState() => _Step7State();
}

class _Step7State extends ConsumerState<Step7SubmitScreen>
    with SingleTickerProviderStateMixin {
  final _notesCtrl = TextEditingController();
  bool _submitted = false;
  Map<String, dynamic>? _result;

  late AnimationController _successAnim;
  late Animation<double> _ringScale;
  late Animation<double> _ringOpacity;

  final _sourceOptions = [
    ('walk_in', 'Walk In', Icons.store_outlined),
    ('referral', 'Referral', Icons.share_outlined),
    ('online', 'Online', Icons.public_rounded),
    ('event', 'Event / Campaign', Icons.event_outlined),
  ];

  @override
  void initState() {
    super.initState();
    _successAnim = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 800));
    _ringScale = Tween<double>(begin: 0.3, end: 1.0).animate(
        CurvedAnimation(parent: _successAnim, curve: Curves.elasticOut));
    _ringOpacity = Tween<double>(begin: 0, end: 1).animate(
        CurvedAnimation(parent: _successAnim, curve: Curves.easeOut));
  }

  @override
  void dispose() {
    _successAnim.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    ref.read(kycProvider.notifier).update(
        (s) => s.copyWith(foNotes: _notesCtrl.text.trim()));
    final result = await ref.read(kycProvider.notifier).submitStep7();
    if (result != null && mounted) {
      setState(() {
        _submitted = true;
        _result = result;
      });
      _successAnim.forward();
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycProvider);

    if (_submitted) return _buildSuccessView(context);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionHeader('Review & Submit',
              'Final review before submitting the application'),
          const SizedBox(height: 20),

          // Summary card
          _buildSummaryCard(state),
          const SizedBox(height: 20),

          // Application source
          const Text('How was this customer acquired?',
              style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppConstants.textPrimary)),
          const SizedBox(height: 10),
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: 10,
            mainAxisSpacing: 10,
            childAspectRatio: 2.5,
            children: _sourceOptions.map((opt) {
              final selected = state.applicationSource == opt.$1;
              return GestureDetector(
                onTap: () => ref
                    .read(kycProvider.notifier)
                    .update((s) =>
                        s.copyWith(applicationSource: opt.$1)),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  padding: const EdgeInsets.symmetric(
                      horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: selected
                        ? AppConstants.primarySurface
                        : AppConstants.borderLight,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: selected
                          ? AppConstants.primary
                          : AppConstants.border,
                      width: selected ? 1.5 : 1,
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(opt.$3,
                          size: 16,
                          color: selected
                              ? AppConstants.primary
                              : AppConstants.textSecondary),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          opt.$2,
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: selected
                                ? FontWeight.w600
                                : FontWeight.w400,
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
          const SizedBox(height: 20),

          // FO notes
          const Text('FO Notes',
              style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                  color: AppConstants.textPrimary)),
          const SizedBox(height: 6),
          Row(children: [
            const SizedBox(width: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                  color: AppConstants.borderLight,
                  borderRadius: BorderRadius.circular(4)),
              child: const Text('Optional',
                  style: TextStyle(
                      fontSize: 9,
                      color: AppConstants.textHint,
                      fontWeight: FontWeight.w500)),
            ),
          ]),
          const SizedBox(height: 6),
          TextFormField(
            controller: _notesCtrl,
            maxLines: 3,
            decoration: const InputDecoration(
              hintText: 'Any additional notes about the customer...',
            ),
          ),

          const SizedBox(height: 32),

          // Warning
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF7ED),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(
                  color: AppConstants.warning.withOpacity(0.3)),
            ),
            child: const Row(
              children: [
                Icon(Icons.warning_amber_rounded,
                    color: AppConstants.warning, size: 18),
                SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Once submitted, the application will go to auto-check and then team review. Make sure all data is correct.',
                    style: TextStyle(
                        fontSize: 11,
                        color: AppConstants.textSecondary),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),
          AppButton(
            label: 'Submit Application',
            width: double.infinity,
            isLoading: state.isSubmitting,
            icon: Icons.send_rounded,
            onPressed: _submit,
          ),

          const SizedBox(height: 12),
          AppButton(
            label: 'Save as Draft',
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

  Widget _buildSummaryCard(KycDraftState s) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppConstants.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppConstants.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Application Summary',
              style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppConstants.textPrimary)),
          const SizedBox(height: 12),
          _row('Customer', '${s.firstName} ${s.lastName}'),
          _row('Phone', s.phone),
          _row('IMEI', s.imeiNumber),
          _row('Device', s.deviceSpecs),
          _row('Cash Price', 'TZS ${s.cashPrice}'),
          _row('Deposit', 'TZS ${s.depositAmount}'),
          _row('Income', 'TZS ${s.monthlyIncome}'),
          _row('NOK', s.nokName),
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    if (value.trim().isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          SizedBox(
            width: 100,
            child: Text(label,
                style: const TextStyle(
                    fontSize: 12, color: AppConstants.textSecondary)),
          ),
          Expanded(
            child: Text(value,
                style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: AppConstants.textPrimary)),
          ),
        ],
      ),
    );
  }

  Widget _buildSuccessView(BuildContext context) {
    final autoCheck = _result?['auto_check_status']?.toString() ?? 'pending';
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Animated success ring
            AnimatedBuilder(
              animation: _successAnim,
              builder: (_, __) => Opacity(
                opacity: _ringOpacity.value,
                child: Transform.scale(
                  scale: _ringScale.value,
                  child: Container(
                    width: 100,
                    height: 100,
                    decoration: BoxDecoration(
                      color: const Color(0xFFECFDF5),
                      shape: BoxShape.circle,
                      border: Border.all(
                          color: AppConstants.success, width: 3),
                    ),
                    child: const Icon(Icons.check_rounded,
                        color: AppConstants.success, size: 48),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Application Submitted!',
              style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: AppConstants.textPrimary),
            ),
            const SizedBox(height: 8),
            Text(
              'Auto-check status: ${autoCheck.toUpperCase()}',
              style: const TextStyle(
                  fontSize: 14, color: AppConstants.textSecondary),
            ),
            const SizedBox(height: 32),
            AppButton(
              label: 'View Customers',
              width: double.infinity,
              icon: Icons.people_rounded,
              onPressed: () => context.go('/customers'),
            ),
            const SizedBox(height: 12),
            AppButton(
              label: 'Register Another',
              width: double.infinity,
              outlined: true,
              icon: Icons.person_add_rounded,
              onPressed: () => context.go('/kyc/new'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionHeader(String title, String subtitle) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title,
              style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: AppConstants.textPrimary)),
          const SizedBox(height: 2),
          Text(subtitle,
              style: const TextStyle(
                  fontSize: 12, color: AppConstants.textSecondary)),
        ],
      );
}
