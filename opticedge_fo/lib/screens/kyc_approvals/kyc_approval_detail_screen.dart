import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../core/providers/kyc_approval_provider.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class KycApprovalDetailScreen extends ConsumerStatefulWidget {
  const KycApprovalDetailScreen({super.key, required this.customerId});

  final String customerId;

  @override
  ConsumerState<KycApprovalDetailScreen> createState() =>
      _KycApprovalDetailScreenState();
}

class _KycApprovalDetailScreenState
    extends ConsumerState<KycApprovalDetailScreen> {
  final _notesCtrl = TextEditingController();
  final _reasonCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref
          .read(kycApprovalDetailProvider(widget.customerId).notifier)
          .load(widget.customerId);
    });
  }

  @override
  void dispose() {
    _notesCtrl.dispose();
    _reasonCtrl.dispose();
    super.dispose();
  }

  int get _stage =>
      ref.read(kycApprovalDetailProvider(widget.customerId)).detail?.kycStage ??
      1;

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycApprovalDetailProvider(widget.customerId));
    final detail = state.detail;

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: PremiumGlassBackground(
        child: SafeArea(
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                child: Row(
                  children: [
                    IconButton(
                      onPressed: () => context.pop(),
                      icon: const Icon(Icons.arrow_back_rounded),
                    ),
                    const Expanded(
                      child: Text(
                        'KYC Review',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: state.isLoading && detail == null
                    ? const Center(child: CircularProgressIndicator())
                    : detail == null
                        ? Center(child: Text(state.error ?? 'Not found'))
                        : RefreshIndicator(
                            onRefresh: () => ref
                                .read(kycApprovalDetailProvider(
                                        widget.customerId)
                                    .notifier)
                                .load(widget.customerId),
                            child: ListView(
                              padding: const EdgeInsets.all(16),
                              children: [
                                GlassCard.surface(
                                  context,
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        detail.fullName,
                                        style: const TextStyle(
                                          fontSize: 20,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      _infoRow('Simu', detail.phone),
                                      if (detail.nidaNumber != null)
                                        _infoRow('NIDA', detail.nidaNumber!),
                                      _infoRow('KYC', detail.kycStatus ?? '—'),
                                      _infoRow(
                                        'Hatua',
                                        '${detail.kycStage ?? 1} / 4',
                                      ),
                                      if (detail.verification?[
                                              'face_match_status'] !=
                                          null)
                                        _infoRow(
                                          'Face match',
                                          detail.verification![
                                                  'face_match_status']
                                              .toString(),
                                        ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 16),
                                if (state.error != null)
                                  Padding(
                                    padding: const EdgeInsets.only(bottom: 12),
                                    child: Text(
                                      state.error!,
                                      style: const TextStyle(
                                        color: AppConstants.error,
                                      ),
                                    ),
                                  ),
                                ..._buildActions(state),
                              ],
                            ),
                          ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  List<Widget> _buildActions(KycApprovalDetailState state) {
    final stage = _stage;
    final widgets = <Widget>[];

    if (stage <= 2) {
      widgets.addAll([
        TextField(
          controller: _notesCtrl,
          decoration: const InputDecoration(
            labelText: 'Maelezo (optional)',
            border: OutlineInputBorder(),
          ),
          maxLines: 2,
        ),
        const SizedBox(height: 12),
        AppButton(
          label: 'Idhinisha Hatua $stage',
          isLoading: state.isSubmitting,
          onPressed: state.isSubmitting
              ? null
              : () => _approveStage(stage),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _reasonCtrl,
          decoration: const InputDecoration(
            labelText: 'Sababu ya kukataa',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 10),
        OutlinedButton(
          onPressed: state.isSubmitting
              ? null
              : () => _rejectStage(stage),
          child: Text('Kataa Hatua $stage'),
        ),
      ]);
    }

    if (stage == 3) {
      widgets.addAll([
        AppButton(
          label: 'Simu: Mteja amethibitisha',
          isLoading: state.isSubmitting,
          onPressed: state.isSubmitting
              ? null
              : () => _recordCall(confirmed: true, isNok: false),
        ),
        const SizedBox(height: 10),
        OutlinedButton(
          onPressed: state.isSubmitting
              ? null
              : () => _recordCall(confirmed: false, isNok: false),
          child: const Text('Simu: Hajathibitishwa'),
        ),
      ]);
    }

    if (stage == 4) {
      widgets.addAll([
        AppButton(
          label: 'NOK: Amethibitisha (KYC kamili)',
          isLoading: state.isSubmitting,
          onPressed: state.isSubmitting
              ? null
              : () => _recordCall(confirmed: true, isNok: true),
        ),
        const SizedBox(height: 10),
        OutlinedButton(
          onPressed: state.isSubmitting
              ? null
              : () => _recordCall(confirmed: false, isNok: true),
          child: const Text('NOK: Hajathibitishwa'),
        ),
      ]);
    }

    final face = state.detail?.verification?['face_match_status']?.toString();
    if (face == 'review' || face == 'failed') {
      widgets.addAll([
        const SizedBox(height: 16),
        AppButton(
          label: 'Thibitisha uso kwa mkono',
          isLoading: state.isSubmitting,
          onPressed: state.isSubmitting ? null : _manualFace,
        ),
      ]);
    }

    return widgets;
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          SizedBox(
            width: 90,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: Theme.of(context).hintColor,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _approveStage(int stage) async {
    final ok = await ref
        .read(kycApprovalDetailProvider(widget.customerId).notifier)
        .approveStage(
          widget.customerId,
          stage,
          notes: _notesCtrl.text.trim().isEmpty ? null : _notesCtrl.text.trim(),
        );
    if (ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Imeidhinishwa')),
      );
      ref.read(kycApprovalListProvider.notifier).load();
    }
  }

  Future<void> _rejectStage(int stage) async {
    if (_reasonCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Weka sababu ya kukataa')),
      );
      return;
    }
    final ok = await ref
        .read(kycApprovalDetailProvider(widget.customerId).notifier)
        .rejectStage(
          widget.customerId,
          stage,
          reason: _reasonCtrl.text.trim(),
          notes: _notesCtrl.text.trim().isEmpty ? null : _notesCtrl.text.trim(),
        );
    if (ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Imekataliwa')),
      );
      ref.read(kycApprovalListProvider.notifier).load();
    }
  }

  Future<void> _recordCall({required bool confirmed, required bool isNok}) async {
    final notifier =
        ref.read(kycApprovalDetailProvider(widget.customerId).notifier);
    final ok = isNok
        ? await notifier.recordNokCall(
            widget.customerId,
            outcome: confirmed ? 'confirmed' : 'not_confirmed',
            notes: _notesCtrl.text.trim().isEmpty ? null : _notesCtrl.text.trim(),
          )
        : await notifier.recordConfirmationCall(
            widget.customerId,
            outcome: confirmed ? 'confirmed' : 'not_confirmed',
            notes: _notesCtrl.text.trim().isEmpty ? null : _notesCtrl.text.trim(),
          );
    if (ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(confirmed ? 'Imethibitishwa' : 'Imekataliwa')),
      );
      ref.read(kycApprovalListProvider.notifier).load();
    }
  }

  Future<void> _manualFace() async {
    final ok = await ref
        .read(kycApprovalDetailProvider(widget.customerId).notifier)
        .manualVerifyFace(widget.customerId);
    if (ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Face match imethibitishwa')),
      );
    }
  }
}
