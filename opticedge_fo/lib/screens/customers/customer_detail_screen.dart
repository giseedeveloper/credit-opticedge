import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../core/providers/customer_provider.dart';
import '../../widgets/common/status_badge.dart';

class CustomerDetailScreen extends ConsumerWidget {
  final String customerId;
  const CustomerDetailScreen({super.key, required this.customerId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(customerDetailProvider(customerId));
    return async.when(
      loading: () => const Scaffold(
        body: Center(
            child: CircularProgressIndicator(color: AppConstants.primary)),
      ),
      error: (e, _) => Scaffold(
        appBar: AppBar(title: const Text('Customer Detail')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline,
                  size: 48, color: AppConstants.error),
              const SizedBox(height: 12),
              Text(e.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppConstants.textSecondary)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () =>
                    ref.refresh(customerDetailProvider(customerId)),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      ),
      data: (customer) => _DetailView(customer: customer),
    );
  }
}

class _DetailView extends StatefulWidget {
  final dynamic customer;
  const _DetailView({required this.customer});

  @override
  State<_DetailView> createState() => _DetailViewState();
}

class _DetailViewState extends State<_DetailView> {
  final Set<String> _expanded = {'personal', 'verification'};

  @override
  Widget build(BuildContext context) {
    final c = widget.customer;
    return Scaffold(
      backgroundColor: AppConstants.background,
      body: CustomScrollView(
        slivers: [
          _buildAppBar(context, c),
          SliverPadding(
            padding: const EdgeInsets.all(16),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                // Verification status banner
                if (c.verification != null) _buildStatusBanner(c.verification),
                const SizedBox(height: 12),

                // Sections
                _buildSection('personal', 'Personal Info',
                    Icons.person_outline_rounded, _buildPersonalInfo(c)),
                _buildSection('identity', 'ID & Identity', Icons.badge_outlined,
                    _buildIdentityInfo(c)),
                _buildSection('device', 'Device Details',
                    Icons.smartphone_rounded, _buildDeviceInfo(c)),
                _buildSection('contact', 'Contact & Location',
                    Icons.location_on_outlined, _buildContactInfo(c)),
                _buildSection('income', 'Work & Income',
                    Icons.work_outline_rounded, _buildIncomeInfo(c)),
                _buildSection('nok', 'Next of Kin',
                    Icons.people_outline_rounded, _buildNokInfo(c)),
                _buildSection('photos', 'Documents & Photos',
                    Icons.photo_library_outlined, _buildPhotos(c)),
                if (c.verification != null)
                  _buildSection('verification', 'Verification Status',
                      Icons.verified_outlined, _buildVerification(c)),

                const SizedBox(height: 24),
              ]),
            ),
          ),
        ],
      ),
    );
  }

  SliverAppBar _buildAppBar(BuildContext context, c) {
    return SliverAppBar(
      pinned: true,
      expandedHeight: 120,
      backgroundColor: AppConstants.primary,
      foregroundColor: Colors.white,
      actions: [
        IconButton(
          icon: const Icon(Icons.edit_outlined),
          onPressed: () {},
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [Color(0xFFEA580C), Color(0xFFC2410C)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 26,
                        backgroundColor: Colors.white.withOpacity(0.25),
                        backgroundImage: c.photos['headshot'] != null
                            ? NetworkImage(c.photos['headshot']!)
                            : null,
                        child: c.photos['headshot'] == null
                            ? Text(
                                c.fullName.isNotEmpty
                                    ? c.fullName[0].toUpperCase()
                                    : '?',
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700),
                              )
                            : null,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              c.fullName,
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 17,
                                  fontWeight: FontWeight.w700),
                            ),
                            Text(
                              c.phone,
                              style: TextStyle(
                                  color: Colors.white.withOpacity(0.8),
                                  fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                      StatusBadge(status: c.kycStatus),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusBanner(Map<String, dynamic> v) {
    final status = v['status']?.toString() ?? 'pending';
    final color = AppConstants.statusColors[status] ?? AppConstants.info;
    final bg = AppConstants.statusBg[status] ?? AppConstants.borderLight;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.25)),
      ),
      child: Row(
        children: [
          Icon(Icons.info_outline, color: color, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  v['stage_label']?.toString() ?? 'Under Review',
                  style: TextStyle(
                      fontWeight: FontWeight.w600, fontSize: 13, color: color),
                ),
                if (v['rejection_reason'] != null)
                  Text(
                    v['rejection_reason'].toString(),
                    style: const TextStyle(
                        fontSize: 12, color: AppConstants.textSecondary),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection(
      String key, String title, IconData icon, Widget content) {
    final isOpen = _expanded.contains(key);
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: AppConstants.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppConstants.border),
      ),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () => setState(() {
              if (isOpen) {
                _expanded.remove(key);
              } else {
                _expanded.add(key);
              }
            }),
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: AppConstants.primarySurface,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(icon, color: AppConstants.primary, size: 16),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppConstants.textPrimary),
                    ),
                  ),
                  Icon(
                    isOpen
                        ? Icons.keyboard_arrow_up_rounded
                        : Icons.keyboard_arrow_down_rounded,
                    color: AppConstants.textHint,
                    size: 20,
                  ),
                ],
              ),
            ),
          ),
          if (isOpen) ...[
            const Divider(height: 1, color: AppConstants.border),
            Padding(
              padding: const EdgeInsets.all(14),
              child: content,
            ),
          ],
        ],
      ),
    );
  }

  Widget _infoRow(String label, String? value) {
    if (value == null || value.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: const TextStyle(
                  fontSize: 12,
                  color: AppConstants.textSecondary,
                  fontWeight: FontWeight.w500),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                  fontSize: 13,
                  color: AppConstants.textPrimary,
                  fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPersonalInfo(c) => Column(
        children: [
          _infoRow('Full Name', c.fullName),
          _infoRow('Gender', c.gender),
          _infoRow('Date of Birth', c.dateOfBirth),
          _infoRow('Branch', c.branch?['name']),
          _infoRow('Registered', c.registeredAt),
          _infoRow('Source', c.applicationSource),
        ],
      );

  Widget _buildIdentityInfo(c) => Column(
        children: [
          _infoRow('NIDA Number', c.nidaNumber),
          _infoRow('ID Type', c.idType),
          _infoRow('Email', c.email),
        ],
      );

  Widget _buildDeviceInfo(c) {
    final d = c.device as Map<String, dynamic>;
    return Column(
      children: [
        _infoRow('Device Specs', d['specs']?.toString()),
        _infoRow('IMEI 1', d['imei_1']?.toString()),
        _infoRow('IMEI 2', d['imei_2']?.toString()),
        _infoRow('Serial Number', d['serial_number']?.toString()),
        _infoRow('Cash Price', d['cash_price']?.toString()),
        _infoRow('Deposit', d['deposit_amount']?.toString()),
        _infoRow('Repayment', d['preferred_repayment']?.toString()),
      ],
    );
  }

  Widget _buildContactInfo(c) => Column(
        children: [
          _infoRow('Phone', c.phone),
          _infoRow('Alt Phone', c.altPhone),
          _infoRow('Region', c.region),
          _infoRow('District', c.district),
          _infoRow('Address', c.address),
          _infoRow('Landmark', c.landmark),
          if (c.latitude != null)
            _infoRow('GPS',
                '${c.latitude?.toStringAsFixed(6)}, ${c.longitude?.toStringAsFixed(6)}'),
        ],
      );

  Widget _buildIncomeInfo(c) {
    final i = c.income as Map<String, dynamic>;
    return Column(
      children: [
        _infoRow('Occupation', i['occupation']?.toString()),
        _infoRow('Employer', i['employer']?.toString()),
        _infoRow('Monthly Income', i['monthly_income']?.toString()),
        _infoRow('Monthly Expenses', i['monthly_expenses']?.toString()),
        _infoRow('Payment Cycle', i['income_payment_cycle']?.toString()),
        _infoRow('Duration at Work', i['duration_at_work']?.toString()),
      ],
    );
  }

  Widget _buildNokInfo(c) {
    final n = c.nok as Map<String, dynamic>;
    return Column(
      children: [
        _infoRow('Name', n['nok_name']?.toString()),
        _infoRow('Phone', n['nok_phone']?.toString()),
        _infoRow('Relationship', n['nok_relationship']?.toString()),
        if (n['nok2_name'] != null) ...[
          const Divider(height: 20, color: AppConstants.border),
          _infoRow('NOK 2 Name', n['nok2_name']?.toString()),
          _infoRow('NOK 2 Phone', n['nok2_phone']?.toString()),
          _infoRow('NOK 2 Relationship', n['nok2_relationship']?.toString()),
        ],
      ],
    );
  }

  Widget _buildPhotos(c) {
    final photos = c.photos as Map<String, String?>;
    final photoEntries = photos.entries
        .where((e) => e.value != null && e.value!.isNotEmpty)
        .toList();

    if (photoEntries.isEmpty) {
      return const Text('No photos uploaded',
          style: TextStyle(color: AppConstants.textHint, fontSize: 13));
    }

    final labels = {
      'imei': 'IMEI',
      'device_box': 'Device Box',
      'device': 'Device',
      'id_front': 'ID Front',
      'id_back': 'ID Back',
      'headshot': 'Headshot',
      'client_fo': 'Client + FO',
      'business': 'Business',
    };

    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 8,
      mainAxisSpacing: 8,
      childAspectRatio: 1,
      children: photoEntries
          .map((e) => Column(
                children: [
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: Image.network(
                        e.value!,
                        fit: BoxFit.cover,
                        width: double.infinity,
                        errorBuilder: (_, __, ___) => Container(
                          color: AppConstants.borderLight,
                          child: const Icon(Icons.broken_image_outlined,
                              color: AppConstants.textHint),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    labels[e.key] ?? e.key,
                    style: const TextStyle(
                        fontSize: 9,
                        color: AppConstants.textSecondary,
                        fontWeight: FontWeight.w500),
                    textAlign: TextAlign.center,
                  ),
                ],
              ))
          .toList(),
    );
  }

  Widget _buildVerification(c) {
    final v = c.verification as Map<String, dynamic>;
    return Column(
      children: [
        _infoRow('Status', v['status']?.toString()),
        _infoRow('Stage', v['stage_label']?.toString()),
        _infoRow('Auto Check', v['auto_check_status']?.toString()),
        _infoRow('Reviewed By', v['reviewed_by']?.toString()),
        _infoRow('Reviewed At', v['reviewed_at']?.toString()),
        _infoRow('Submitted At', v['submitted_at']?.toString()),
        if (v['rejection_reason'] != null)
          _infoRow('Rejection Reason', v['rejection_reason']?.toString()),
        if (v['notes'] != null) _infoRow('Notes', v['notes']?.toString()),
      ],
    );
  }
}
