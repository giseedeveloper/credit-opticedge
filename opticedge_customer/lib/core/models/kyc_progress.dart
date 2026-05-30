class KycVerificationProgress {
  final int stage;
  final String status;
  final String? faceMatchStatus;
  final String? stage1Status;
  final String? stage2Status;
  final String? stage3Status;
  final String? stage4Status;

  const KycVerificationProgress({
    required this.stage,
    required this.status,
    this.faceMatchStatus,
    this.stage1Status,
    this.stage2Status,
    this.stage3Status,
    this.stage4Status,
  });

  factory KycVerificationProgress.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const KycVerificationProgress(stage: 1, status: 'pending');
    }

    return KycVerificationProgress(
      stage: (json['stage'] as num?)?.toInt() ?? 1,
      status: json['status']?.toString() ?? 'pending',
      faceMatchStatus: json['face_match_status']?.toString(),
      stage1Status: json['stage1_status']?.toString(),
      stage2Status: json['stage2_status']?.toString(),
      stage3Status: json['stage3_status']?.toString(),
      stage4Status: json['stage4_status']?.toString(),
    );
  }
}

class KycProgressSnapshot {
  final String eligibility;
  final String? customerName;
  final String? kycStatus;
  final int? kycStage;
  final String? assetReleaseStatus;
  final String portalMessage;
  final KycVerificationProgress? verification;

  const KycProgressSnapshot({
    required this.eligibility,
    required this.portalMessage,
    this.customerName,
    this.kycStatus,
    this.kycStage,
    this.assetReleaseStatus,
    this.verification,
  });

  bool get isInProgress => eligibility == 'kyc_in_progress';

  bool get isPortalActive => eligibility == 'portal_active';

  bool get isRejected => kycStatus == 'rejected';

  factory KycProgressSnapshot.fromJson(Map<String, dynamic> json) {
    final verificationRaw = json['verification'];
    return KycProgressSnapshot(
      eligibility: json['eligibility']?.toString() ?? 'unknown',
      customerName: json['customer_name']?.toString(),
      kycStatus: json['kyc_status']?.toString(),
      kycStage: (json['kyc_stage'] as num?)?.toInt(),
      assetReleaseStatus: json['asset_release_status']?.toString(),
      portalMessage: json['portal_message']?.toString() ??
          'Maombi yako yako chini ya ukaguzi.',
      verification: verificationRaw is Map<String, dynamic>
          ? KycVerificationProgress.fromJson(verificationRaw)
          : null,
    );
  }
}
