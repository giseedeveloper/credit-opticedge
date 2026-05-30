import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/staff_ops_provider.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class OperationsHubScreen extends ConsumerStatefulWidget {
  const OperationsHubScreen({super.key});

  @override
  ConsumerState<OperationsHubScreen> createState() =>
      _OperationsHubScreenState();
}

class _OperationsHubScreenState extends ConsumerState<OperationsHubScreen> {
  final _imeiCtrl = TextEditingController();

  @override
  void dispose() {
    _imeiCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).user;
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: const Text('Field Operations'),
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
      ),
      body: PremiumGlassBackground(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
          children: [
            if (user?.canViewStaffMetrics ?? false) ...[
              _SectionTitle('My performance'),
              const SizedBox(height: 10),
              ref.watch(staffMetricsProvider).when(
                    loading: () => const Center(
                      child: Padding(
                        padding: EdgeInsets.all(24),
                        child: CircularProgressIndicator(),
                      ),
                    ),
                    error: (e, _) => _ErrorBanner(e.toString()),
                    data: (m) => GlassCard.surface(
                      context,
                      padding: const EdgeInsets.all(18),
                      child: Column(
                        children: [
                          _MetricRow(
                            'Customers onboarded',
                            '${m['total_customers_acquired'] ?? 0}',
                          ),
                          const Divider(height: 20),
                          _MetricRow(
                            'Active loans managed',
                            '${m['active_loans_managed'] ?? 0}',
                          ),
                        ],
                      ),
                    ),
                  ),
              const SizedBox(height: 24),
            ],
            if (user?.canViewStock ?? false) ...[
              _SectionTitle('Stock lookup'),
              const SizedBox(height: 10),
              TextField(
                controller: _imeiCtrl,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: 'IMEI (15 digits)',
                  filled: true,
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.search_rounded),
                    onPressed: () => setState(() {}),
                  ),
                ),
                onSubmitted: (_) => setState(() {}),
              ),
              const SizedBox(height: 12),
              if (_imeiCtrl.text.trim().length >= 14)
                ref.watch(stockSearchProvider(_imeiCtrl.text)).when(
                      loading: () => const LinearProgressIndicator(),
                      error: (e, _) => _ErrorBanner(e.toString()),
                      data: (unit) => unit == null
                          ? const Text('Enter a valid IMEI to search.')
                          : GlassCard.surface(
                              context,
                              padding: const EdgeInsets.all(16),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    unit['phone_model']?['name']?.toString() ??
                                        unit['model_name']?.toString() ??
                                        'Device',
                                    style: theme.textTheme.titleMedium
                                        ?.copyWith(fontWeight: FontWeight.w800),
                                  ),
                                  const SizedBox(height: 8),
                                  _MetricRow('Status', '${unit['status']}'),
                                  _MetricRow('IMEI 1', '${unit['imei_1']}'),
                                ],
                              ),
                            ),
                    ),
              const SizedBox(height: 24),
            ],
            if (user?.canViewRecovery ?? false) ...[
              _SectionTitle('Recovery tickets'),
              const SizedBox(height: 10),
              ref.watch(recoveryTicketsProvider).when(
                    loading: () => const Center(
                      child: CircularProgressIndicator(),
                    ),
                    error: (e, _) => _ErrorBanner(e.toString()),
                    data: (page) {
                      final items = page['data'] is List
                          ? page['data'] as List
                          : (page['data'] is Map &&
                                  page['data']['data'] is List)
                              ? page['data']['data'] as List
                              : <dynamic>[];

                      if (items.isEmpty) {
                        return GlassCard.surface(
                          context,
                          padding: const EdgeInsets.all(18),
                          child: const Text(
                            'No open recovery assignments for you.',
                          ),
                        );
                      }

                      return Column(
                        children: items.take(10).map((raw) {
                          final t = Map<String, dynamic>.from(raw as Map);
                          final loan = t['loan'] is Map
                              ? Map<String, dynamic>.from(t['loan'] as Map)
                              : null;
                          final customer = loan?['customer'] is Map
                              ? loan!['customer']['full_name']?.toString()
                              : null;

                          return Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: GlassCard.surface(
                              context,
                              padding: const EdgeInsets.all(14),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.assignment_return_rounded,
                                    color: DesignTokens.statAmber,
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          customer ?? 'Recovery ticket',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                        Text(
                                          'Status: ${t['status']}',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: theme
                                                .textTheme.bodySmall?.color,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        }).toList(),
                      );
                    },
                  ),
            ],
            if (!(user?.canViewStaffMetrics ?? false) &&
                !(user?.canViewStock ?? false) &&
                !(user?.canViewRecovery ?? false))
              GlassCard.surface(
                context,
                padding: const EdgeInsets.all(20),
                child: Text(
                  'Your role does not include field operations modules. '
                  'Contact HQ if you need stock or recovery access.',
                  style: TextStyle(color: theme.textTheme.bodyMedium?.color),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w800,
        color: AppConstants.primary,
      ),
    );
  }
}

class _MetricRow extends StatelessWidget {
  const _MetricRow(this.label, this.value);
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
      ],
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner(this.message);
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppConstants.error.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(message, style: const TextStyle(color: AppConstants.error)),
    );
  }
}
