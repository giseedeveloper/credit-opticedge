import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../config/constants.dart';
import '../../config/design_tokens.dart';
import '../../core/api/api_client.dart';
import '../../core/models/customer_model.dart';
import '../../core/providers/customer_provider.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/status_badge.dart';
import '../../widgets/common/glass_card.dart';
import '../../widgets/common/premium_glass_background.dart';

class CustomerDetailScreen extends ConsumerWidget {
  final String customerId;

  const CustomerDetailScreen({
    super.key,
    required this.customerId,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(customerDetailProvider(customerId));

    return async.when(
      loading: () => const Scaffold(
        body: Center(
          child: CircularProgressIndicator(color: AppConstants.primary),
        ),
      ),
      error: (error, _) => Scaffold(
        appBar: AppBar(title: const Text('Customer Detail')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(
                  Icons.error_outline_rounded,
                  size: 52,
                  color: AppConstants.error,
                ),
                const SizedBox(height: 12),
                Text(
                  error.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppConstants.textSecondary),
                ),
                const SizedBox(height: 16),
                AppButton(
                  label: 'Retry',
                  icon: Icons.refresh_rounded,
                  onPressed: () =>
                      ref.refresh(customerDetailProvider(customerId)),
                ),
              ],
            ),
          ),
        ),
      ),
      data: (customer) => _DetailView(
        customer: customer,
        customerId: customerId,
      ),
    );
  }
}

class _DetailView extends ConsumerStatefulWidget {
  final CustomerDetail customer;
  final String customerId;

  const _DetailView({
    required this.customer,
    required this.customerId,
  });

  @override
  ConsumerState<_DetailView> createState() => _DetailViewState();
}

class _DetailViewState extends ConsumerState<_DetailView>
    with WidgetsBindingObserver {
  final Set<String> _expanded = {
    'personal',
    'device',
    'payment',
    'release',
  };

  bool _releasing = false;
  bool _uploadingHandover = false;
  Timer? _detailPollTimer;

  CustomerDetail get customer => widget.customer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _detailPollTimer = Timer.periodic(const Duration(seconds: 25), (_) {
      if (!mounted) {
        return;
      }
      ref.invalidate(customerDetailProvider(widget.customerId));
    });
  }

  @override
  void dispose() {
    _detailPollTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.invalidate(customerDetailProvider(widget.customerId));
    }
  }

  @override
  Widget build(BuildContext context) {
    final verification = customer.verification;
    final isReleased = customer.release?.status == 'released';
    final canResumeDraft = customer.canResumeDraft;

    return Scaffold(
      backgroundColor: Colors.transparent,
      bottomNavigationBar: customer.canReleaseAsset && !isReleased
          ? SafeArea(
              minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              child: AppButton(
                label: 'Release Asset',
                icon: Icons.inventory_2_outlined,
                isLoading: _releasing,
                onPressed: _releaseAsset,
              ),
            )
          : null,
      body: PremiumGlassBackground(
        child: CustomScrollView(
          slivers: [
            _buildAppBar(context),
            SliverPadding(
              padding: const EdgeInsets.all(16),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  if (verification != null) _buildStatusBanner(verification),
                  if (canResumeDraft) ...[
                    if (verification != null) const SizedBox(height: 12),
                    _resumeDraftCard(context),
                  ],
                  if (customer.release?.status == 'released') ...[
                    const SizedBox(height: 12),
                    _releasedBanner(),
                  ],
                  const SizedBox(height: 12),
                  _buildSection(
                    keyName: 'personal',
                    title: 'Personal Info',
                    icon: Icons.person_outline_rounded,
                    content: _buildPersonalInfo(),
                  ),
                  _buildSection(
                    keyName: 'identity',
                    title: 'Identity & Contact',
                    icon: Icons.badge_outlined,
                    content: _buildIdentityAndContact(),
                  ),
                  _buildSection(
                    keyName: 'device',
                    title: 'Device & Offer Details',
                    icon: Icons.smartphone_rounded,
                    content: _buildDeviceInfo(),
                  ),
                  _buildSection(
                    keyName: 'income',
                    title: 'Work & Income',
                    icon: Icons.work_outline_rounded,
                    content: _buildIncomeInfo(),
                  ),
                  _buildSection(
                    keyName: 'nok',
                    title: 'Next of Kin',
                    icon: Icons.people_outline_rounded,
                    content: _buildNokInfo(),
                  ),
                  _buildSection(
                    keyName: 'payment',
                    title: 'Payment & Agreement',
                    icon: Icons.payments_outlined,
                    content: _buildPaymentAgreement(),
                  ),
                  _buildSection(
                    keyName: 'photos',
                    title: 'Photos, Signatures & Files',
                    icon: Icons.photo_library_outlined,
                    content: _buildPhotos(),
                  ),
                  if (verification != null)
                    _buildSection(
                      keyName: 'verification',
                      title: 'Verification Status',
                      icon: Icons.verified_outlined,
                      content: _buildVerification(verification),
                    ),
                  _buildSection(
                    keyName: 'release',
                    title: 'Asset Release',
                    icon: Icons.inventory_2_outlined,
                    content: _buildRelease(),
                  ),
                  const SizedBox(height: 24),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  bool _needsHandoverChecklistUpload(CustomerDetail c) {
    if (c.release?.status == 'released') {
      return false;
    }
    final blockers = c.release?.eligibilityBlockers ?? const <String>[];
    return blockers.any(
      (b) => b.toLowerCase().contains('handover'),
    );
  }

  Future<void> _uploadHandoverChecklist() async {
    final picker = ImagePicker();
    final xfile = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 82,
      maxWidth: 2000,
    );
    if (xfile == null || !mounted) {
      return;
    }

    setState(() => _uploadingHandover = true);
    try {
      final form = FormData.fromMap({
        'asset_handover_list': await MultipartFile.fromFile(
          xfile.path,
          filename: xfile.name,
        ),
      });
      await ApiClient.instance.postForm(
        '/kyc/customers/${widget.customerId}/handover-checklist',
        form,
      );
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Handover checklist uploaded.'),
          backgroundColor: AppConstants.success,
        ),
      );
      ref.invalidate(customerDetailProvider(widget.customerId));
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ApiClient.instance.parseError(e)),
          backgroundColor: AppConstants.error,
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _uploadingHandover = false);
      }
    }
  }

  Future<void> _releaseAsset() async {
    setState(() {
      _releasing = true;
    });

    try {
      final response = await ApiClient.instance
          .post('/kyc/customers/${widget.customerId}/release-asset');

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            response.data['message']?.toString() ??
                'Asset released successfully.',
          ),
          backgroundColor: AppConstants.success,
        ),
      );
      ref.invalidate(customerDetailProvider(widget.customerId));
    } catch (error) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ApiClient.instance.parseError(error)),
          backgroundColor: AppConstants.error,
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _releasing = false;
        });
      }
    }
  }

  SliverAppBar _buildAppBar(BuildContext context) {
    return SliverAppBar(
      pinned: true,
      expandedHeight: 158,
      backgroundColor: DesignTokens.heroStart,
      foregroundColor: Colors.white,
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: BoxDecoration(
            gradient: DesignTokens.heroGradientWithPrimaryHint,
          ),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 18),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 56,
                        height: 56,
                        clipBehavior: Clip.antiAlias,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.22),
                          shape: BoxShape.circle,
                        ),
                        child: customer.photos['headshot'] == null
                            ? Center(
                                child: Text(
                                  customer.fullName.isNotEmpty
                                      ? customer.fullName[0].toUpperCase()
                                      : '?',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              )
                            : Image.network(
                                customer.photos['headshot']!,
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) => Center(
                                  child: Text(
                                    customer.fullName.isNotEmpty
                                        ? customer.fullName[0].toUpperCase()
                                        : '?',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 18,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              customer.fullName,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              customer.phone,
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.82),
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                      StatusBadge(status: customer.kycStatus),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
      actions: [
        IconButton(
          icon: const Icon(Icons.copy_rounded),
          onPressed: () async {
            await Clipboard.setData(ClipboardData(text: customer.id));
            if (!context.mounted) {
              return;
            }
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Customer ID copied.'),
                backgroundColor: AppConstants.success,
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildStatusBanner(Map<String, dynamic> verification) {
    final status = verification['status']?.toString() ?? 'pending';
    final color = AppConstants.statusColors[status] ?? AppConstants.info;
    final background =
        AppConstants.statusBg[status] ?? AppConstants.borderLight;

    return GlassCard(
      tint: background,
      borderRadius: BorderRadius.circular(18),
      borderColor: color.withValues(alpha: 0.22),
      padding: const EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline_rounded, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  verification['stage_label']?.toString() ?? 'Under Review',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  verification['notes']?.toString() ??
                      'This application is moving through verification review.',
                  style: const TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _releasedBanner() {
    return GlassCard(
      tint: const Color(0xFFF0FDF4),
      borderRadius: BorderRadius.circular(18),
      borderColor: AppConstants.success.withValues(alpha: 0.22),
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          const Icon(Icons.check_circle_rounded, color: AppConstants.success),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Asset released on ${customer.release?.releasedAt ?? '-'} by ${customer.release?.releasedBy ?? 'system'}.',
              style: const TextStyle(
                fontSize: 12,
                color: AppConstants.textSecondary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection({
    required String keyName,
    required String title,
    required IconData icon,
    required Widget content,
  }) {
    final isOpen = _expanded.contains(keyName);

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: GlassCard(
        tint: Colors.white,
        borderRadius: BorderRadius.circular(18),
        borderColor: AppConstants.border,
        padding: EdgeInsets.zero,
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(18),
            onTap: () {
              setState(() {
                if (isOpen) {
                  _expanded.remove(keyName);
                } else {
                  _expanded.add(keyName);
                }
              });
            },
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  Container(
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(
                      color: AppConstants.primarySurface,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(icon, size: 17, color: AppConstants.primary),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: AppConstants.textPrimary,
                      ),
                    ),
                  ),
                  Icon(
                    isOpen
                        ? Icons.keyboard_arrow_up_rounded
                        : Icons.keyboard_arrow_down_rounded,
                    color: AppConstants.textHint,
                  ),
                ],
              ),
            ),
          ),
          if (isOpen) ...[
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.all(14),
              child: content,
            ),
          ],
        ],
      ),
      ),
    );
  }

  Widget _buildPersonalInfo() {
    return Column(
      children: [
        _infoRow('Full Name', customer.fullName),
        _infoRow('Gender', customer.gender),
        _infoRow('Date of Birth', customer.dateOfBirth),
        _infoRow('NIDA Number', customer.nidaNumber),
        _infoRow('Branch', customer.branch?['name']?.toString()),
        _infoRow('Vendor / Store', customer.vendor?['name']?.toString()),
        _infoRow('Application Source', customer.applicationSource),
        _infoRow('Registered At', customer.registeredAt),
      ],
    );
  }

  Widget _buildIdentityAndContact() {
    return Column(
      children: [
        _infoRow('Phone', customer.phone),
        _infoRow('Alternative Phone', customer.altPhone),
        _infoRow('Email', customer.email),
        _infoRow('Address', customer.address),
        _infoRow('Landmark', customer.landmark),
        _infoRow('Region', customer.region),
        _infoRow('District', customer.district),
        if (customer.latitude != null && customer.longitude != null)
          _infoRow(
            'GPS',
            '${customer.latitude?.toStringAsFixed(6)}, ${customer.longitude?.toStringAsFixed(6)}',
          ),
      ],
    );
  }

  Widget _buildDeviceInfo() {
    final device = customer.device;
    final accessories = (device['accessories'] as List<dynamic>? ?? []);
    final scanMetadata = device['scan_metadata'] as Map<String, dynamic>?;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _infoRow('Brand', device['brand_name']?.toString()),
        _infoRow('Model', device['model_name']?.toString()),
        _infoRow('Specs', device['specs']?.toString()),
        _infoRow('IMEI 1', device['imei_1']?.toString()),
        _infoRow('IMEI 2', device['imei_2']?.toString()),
        _infoRow('Serial Number', device['serial_number']?.toString()),
        _infoRow('Cash Price', device['cash_price']?.toString()),
        _infoRow('Deposit', device['deposit_amount']?.toString()),
        _infoRow('Repayment', device['preferred_repayment']?.toString()),
        if (accessories.isNotEmpty) ...[
          const SizedBox(height: 6),
          const Text(
            'Accessories / Offers',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppConstants.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: accessories
                .map(
                  (item) => Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 7,
                    ),
                    decoration: BoxDecoration(
                      color: AppConstants.primarySurface,
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      '${item['name'] ?? 'Accessory'} • ${item['offer_type'] ?? 'free'}',
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppConstants.primary,
                      ),
                    ),
                  ),
                )
                .toList(),
          ),
        ],
        _infoRow('Store Notes', device['store_offer_notes']?.toString()),
        if (scanMetadata != null) ...[
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppConstants.borderLight,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Scan Metadata',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppConstants.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                _infoRow(
                    'Selected IMEI', scanMetadata['selected_imei']?.toString()),
                _infoRow(
                  'Selected Serial',
                  scanMetadata['selected_serial']?.toString(),
                ),
                _infoRow(
                  'Confidence',
                  scanMetadata['confidence']?.toString(),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildIncomeInfo() {
    final income = customer.income;

    return Column(
      children: [
        _infoRow('Occupation', income['occupation']?.toString()),
        _infoRow('Employer', income['employer']?.toString()),
        _infoRow('Work Location', income['work_location']?.toString()),
        _infoRow('Monthly Income', income['monthly_income']?.toString()),
        _infoRow('Monthly Expenses', income['monthly_expenses']?.toString()),
        _infoRow('Income Cycle', income['income_payment_cycle']?.toString()),
        _infoRow('Duration at Work', income['duration_at_work']?.toString()),
      ],
    );
  }

  Widget _buildNokInfo() {
    final nok = customer.nok;

    return Column(
      children: [
        _infoRow('Primary NOK', nok['nok_name']?.toString()),
        _infoRow(
            'NOK Phone',
            nok['nok_phone_display']?.toString() ??
                nok['nok_phone']?.toString()),
        _infoRow('Relationship', nok['nok_relationship']?.toString()),
        _infoRow('Second NOK', nok['nok2_name']?.toString()),
        _infoRow(
            'NOK 2 Phone',
            nok['nok2_phone_display']?.toString() ??
                nok['nok2_phone']?.toString()),
        _infoRow('Second Relationship', nok['nok2_relationship']?.toString()),
      ],
    );
  }

  Widget _buildPaymentAgreement() {
    final payment = customer.payment;
    final agreement = customer.agreement;
    final paymentSuccess = payment?.isCompleted == true;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _statusTile(
          title: paymentSuccess ? 'Deposit Paid' : 'Payment Pending',
          subtitle: paymentSuccess
              ? 'Customer payment succeeded and is attached to this application.'
              : 'Payment is still pending or has not started.',
          color: paymentSuccess ? AppConstants.success : AppConstants.warning,
          icon: paymentSuccess
              ? Icons.check_circle_outline
              : Icons.hourglass_top_rounded,
        ),
        const SizedBox(height: 12),
        _infoRow('Payment Status', payment?.status),
        _infoRow('Gateway Status', payment?.paymentStatus),
        _infoRow('Reference', payment?.reference),
        _infoRow('Order ID', payment?.orderId),
        _infoRow('Trans ID', payment?.transId),
        _infoRow('Amount', payment?.amount?.toString()),
        _infoRow('Phone', payment?.phone),
        _infoRow('Paid At', payment?.paidAt),
        const Divider(height: 24),
        _statusTile(
          title: agreement?.accepted == true
              ? 'Agreement Accepted'
              : 'Agreement Not Accepted',
          subtitle: agreement?.activeDocument != null
              ? agreement!.activeDocument!.title
              : 'Admin has not uploaded an active customer agreement yet.',
          color: agreement?.accepted == true
              ? AppConstants.success
              : AppConstants.info,
          icon: Icons.picture_as_pdf_outlined,
        ),
        const SizedBox(height: 12),
        _infoRow('Presented At', agreement?.presentedAt),
        _infoRow('Decision At', agreement?.decisionAt),
        _infoRow('Handover Notes', agreement?.handoverNotes),
        if (agreement?.activeDocument?.url != null) ...[
          const SizedBox(height: 8),
          AppButton(
            label: 'Copy Agreement Link',
            outlined: true,
            icon: Icons.copy_rounded,
            onPressed: () async {
              await Clipboard.setData(
                ClipboardData(text: agreement!.activeDocument!.url),
              );
              if (!mounted) {
                return;
              }
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Agreement link copied.'),
                  backgroundColor: AppConstants.success,
                ),
              );
            },
          ),
        ],
      ],
    );
  }

  Widget _buildPhotos() {
    final entries = customer.photos.entries
        .where((entry) => entry.value != null && entry.value!.isNotEmpty)
        .toList();

    if (entries.isEmpty) {
      return const Text(
        'No photos or files uploaded yet.',
        style: TextStyle(
          fontSize: 12,
          color: AppConstants.textSecondary,
        ),
      );
    }

    final labels = {
      'imei': 'IMEI Sticker',
      'device_box': 'Device Box',
      'device': 'Device',
      'id_front': 'ID Front',
      'id_back': 'ID Back',
      'headshot': 'Headshot',
      'client_fo': 'Customer + FO',
      'business': 'Business Photo',
      'customer_signature': 'Customer Signature',
      'fo_signature': 'FO Signature',
      'asset_handover_list': 'Handover Checklist',
    };

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 10,
      mainAxisSpacing: 10,
      childAspectRatio: 1.05,
      children: entries.map((entry) {
        final url = entry.value!;
        final isPdf = url.toLowerCase().contains('.pdf');

        return Container(
          decoration: BoxDecoration(
            color: AppConstants.borderLight,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppConstants.border),
          ),
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () async {
              await Clipboard.setData(ClipboardData(text: url));
              if (!mounted) {
                return;
              }
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('File link copied.'),
                  backgroundColor: AppConstants.success,
                ),
              );
            },
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: ClipRRect(
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(13),
                    ),
                    child: isPdf
                        ? Container(
                            color: const Color(0xFFFFF7ED),
                            alignment: Alignment.center,
                            child: const Icon(
                              Icons.picture_as_pdf_outlined,
                              size: 44,
                              color: AppConstants.primary,
                            ),
                          )
                        : Image.network(
                            url,
                            width: double.infinity,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => Container(
                              color: AppConstants.borderLight,
                              alignment: Alignment.center,
                              child: const Icon(
                                Icons.broken_image_outlined,
                                color: AppConstants.textHint,
                              ),
                            ),
                          ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(10),
                  child: Text(
                    labels[entry.key] ?? entry.key,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppConstants.textPrimary,
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildVerification(Map<String, dynamic> verification) {
    return Column(
      children: [
        _infoRow('Status', verification['status']?.toString()),
        _infoRow('Stage', verification['stage_label']?.toString()),
        _infoRow('Auto Check', verification['auto_check_status']?.toString()),
        _infoRow('Reviewed By', verification['reviewed_by']?.toString()),
        _infoRow('Reviewed At', verification['reviewed_at']?.toString()),
        _infoRow('Submitted At', verification['submitted_at']?.toString()),
        _infoRow(
            'Rejection Reason', verification['rejection_reason']?.toString()),
        _infoRow('Notes', verification['notes']?.toString()),
      ],
    );
  }

  Widget _buildRelease() {
    final release = customer.release;
    final canRelease = customer.canReleaseAsset;
    final isReleased = release?.status == 'released';
    final blockers = release?.eligibilityBlockers ?? const <String>[];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _statusTile(
          title: isReleased
              ? 'Asset Released'
              : canRelease
                  ? 'Ready for Asset Release'
                  : 'Waiting for Approval or Final Requirements',
          subtitle: isReleased
              ? 'This device has already been handed to the customer.'
              : canRelease
                  ? 'All requirements are met. The officer can now release the linked asset.'
                  : 'Payment, agreement, signatures, handover, and approved KYC must all be in place.',
          color: isReleased
              ? AppConstants.success
              : canRelease
                  ? AppConstants.primary
                  : AppConstants.warning,
          icon: Icons.inventory_2_outlined,
        ),
        if (!isReleased && blockers.isNotEmpty) ...[
          const SizedBox(height: 12),
          GlassCard(
            tint: AppConstants.warning.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(18),
            borderColor: AppConstants.warning.withValues(alpha: 0.22),
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Still needed before release',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    color: AppConstants.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                ...blockers.map(
                  (b) => Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          '• ',
                          style: TextStyle(
                            fontSize: 13,
                            color: AppConstants.textSecondary,
                          ),
                        ),
                        Expanded(
                          child: Text(
                            b,
                            style: const TextStyle(
                              fontSize: 13,
                              height: 1.35,
                              color: AppConstants.textSecondary,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        if (_needsHandoverChecklistUpload(customer)) ...[
          const SizedBox(height: 12),
          Text(
            'Checklist ni fomu iliyosainiwa inayoonyesha kifaa kilichopewa mteja (picha ya PDF/print).',
            style: TextStyle(
              fontSize: 12,
              height: 1.4,
              color: Theme.of(context).textTheme.bodyMedium?.color,
            ),
          ),
          const SizedBox(height: 10),
          AppButton(
            label: 'Pakua checklist ya handover',
            icon: Icons.upload_file_outlined,
            isLoading: _uploadingHandover,
            outlined: true,
            width: double.infinity,
            onPressed: _uploadingHandover ? null : _uploadHandoverChecklist,
          ),
        ],
        const SizedBox(height: 12),
        _infoRow('Release Status', release?.status),
        _infoRow('Released At', release?.releasedAt),
        _infoRow('Released By', release?.releasedBy),
        _infoRow('Inventory Unit', release?.inventoryUnitId),
        _infoRow('Inventory Status', release?.inventoryUnitStatus),
        if (canRelease && !isReleased) ...[
          const SizedBox(height: 8),
          AppButton(
            label: 'Release Asset Now',
            icon: Icons.inventory_2_outlined,
            isLoading: _releasing,
            onPressed: _releaseAsset,
          ),
        ],
      ],
    );
  }

  Widget _resumeDraftCard(BuildContext context) {
    return GlassCard(
      tint: AppConstants.infoSurface,
      borderRadius: BorderRadius.circular(18),
      borderColor: AppConstants.info.withValues(alpha: 0.22),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(
                Icons.edit_note_rounded,
                color: AppConstants.info,
                size: 18,
              ),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Draft application can continue',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: AppConstants.textPrimary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'This file is still in draft flow. Continue from step ${customer.resumeStep} to update or finish the application cleanly.',
            style: const TextStyle(
              fontSize: 12,
              height: 1.45,
              color: AppConstants.textSecondary,
            ),
          ),
          const SizedBox(height: 12),
          AppButton(
            label: 'Resume Draft',
            icon: Icons.arrow_forward_rounded,
            onPressed: () => context.go('/kyc/new?draft=${widget.customerId}'),
          ),
        ],
      ),
    );
  }

  Widget _statusTile({
    required String title,
    required String subtitle,
    required Color color,
    required IconData icon,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.16)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: const TextStyle(
                    fontSize: 12,
                    height: 1.45,
                    color: AppConstants.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(String label, String? value) {
    if (value == null || value.isEmpty) {
      return const SizedBox.shrink();
    }

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
                fontWeight: FontWeight.w600,
                color: AppConstants.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppConstants.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
