class KycApprovalQueueItem {
  final String id;
  final String fullName;
  final String phone;
  final String? kycStatus;
  final int? kycStage;
  final int? verificationStage;
  final String? verificationStatus;
  final String? faceMatchStatus;

  const KycApprovalQueueItem({
    required this.id,
    required this.fullName,
    required this.phone,
    this.kycStatus,
    this.kycStage,
    this.verificationStage,
    this.verificationStatus,
    this.faceMatchStatus,
  });

  factory KycApprovalQueueItem.fromJson(Map<String, dynamic> json) {
    final verification = json['verification'];
    Map<String, dynamic>? vMap;
    if (verification is Map<String, dynamic>) {
      vMap = verification;
    } else if (verification is Map) {
      vMap = Map<String, dynamic>.from(verification);
    }

    return KycApprovalQueueItem(
      id: json['id']?.toString() ?? '',
      fullName: json['full_name']?.toString() ?? 'Customer',
      phone: json['phone']?.toString() ?? '',
      kycStatus: json['kyc_status']?.toString(),
      kycStage: (json['kyc_stage'] as num?)?.toInt(),
      verificationStage: (vMap?['stage'] as num?)?.toInt(),
      verificationStatus: vMap?['status']?.toString(),
      faceMatchStatus: vMap?['face_match_status']?.toString(),
    );
  }
}

class KycApprovalDetail {
  final String id;
  final String fullName;
  final String phone;
  final String? nidaNumber;
  final String? kycStatus;
  final int? kycStage;
  final String? assetReleaseStatus;
  final Map<String, dynamic>? verification;

  const KycApprovalDetail({
    required this.id,
    required this.fullName,
    required this.phone,
    this.nidaNumber,
    this.kycStatus,
    this.kycStage,
    this.assetReleaseStatus,
    this.verification,
  });

  factory KycApprovalDetail.fromJson(Map<String, dynamic> json) {
    final verification = json['verification'];
    return KycApprovalDetail(
      id: json['id']?.toString() ?? '',
      fullName: json['full_name']?.toString() ?? 'Customer',
      phone: json['phone']?.toString() ?? '',
      nidaNumber: json['nida_number']?.toString(),
      kycStatus: json['kyc_status']?.toString(),
      kycStage: (json['kyc_stage'] as num?)?.toInt(),
      assetReleaseStatus: json['asset_release_status']?.toString(),
      verification: verification is Map<String, dynamic>
          ? verification
          : verification is Map
              ? Map<String, dynamic>.from(verification)
              : null,
    );
  }
}
