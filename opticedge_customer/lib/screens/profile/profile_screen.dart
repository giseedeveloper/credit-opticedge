import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/auth_provider.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);
    final c = auth.customer;

    return Scaffold(
      appBar: AppBar(title: const Text('Profaili Yangu')),
      body: c == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Avatar
                Center(
                  child: Column(
                    children: [
                      CircleAvatar(
                        radius: 48,
                        backgroundColor: AppConstants.primary.withValues(
                          alpha: 0.1,
                        ),
                        child: Text(
                          _initials(c.firstName, c.lastName),
                          style: const TextStyle(
                            fontSize: 28,
                            fontWeight: FontWeight.bold,
                            color: AppConstants.primary,
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        c.fullName,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        c.phoneDisplay ?? c.phone,
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),

                // Personal Info
                _SectionCard(
                  title: 'Taarifa Binafsi',
                  icon: Icons.person_rounded,
                  children: [
                    _InfoRow('Jina Kamili', c.fullName),
                    _InfoRow('Simu', c.phoneDisplay ?? c.phone),
                    if (c.email != null) _InfoRow('Barua Pepe', c.email!),
                    if (c.gender != null)
                      _InfoRow(
                        'Jinsia',
                        c.gender == 'male' ? 'Mwanaume' : 'Mwanamke',
                      ),
                    if (c.nidaNumber != null) _InfoRow('NIDA', c.nidaNumber!),
                  ],
                ),
                const SizedBox(height: 12),

                // Vendor Info
                if (c.vendor != null)
                  _SectionCard(
                    title: 'Muuzaji',
                    icon: Icons.store_rounded,
                    children: [
                      _InfoRow('Jina', c.vendor!.name),
                      if (c.vendor!.phone != null)
                        _InfoRow('Simu', c.vendor!.phone!),
                      if (c.vendor!.address != null)
                        _InfoRow('Anwani', c.vendor!.address!),
                    ],
                  ),
                if (c.vendor != null) const SizedBox(height: 12),

                // Branch Info
                if (c.branch != null)
                  _SectionCard(
                    title: 'Tawi',
                    icon: Icons.location_on_rounded,
                    children: [
                      _InfoRow('Jina', c.branch!.name),
                      if (c.branch!.phone != null)
                        _InfoRow('Simu', c.branch!.phone!),
                      if (c.branch!.region != null)
                        _InfoRow('Mkoa', c.branch!.region!),
                      if (c.branch!.address != null)
                        _InfoRow('Anwani', c.branch!.address!),
                    ],
                  ),
                const SizedBox(height: 24),

                // Change PIN
                Card(
                  child: ListTile(
                    leading: const Icon(
                      Icons.lock_rounded,
                      color: AppConstants.primary,
                    ),
                    title: const Text('Badilisha PIN'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => _showChangePinDialog(context, ref),
                  ),
                ),
                const SizedBox(height: 8),

                // Logout
                Card(
                  child: ListTile(
                    leading: const Icon(
                      Icons.logout_rounded,
                      color: AppConstants.danger,
                    ),
                    title: const Text(
                      'Ondoka',
                      style: TextStyle(color: AppConstants.danger),
                    ),
                    onTap: () => _confirmLogout(context, ref),
                  ),
                ),
                const SizedBox(height: 24),
                Center(
                  child: Text(
                    'Opticedge Customer v1.0',
                    style: TextStyle(color: Colors.grey[400], fontSize: 12),
                  ),
                ),
              ],
            ),
    );
  }

  String _initials(String first, String last) {
    final f = first.isNotEmpty ? first[0].toUpperCase() : '';
    final l = last.isNotEmpty ? last[0].toUpperCase() : '';
    return '$f$l';
  }

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Ondoka'),
        content: const Text('Una uhakika unataka kuondoka?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Hapana'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              ref.read(authProvider.notifier).logout();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppConstants.danger,
            ),
            child: const Text('Ndiyo, Ondoka'),
          ),
        ],
      ),
    );
  }

  void _showChangePinDialog(BuildContext context, WidgetRef ref) {
    final currentPinCtrl = TextEditingController();
    final newPinCtrl = TextEditingController();
    final confirmPinCtrl = TextEditingController();
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Badilisha PIN'),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: currentPinCtrl,
                obscureText: true,
                maxLength: 6,
                decoration: const InputDecoration(
                  labelText: 'PIN ya Sasa',
                  counterText: '',
                ),
                validator: (v) =>
                    v == null || v.length < 4 ? 'Weka PIN ya sasa' : null,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: newPinCtrl,
                obscureText: true,
                maxLength: 6,
                decoration: const InputDecoration(
                  labelText: 'PIN Mpya',
                  counterText: '',
                ),
                validator: (v) =>
                    v == null || v.length < 4 ? 'PIN lazima iwe 4-6' : null,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: confirmPinCtrl,
                obscureText: true,
                maxLength: 6,
                decoration: const InputDecoration(
                  labelText: 'Thibitisha PIN Mpya',
                  counterText: '',
                ),
                validator: (v) {
                  if (v != newPinCtrl.text) return 'PIN hazilingani';
                  return null;
                },
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Ghairi'),
          ),
          ElevatedButton(
            onPressed: () async {
              if (!formKey.currentState!.validate()) return;
              try {
                await _changePin(
                  currentPinCtrl.text,
                  newPinCtrl.text,
                  confirmPinCtrl.text,
                );
                if (ctx.mounted) Navigator.pop(ctx);
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('PIN imebadilishwa!'),
                      backgroundColor: AppConstants.accent,
                    ),
                  );
                }
              } catch (e) {
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Imeshindikana: $e'),
                      backgroundColor: AppConstants.danger,
                    ),
                  );
                }
              }
            },
            child: const Text('Badilisha'),
          ),
        ],
      ),
    );
  }

  Future<void> _changePin(String current, String newPin, String confirm) async {
    final res = await ApiClient.instance.put(
      '/pin',
      data: {
        'current_pin': current,
        'new_pin': newPin,
        'new_pin_confirmation': confirm,
      },
    );
    if (res.data['success'] != true) {
      throw res.data['message'] ?? 'Failed';
    }
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final List<Widget> children;
  const _SectionCard({
    required this.title,
    required this.icon,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: AppConstants.primary, size: 20),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            const Divider(height: 20),
            ...children,
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Colors.grey[600], fontSize: 13)),
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }
}
