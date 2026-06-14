import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/models/kyc_approval_models.dart';
import '../../core/providers/kyc_approval_provider.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';
import '../../widgets/common/status_badge.dart';

class KycApprovalQueueScreen extends ConsumerStatefulWidget {
  const KycApprovalQueueScreen({super.key, this.initialStage});

  final int? initialStage;

  @override
  ConsumerState<KycApprovalQueueScreen> createState() =>
      _KycApprovalQueueScreenState();
}

class _KycApprovalQueueScreenState extends ConsumerState<KycApprovalQueueScreen> {
  final _searchCtrl = TextEditingController();
  Timer? _debounce;

  static const _stages = <_StageMeta>[
    _StageMeta(
      stage: 1,
      label: 'Stage 1',
      caption: 'Documents',
      color: AppConstants.primary,
      surfaceLight: Color(0xFFFFF4EC),
      surfaceDark: DesignTokens.statRegisterBgDark,
    ),
    _StageMeta(
      stage: 2,
      label: 'Stage 2',
      caption: 'Review',
      color: DesignTokens.statBlue,
      surfaceLight: DesignTokens.statBlueBg,
      surfaceDark: DesignTokens.statBlueBgDark,
    ),
    _StageMeta(
      stage: 3,
      label: 'Calls',
      caption: 'Confirmation',
      color: DesignTokens.statViolet,
      surfaceLight: DesignTokens.statVioletBg,
      surfaceDark: DesignTokens.statVioletBgDark,
    ),
    _StageMeta(
      stage: 4,
      label: 'NOK',
      caption: 'Next of kin',
      color: DesignTokens.statAmber,
      surfaceLight: DesignTokens.statAmberBg,
      surfaceDark: DesignTokens.statAmberBgDark,
    ),
  ];

  @override
  void initState() {
    super.initState();
    final stage = (widget.initialStage ?? 1).clamp(1, 4);
    Future.microtask(() {
      ref.read(kycApprovalListProvider.notifier).load(stage: stage);
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onSearch(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      ref.read(kycApprovalListProvider.notifier).load(search: value.trim());
    });
  }

  void _selectStage(int stage) {
    ref.read(kycApprovalListProvider.notifier).load(stage: stage);
  }

  _StageMeta _metaForStage(int stage) {
    return _stages.firstWhere(
      (s) => s.stage == stage,
      orElse: () => _stages.first,
    );
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kycApprovalListProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final stageMeta = _metaForStage(state.stage);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.go('/dashboard'),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'KYC Approvals',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w800,
                fontSize: 18,
              ),
            ),
            Text(
              'HQ queue — approve by stage',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: isDark
                    ? Colors.white.withValues(alpha: 0.62)
                    : AppConstants.textSecondary,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () =>
                ref.read(kycApprovalListProvider.notifier).load(),
          ),
        ],
      ),
      body: PremiumGlassBackground(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 10),
              child: GlassCard(
                tint: isDark
                    ? DesignTokens.darkSurfaceElevated
                    : Colors.white,
                borderRadius: BorderRadius.circular(18),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
                borderColor: isDark
                    ? DesignTokens.darkBorder
                    : AppConstants.border,
                child: TextField(
                  controller: _searchCtrl,
                  onChanged: (value) {
                    setState(() {});
                    _onSearch(value);
                  },
                  style: GoogleFonts.plusJakartaSans(fontSize: 14),
                  decoration: InputDecoration(
                    hintText: 'Tafuta jina, simu, NIDA...',
                    hintStyle: GoogleFonts.plusJakartaSans(
                      fontSize: 13,
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.45)
                          : AppConstants.textHint,
                    ),
                    prefixIcon: const Icon(
                      Icons.search_rounded,
                      size: 22,
                      color: AppConstants.primary,
                    ),
                    suffixIcon: _searchCtrl.text.isNotEmpty
                        ? IconButton(
                            icon: Icon(
                              Icons.close_rounded,
                              size: 20,
                              color: isDark
                                  ? Colors.white.withValues(alpha: 0.7)
                                  : AppConstants.textSecondary,
                            ),
                            onPressed: () {
                              _debounce?.cancel();
                              _searchCtrl.clear();
                              setState(() {});
                              ref
                                  .read(kycApprovalListProvider.notifier)
                                  .load(search: '');
                            },
                          )
                        : null,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 4,
                      vertical: 12,
                    ),
                    border: InputBorder.none,
                  ),
                ),
              ),
            ),
            _StageFilterRow(
              stages: _stages,
              selectedStage: state.stage,
              counts: state.stageCounts,
              isDark: isDark,
              onSelect: _selectStage,
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 6),
              child: Row(
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: stageMeta.color,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '${stageMeta.label} · ${stageMeta.caption}',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: stageMeta.color,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    '${state.items.length} pending',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.55)
                          : AppConstants.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppConstants.primary,
                onRefresh: () =>
                    ref.read(kycApprovalListProvider.notifier).load(),
                child: _buildBody(state, isDark, stageMeta),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBody(
    KycApprovalListState state,
    bool isDark,
    _StageMeta stageMeta,
  ) {
    if (state.isLoading && state.items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 120),
          Center(
            child: CircularProgressIndicator(color: AppConstants.primary),
          ),
        ],
      );
    }
    if (state.error != null && state.items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          Padding(
            padding: const EdgeInsets.all(24),
            child: GlassCard.surface(
              context,
              child: Column(
                children: [
                  Icon(
                    Icons.cloud_off_rounded,
                    size: 40,
                    color: isDark
                        ? Colors.white.withValues(alpha: 0.5)
                        : AppConstants.textSecondary,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    state.error!,
                    textAlign: TextAlign.center,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 14,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 16),
                  FilledButton.icon(
                    onPressed: () =>
                        ref.read(kycApprovalListProvider.notifier).load(),
                    icon: const Icon(Icons.refresh_rounded, size: 18),
                    label: const Text('Jaribu tena'),
                  ),
                ],
              ),
            ),
          ),
        ],
      );
    }
    if (state.items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          const SizedBox(height: 48),
          Center(
            child: Column(
              children: [
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    color: stageMeta.surface(isDark),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: stageMeta.color.withValues(alpha: 0.35),
                    ),
                  ),
                  child: Icon(
                    Icons.inbox_rounded,
                    size: 34,
                    color: stageMeta.color,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Hakuna maombi kwenye hatua hii',
                  style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Maombi mapya yataonekana hapa.',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 13,
                    color: isDark
                        ? Colors.white.withValues(alpha: 0.55)
                        : AppConstants.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      );
    }

    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
      itemCount: state.items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, index) {
        final item = state.items[index];
        return _QueueTile(
          item: item,
          accent: stageMeta.color,
          onTap: () => context.go('/kyc/approvals/${item.id}'),
        );
      },
    );
  }
}

class _StageMeta {
  const _StageMeta({
    required this.stage,
    required this.label,
    required this.caption,
    required this.color,
    required this.surfaceLight,
    required this.surfaceDark,
  });

  final int stage;
  final String label;
  final String caption;
  final Color color;
  final Color surfaceLight;
  final Color surfaceDark;

  Color surface(bool isDark) => isDark ? surfaceDark : surfaceLight;
}

class _StageFilterRow extends StatelessWidget {
  const _StageFilterRow({
    required this.stages,
    required this.selectedStage,
    required this.counts,
    required this.isDark,
    required this.onSelect,
  });

  final List<_StageMeta> stages;
  final int selectedStage;
  final Map<int, int> counts;
  final bool isDark;
  final ValueChanged<int> onSelect;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 78,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: stages.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final meta = stages[index];
          final selected = meta.stage == selectedStage;
          final count = counts[meta.stage] ?? 0;

          return _StageChip(
            meta: meta,
            count: count,
            selected: selected,
            isDark: isDark,
            onTap: () => onSelect(meta.stage),
          );
        },
      ),
    );
  }
}

class _StageChip extends StatelessWidget {
  const _StageChip({
    required this.meta,
    required this.count,
    required this.selected,
    required this.isDark,
    required this.onTap,
  });

  final _StageMeta meta;
  final int count;
  final bool selected;
  final bool isDark;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final labelColor = selected
        ? Colors.white
        : (isDark ? Colors.white.withValues(alpha: 0.88) : meta.color);
    final captionColor = selected
        ? Colors.white.withValues(alpha: 0.88)
        : (isDark
            ? Colors.white.withValues(alpha: 0.55)
            : AppConstants.textSecondary);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOutCubic,
          width: 118,
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
          decoration: BoxDecoration(
            gradient: selected
                ? LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      meta.color,
                      Color.lerp(meta.color, Colors.black, 0.12)!,
                    ],
                  )
                : null,
            color: selected ? null : meta.surface(isDark),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected
                  ? meta.color.withValues(alpha: 0.9)
                  : meta.color.withValues(alpha: isDark ? 0.45 : 0.28),
              width: selected ? 1.5 : 1,
            ),
            boxShadow: selected
                ? [
                    BoxShadow(
                      color: meta.color.withValues(alpha: 0.32),
                      blurRadius: 14,
                      offset: const Offset(0, 6),
                    ),
                  ]
                : null,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      meta.label,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: labelColor,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 7,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: selected
                          ? Colors.white.withValues(alpha: 0.22)
                          : meta.color.withValues(alpha: isDark ? 0.22 : 0.14),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '$count',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        color: labelColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                meta.caption,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: captionColor,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QueueTile extends StatelessWidget {
  const _QueueTile({
    required this.item,
    required this.accent,
    required this.onTap,
  });

  final KycApprovalQueueItem item;
  final Color accent;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: GlassCard.surface(
          context,
          borderRadius: BorderRadius.circular(20),
          padding: EdgeInsets.zero,
          child: Row(
            children: [
              Container(
                width: 5,
                height: 72,
                decoration: BoxDecoration(
                  color: accent,
                  borderRadius: const BorderRadius.horizontal(
                    left: Radius.circular(20),
                  ),
                ),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 14, 12, 14),
                  child: Row(
                    children: [
                      Container(
                        width: 48,
                        height: 48,
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [
                              accent,
                              Color.lerp(accent, Colors.black, 0.15)!,
                            ],
                          ),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        alignment: Alignment.center,
                        child: Text(
                          item.fullName.isNotEmpty
                              ? item.fullName[0].toUpperCase()
                              : '?',
                          style: GoogleFonts.plusJakartaSans(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 18,
                          ),
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              item.fullName,
                              style: GoogleFonts.plusJakartaSans(
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              item.phone,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 12,
                                color: isDark
                                    ? Colors.white.withValues(alpha: 0.55)
                                    : AppConstants.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const StatusBadge(status: 'pending', small: true),
                          if (item.faceMatchStatus != null) ...[
                            const SizedBox(height: 6),
                            Text(
                              item.faceMatchStatus!,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: AppConstants.warning,
                              ),
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(width: 4),
                      Icon(
                        Icons.chevron_right_rounded,
                        color: isDark
                            ? Colors.white.withValues(alpha: 0.45)
                            : AppConstants.textHint,
                      ),
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
}
