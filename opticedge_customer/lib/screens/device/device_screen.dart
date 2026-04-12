import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../core/api/api_client.dart';

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

final _deviceProvider = StateNotifierProvider<_DeviceNotifier, _DeviceState>((ref) {
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
      appBar: AppBar(title: const Text('Kifaa Changu')),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : state.error != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(state.error!, style: const TextStyle(color: AppConstants.danger)),
                      const SizedBox(height: 8),
                      TextButton(
                        onPressed: () => ref.read(_deviceProvider.notifier).load(),
                        child: const Text('Jaribu Tena'),
                      ),
                    ],
                  ),
                )
              : state.data == null
                  ? const Center(child: Text('Hakuna taarifa za kifaa'))
                  : RefreshIndicator(
                      onRefresh: () => ref.read(_deviceProvider.notifier).load(),
                      child: _buildContent(context, state.data!),
                    ),
    );
  }

  Widget _buildContent(BuildContext context, Map<String, dynamic> d) {
    final brand = d['brand'] as Map<String, dynamic>?;
    final model = d['model'] as Map<String, dynamic>?;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Device header
        Card(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: AppConstants.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Icon(Icons.phone_android, size: 40, color: AppConstants.primary),
                ),
                const SizedBox(height: 16),
                Text(
                  '${brand?['name'] ?? ''} ${model?['name'] ?? ''}',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                  textAlign: TextAlign.center,
                ),
                if (model?['storage'] != null || model?['ram'] != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    [
                      if (model?['storage'] != null) model!['storage'],
                      if (model?['ram'] != null) '${model!['ram']} RAM',
                    ].join(' • '),
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Device details
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Taarifa za Kifaa', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)),
                const Divider(height: 20),
                _InfoRow('IMEI', d['imei'] ?? '-'),
                if (d['imei_2'] != null) _InfoRow('IMEI 2', d['imei_2']),
                if (d['serial_number'] != null) _InfoRow('Serial Number', d['serial_number']),
                _InfoRow('Bei ya Pesa Taslimu', 'TZS ${_fmtAmount(d['cash_price'])}'),
                _InfoRow('Amana', 'TZS ${_fmtAmount(d['deposit_amount'])}'),
                _InfoRow('Malipo', _repaymentLabel(d['preferred_repayment'])),
                _InfoRow('Hali', d['asset_release_status'] == 'released' ? 'Imepewa' : 'Inasubiri'),
                if (d['asset_released_at'] != null) _InfoRow('Tarehe ya Kupewa', d['asset_released_at']),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Agreement
        if (d['agreement'] != null)
          Card(
            child: ListTile(
              leading: const Icon(Icons.description_rounded, color: AppConstants.primary),
              title: Text(d['agreement']['title'] ?? 'Mkataba'),
              subtitle: const Text('Bonyeza kuona mkataba'),
              trailing: const Icon(Icons.open_in_new, size: 18),
              onTap: () {
                // Could open PDF viewer
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Mkataba utafunguliwa hivi karibuni')),
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
    return d.toStringAsFixed(0).replaceAllMapped(
          RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
          (m) => '${m[1]},',
        );
  }

  String _repaymentLabel(dynamic v) {
    return switch (v) {
      'weekly' => 'Kila Wiki',
      'biweekly' => 'Kila Wiki 2',
      'monthly' => 'Kila Mwezi',
      _ => v?.toString() ?? '-',
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
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(child: Text(label, style: TextStyle(color: Colors.grey[600], fontSize: 13))),
          const SizedBox(width: 12),
          Flexible(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13), textAlign: TextAlign.end)),
        ],
      ),
    );
  }
}
