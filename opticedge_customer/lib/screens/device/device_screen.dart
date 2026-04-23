import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../config/customer_colors.dart';
import '../../core/api/api_client.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class _DeviceState {
  final Map<String, dynamic>? data;
  final bool isLoading;
  final String? error;
  const _DeviceState({this.data, this.isLoading = false, this.error});
}

class _DeviceNotifier extends StateNotifier<_DeviceState> {
  _DeviceNotifier() : super(const _DeviceState());

  Future<void> load() async {
    state = const _DeviceState(isLoading: true);
    try {
      final res = await ApiClient.instance.get('/device');
      state = _DeviceState(data: res.data['data'] as Map<String, dynamic>?);
    } catch (e) {
      state = _DeviceState(error: ApiClient.parseError(e));
    }
  }
}

final _deviceProvider = StateNotifierProvider<_DeviceNotifier, _DeviceState>((
  ref,
) {
  return _DeviceNotifier();
});

class DeviceScreen extends ConsumerStatefulWidget {
  const DeviceScreen({super.key});

  @override
  ConsumerState<DeviceScreen> createState() => _DeviceScreenState();
}

class _DeviceScreenState extends ConsumerState<DeviceScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(_deviceProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(_deviceProvider);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: const Text(
          'Kifaa Changu',
          style: TextStyle(fontWeight: FontWeight.w800, letterSpacing: -0.4),
        ),
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
      ),
      body: PremiumGlassBackground(
        child: state.isLoading
          ? const Center(
              child: CircularProgressIndicator(color: AppConstants.primary),
            )
          : state.error != null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 64,
                      height: 64,
                      decoration: BoxDecoration(
                        color: AppConstants.error.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Icon(
                        Icons.wifi_off_rounded,
                        color: AppConstants.error,
                        size: 32,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      state.error!,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: CustomerColors.of(context).textPrimary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton.icon(
                      onPressed: () =>
                          ref.read(_deviceProvider.notifier).load(),
                      icon: const Icon(Icons.refresh_rounded, size: 18),
                      label: const Text('Jaribu Tena'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppConstants.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                        elevation: 0,
                      ),
                    ),
                  ],
                ),
              ),
            )
          : state.data == null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF7F3FF),
                      borderRadius: BorderRadius.circular(22),
                    ),
                    child: const Icon(
                      Icons.phone_android_rounded,
                      color: Color(0xFF8B5CF6),
                      size: 36,
                    ),
                  ),
                  const SizedBox(height: 18),
                  Text(
                    'Hakuna Taarifa za Kifaa',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: CustomerColors.of(context).textPrimary,
                    ),
                  ),
                ],
              ),
            )
          : RefreshIndicator(
              color: AppConstants.primary,
              onRefresh: () => ref.read(_deviceProvider.notifier).load(),
              child: _buildContent(context, state.data!),
            ),
      ),
    );
  }

  Widget _buildContent(BuildContext context, Map<String, dynamic> d) {
    final brand = d['brand'] as Map<String, dynamic>?;
    final model = d['model'] as Map<String, dynamic>?;
    final cc = CustomerColors.of(context);

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      children: [
        GlassCard.tinted(
          surfaceTint: cc.isDark ? const Color(0xFF231A30) : const Color(0xFFF7F3FF),
          accent: const Color(0xFF8B5CF6),
          borderRadius: BorderRadius.circular(26),
          padding: const EdgeInsets.all(28),
          child: Column(
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: const Color(0xFF8B5CF6).withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: const Icon(
                  Icons.phone_android_rounded,
                  size: 40,
                  color: Color(0xFF8B5CF6),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                '${brand?['name'] ?? ''} ${model?['name'] ?? ''}',
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: cc.textPrimary,
                  letterSpacing: -0.4,
                ),
                textAlign: TextAlign.center,
              ),
              if (model?['storage'] != null || model?['ram'] != null) ...[
                const SizedBox(height: 6),
                Text(
                  [
                    if (model?['storage'] != null) model!['storage'],
                    if (model?['ram'] != null) '${model!['ram']} RAM',
                  ].join(' • '),
                  style: TextStyle(
                    color: cc.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 18),

        GlassCard.surface(
          context,
          borderRadius: BorderRadius.circular(24),
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: AppConstants.info.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(
                      Icons.info_outline_rounded,
                      color: AppConstants.info,
                      size: 18,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    'Taarifa za Kifaa',
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 16,
                      color: cc.textPrimary,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _InfoRow('IMEI', d['imei'] ?? '-'),
              if (d['imei_2'] != null) _InfoRow('IMEI 2', d['imei_2']),
              if (d['serial_number'] != null)
                _InfoRow('Serial Number', d['serial_number']),
              _InfoRow(
                'Bei ya Pesa Taslimu',
                'TZS ${_fmtAmount(d['cash_price'])}',
              ),
              _InfoRow('Amana', 'TZS ${_fmtAmount(d['deposit_amount'])}'),
              _InfoRow('Malipo', _repaymentLabel(d['preferred_repayment'])),
              if (d['loan_interest_rate'] != null)
                _InfoRow(
                  'Interest',
                  '${d['loan_interest_rate']}% ${_interestTypeLabel(d['loan_interest_type'])}',
                ),
              if (d['loan_duration_weeks'] != null)
                _InfoRow('Duration', '${d['loan_duration_weeks']} weeks'),
              if (d['loan_grace_period_days'] != null)
                _InfoRow('Grace Period', '${d['loan_grace_period_days']} days'),
              _InfoRow(
                'Hali',
                d['asset_release_status'] == 'released'
                    ? 'Imepewa'
                    : 'Inasubiri',
              ),
              if (d['asset_released_at'] != null)
                _InfoRow('Tarehe ya Kupewa', d['asset_released_at']),
            ],
          ),
        ),
        const SizedBox(height: 18),

        if (d['agreement'] != null)
          GlassCard.surface(
            context,
            borderRadius: BorderRadius.circular(20),
            padding: EdgeInsets.zero,
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 6,
              ),
              leading: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppConstants.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.description_rounded,
                  color: AppConstants.primary,
                  size: 20,
                ),
              ),
              title: Text(
                d['agreement']['title'] ?? 'Mkataba',
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                ),
              ),
              subtitle: const Text(
                'Bonyeza kuona mkataba',
                style: TextStyle(fontSize: 12),
              ),
              trailing: const Icon(
                Icons.open_in_new,
                size: 16,
                color: AppConstants.textHint,
              ),
              onTap: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Mkataba utafunguliwa hivi karibuni'),
                  ),
                );
              },
            ),
          ),
      ],
    );
  }

  String _fmtAmount(dynamic v) {
    if (v == null) return '0';
    final d = double.tryParse(v.toString()) ?? 0;
    return d
        .toStringAsFixed(0)
        .replaceAllMapped(RegExp(r'(\d)(?=(\d{3})+(?!\d))'), (m) => '${m[1]},');
  }

  String _repaymentLabel(dynamic v) {
    return switch (v) {
      'weekly' => 'Kila Wiki',
      'biweekly' => 'Kila Wiki 2',
      'monthly' => 'Kila Mwezi',
      _ => v?.toString() ?? '-',
    };
  }

  String _interestTypeLabel(dynamic value) {
    return switch (value) {
      'reducing_balance' => 'Reducing',
      'flat' => 'Flat',
      _ => value?.toString() ?? '-',
    };
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Text(
              label,
              style: TextStyle(
                color: CustomerColors.of(context).textSecondary,
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: CustomerColors.of(context).textPrimary,
              ),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }
}
