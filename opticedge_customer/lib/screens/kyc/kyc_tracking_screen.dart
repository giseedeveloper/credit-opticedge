import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../config/constants.dart';
import '../../config/customer_colors.dart';
import '../../core/models/kyc_progress.dart';
import '../../core/providers/kyc_tracking_provider.dart';
import '../../widgets/common/app_brand_logo.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class KycTrackingScreen extends ConsumerStatefulWidget {
  const KycTrackingScreen({super.key, this.initialPhone});

  final String? initialPhone;

  @override
  ConsumerState<KycTrackingScreen> createState() => _KycTrackingScreenState();
}

class _KycTrackingScreenState extends ConsumerState<KycTrackingScreen>
    with SingleTickerProviderStateMixin {
  final _phoneCtrl = TextEditingController();
  late final AnimationController _anim;

  @override
  void initState() {
    super.initState();
    _anim = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..forward();

    final phone = widget.initialPhone?.trim();
    if (phone != null && phone.isNotEmpty) {
      _phoneCtrl.text = phone;
      Future.microtask(() => _load(phone));
    }
  }

  @override
  void dispose() {
    _anim.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  Future<void> _load(String phone) async {
    try {
      await ref.read(kycTrackingProvider.notifier).loadByPhone(phone.trim());
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final tracking = ref.watch(kycTrackingProvider);
    final cc = CustomerColors.of(context);

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: PremiumGlassBackground(
        child: SafeArea(
          child: RefreshIndicator(
            color: AppConstants.primary,
            onRefresh: () => ref.read(kycTrackingProvider.notifier).refresh(),
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
                    child: Row(
                      children: [
                        IconButton(
                          onPressed: () => context.go('/login'),
                          icon: Icon(Icons.arrow_back_rounded,
                              color: cc.textPrimary),
                        ),
                        const Expanded(
                          child: Text(
                            'Fuatilia KYC',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        FadeTransition(
                          opacity: _anim,
                          child: const AppBrandLogo(size: 72),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Angalia hatua ya maombi yako',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 14,
                            color: cc.textSecondary,
                            height: 1.5,
                          ),
                        ),
                        const SizedBox(height: 20),
                        GlassCard.surface(
                          context,
                          borderRadius: BorderRadius.circular(22),
                          padding: const EdgeInsets.all(18),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              TextField(
                                controller: _phoneCtrl,
                                keyboardType: TextInputType.phone,
                                decoration: InputDecoration(
                                  labelText: 'Namba ya simu',
                                  prefixIcon: Icon(Icons.phone_rounded,
                                      color: AppConstants.primary),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(14),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 14),
                              AppButton(
                                label: tracking.isLoading
                                    ? 'Inatafuta...'
                                    : 'Angalia hali',
                                isLoading: tracking.isLoading,
                                onPressed: tracking.isLoading
                                    ? null
                                    : () => _load(_phoneCtrl.text),
                              ),
                            ],
                          ),
                        ),
                        if (tracking.error != null) ...[
                          const SizedBox(height: 16),
                          _ErrorBanner(message: tracking.error!),
                        ],
                        if (tracking.snapshot != null) ...[
                          const SizedBox(height: 22),
                          _ProgressHero(snapshot: tracking.snapshot!),
                          const SizedBox(height: 18),
                          _StageTimeline(snapshot: tracking.snapshot!),
                          if (tracking.snapshot!.verification != null) ...[
                            const SizedBox(height: 18),
                            _VerificationDetails(
                              verification: tracking.snapshot!.verification!,
                            ),
                          ],
                          const SizedBox(height: 18),
                          if (tracking.snapshot!.isPortalActive)
                            AppButton(
                              label: 'Endelea kuingia',
                              onPressed: () => context.go('/login'),
                            ),
                        ],
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

class _ProgressHero extends StatelessWidget {
  const _ProgressHero({required this.snapshot});

  final KycProgressSnapshot snapshot;

  @override
  Widget build(BuildContext context) {
    final cc = CustomerColors.of(context);
    final isRejected = snapshot.isRejected;
    final accent = isRejected
        ? AppConstants.error
        : snapshot.isPortalActive
            ? AppConstants.success
            : AppConstants.warning;

    return GlassCard.tinted(
      surfaceTint: accent.withValues(alpha: 0.08),
      accent: accent,
      borderRadius: BorderRadius.circular(26),
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Icon(
              isRejected
                  ? Icons.cancel_rounded
                  : snapshot.isPortalActive
                      ? Icons.verified_rounded
                      : Icons.hourglass_top_rounded,
              color: accent,
              size: 34,
            ),
          ),
          const SizedBox(height: 14),
          if (snapshot.customerName != null)
            Text(
              'Habari ${snapshot.customerName}',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: cc.textPrimary,
              ),
            ),
          const SizedBox(height: 8),
          Text(
            snapshot.portalMessage,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13,
              color: cc.textSecondary,
              height: 1.55,
            ),
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            alignment: WrapAlignment.center,
            children: [
              _Chip(
                label: 'KYC',
                value: snapshot.kycStatus ?? '—',
                color: accent,
              ),
              if (snapshot.kycStage != null)
                _Chip(
                  label: 'Hatua',
                  value: '${snapshot.kycStage}/4',
                  color: AppConstants.info,
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StageTimeline extends StatelessWidget {
  const _StageTimeline({required this.snapshot});

  final KycProgressSnapshot snapshot;

  @override
  Widget build(BuildContext context) {
    final cc = CustomerColors.of(context);
    final current = snapshot.kycStage ?? snapshot.verification?.stage ?? 1;
    final v = snapshot.verification;

    final stages = [
      _StageMeta(1, 'Ukaguzi wa nyaraka', v?.stage1Status),
      _StageMeta(2, 'Uthibitishaji wa mteja', v?.stage2Status),
      _StageMeta(3, 'Simu ya uthibitisho', v?.stage3Status),
      _StageMeta(4, 'Simu ya ndugu', v?.stage4Status),
    ];

    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(22),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Hatua za idhini',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: cc.textPrimary,
            ),
          ),
          const SizedBox(height: 16),
          ...stages.map((s) => _TimelineRow(
                meta: s,
                isActive: s.number == current,
                isComplete: _isApproved(s.status) || s.number < current,
              )),
        ],
      ),
    );
  }

  bool _isApproved(String? status) =>
      status == 'approved' || status == 'manual_verified';
}

class _StageMeta {
  final int number;
  final String title;
  final String? status;

  _StageMeta(this.number, this.title, this.status);
}

class _TimelineRow extends StatelessWidget {
  const _TimelineRow({
    required this.meta,
    required this.isActive,
    required this.isComplete,
  });

  final _StageMeta meta;
  final bool isActive;
  final bool isComplete;

  @override
  Widget build(BuildContext context) {
    final cc = CustomerColors.of(context);
    final color = meta.status == 'rejected'
        ? AppConstants.error
        : isComplete
            ? AppConstants.success
            : isActive
                ? AppConstants.primary
                : cc.textSecondary.withValues(alpha: 0.5);

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.15),
              shape: BoxShape.circle,
              border: Border.all(color: color, width: isActive ? 2 : 1),
            ),
            alignment: Alignment.center,
            child: Text(
              '${meta.number}',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 12,
                color: color,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  meta.title,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: cc.textPrimary,
                  ),
                ),
                if (meta.status != null)
                  Text(
                    _statusLabel(meta.status!),
                    style: TextStyle(fontSize: 12, color: color),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _statusLabel(String status) {
    return switch (status) {
      'approved' => 'Imeidhinishwa',
      'rejected' => 'Imekataliwa',
      'pending' => 'Inasubiri',
      _ => status,
    };
  }
}

class _VerificationDetails extends StatelessWidget {
  const _VerificationDetails({required this.verification});

  final KycVerificationProgress verification;

  @override
  Widget build(BuildContext context) {
    final cc = CustomerColors.of(context);
    final face = verification.faceMatchStatus;

    if (face == null) {
      return const SizedBox.shrink();
    }

    final faceColor = switch (face) {
      'passed' || 'manual_verified' => AppConstants.success,
      'failed' => AppConstants.error,
      _ => AppConstants.warning,
    };

    return GlassCard.surface(
      context,
      borderRadius: BorderRadius.circular(18),
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Icon(Icons.face_retouching_natural_rounded, color: faceColor),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Uthibitishaji wa uso',
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: cc.textPrimary,
                  ),
                ),
                Text(
                  face.replaceAll('_', ' '),
                  style: TextStyle(fontSize: 12, color: faceColor),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Text(
        '$label: $value',
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppConstants.error.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppConstants.error.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded,
              color: AppConstants.error, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(message, style: const TextStyle(fontSize: 13)),
          ),
        ],
      ),
    );
  }
}
