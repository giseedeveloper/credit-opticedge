import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../config/constants.dart';
import '../../core/providers/loan_provider.dart';

final _currencyFmt = NumberFormat('#,##0', 'en');

class ScheduleScreen extends ConsumerStatefulWidget {
  const ScheduleScreen({super.key});

  @override
  ConsumerState<ScheduleScreen> createState() => _ScheduleScreenState();
}

class _ScheduleScreenState extends ConsumerState<ScheduleScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(scheduleProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(scheduleProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Ratiba ya Malipo')),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : state.error != null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    state.error!,
                    style: const TextStyle(color: AppConstants.error),
                  ),
                  const SizedBox(height: 8),
                  TextButton(
                    onPressed: () => ref.read(scheduleProvider.notifier).load(),
                    child: const Text('Jaribu Tena'),
                  ),
                ],
              ),
            )
          : state.schedule == null
          ? const Center(child: Text('Hakuna ratiba'))
          : RefreshIndicator(
              onRefresh: () => ref.read(scheduleProvider.notifier).load(),
              child: _buildList(context, state),
            ),
    );
  }

  Widget _buildList(BuildContext context, ScheduleState state) {
    final s = state.schedule!;

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: s.schedule.length + 1,
      itemBuilder: (ctx, index) {
        if (index == 0) {
          return Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _StatChip(
                      label: 'Jumla',
                      value: '${s.totalInstallments}',
                      color: AppConstants.primary,
                    ),
                    _StatChip(
                      label: 'Zilipolipwa',
                      value: '${s.paidInstallments}',
                      color: AppConstants.success,
                    ),
                    _StatChip(
                      label: 'Zimebaki',
                      value: '${s.totalInstallments - s.paidInstallments}',
                      color: AppConstants.warning,
                    ),
                  ],
                ),
              ),
            ),
          );
        }

        final item = s.schedule[index - 1];
        final statusColor = AppConstants.loanStatusColor(item.status);

        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: ListTile(
            leading: Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Center(
                child: Text(
                  '#${item.installmentNumber}',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: statusColor,
                    fontSize: 13,
                  ),
                ),
              ),
            ),
            title: Text(
              'TZS ${_currencyFmt.format(item.amountDue)}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text(
              item.dueDate ?? '-',
              style: TextStyle(color: Colors.grey[600], fontSize: 12),
            ),
            trailing: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    _statusLabel(item.status),
                    style: TextStyle(
                      color: statusColor,
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                if (item.isOverdue && item.daysOverdue > 0)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      'Siku ${item.daysOverdue}',
                      style: const TextStyle(
                        color: AppConstants.error,
                        fontSize: 10,
                      ),
                    ),
                  ),
                if (item.isPaid && item.paidAt != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      item.paidAt!,
                      style: TextStyle(color: Colors.grey[500], fontSize: 10),
                    ),
                  ),
              ],
            ),
          ),
        );
      },
    );
  }

  String _statusLabel(String status) {
    return switch (status) {
      'paid' => 'Imelipwa',
      'pending' => 'Inasubiri',
      'partial' => 'Sehemu',
      'overdue' => 'Imechelewa',
      _ => status,
    };
  }
}

class _StatChip extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  const _StatChip({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        const SizedBox(height: 2),
        Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[600])),
      ],
    );
  }
}
