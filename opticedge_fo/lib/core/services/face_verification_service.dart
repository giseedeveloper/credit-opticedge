import 'dart:io';

import 'package:dio/dio.dart';

import '../api/api_client.dart';

/// Result from face verification API
class FaceVerificationResult {
  final bool passed;
  final String status;
  final double score;
  final String? reason;
  final bool alert;
  final String? headshotUrl;
  final String? idFrontUrl;

  const FaceVerificationResult({
    required this.passed,
    required this.status,
    required this.score,
    this.reason,
    required this.alert,
    this.headshotUrl,
    this.idFrontUrl,
  });

  factory FaceVerificationResult.fromJson(Map<String, dynamic> json) {
    final faceMatch = json['face_match'] as Map<String, dynamic>? ?? {};
    return FaceVerificationResult(
      passed: json['passed'] as bool? ?? false,
      status: faceMatch['status'] as String? ?? 'pending',
      score: (faceMatch['score'] as num?)?.toDouble() ?? 0.0,
      reason: faceMatch['reason'] as String?,
      alert: faceMatch['alert'] as bool? ?? false,
      headshotUrl: json['headshot_url'] as String?,
      idFrontUrl: json['id_front_url'] as String?,
    );
  }

  /// Auto-pass from model **or** back-office manual verification (if ever returned on verify).
  bool get isFaceStepComplete =>
      passed || status == 'manual_verified';

  bool get isReviewBand => status == 'review';

  bool get isFailedBand => status == 'failed';
}

/// Status response from face status API
class FaceVerificationStatus {
  final String customerId;
  final bool hasIdFront;
  final bool hasHeadshot;
  final String? idFrontUrl;
  final String? headshotUrl;
  final FaceMatchInfo? faceMatch;

  const FaceVerificationStatus({
    required this.customerId,
    required this.hasIdFront,
    required this.hasHeadshot,
    this.idFrontUrl,
    this.headshotUrl,
    this.faceMatch,
  });

  factory FaceVerificationStatus.fromJson(Map<String, dynamic> json) {
    final faceMatchJson = json['face_match'] as Map<String, dynamic>?;
    return FaceVerificationStatus(
      customerId: json['customer_id'] as String? ?? '',
      hasIdFront: json['has_id_front'] as bool? ?? false,
      hasHeadshot: json['has_headshot'] as bool? ?? false,
      idFrontUrl: json['id_front_url'] as String?,
      headshotUrl: json['headshot_url'] as String?,
      faceMatch:
          faceMatchJson != null ? FaceMatchInfo.fromJson(faceMatchJson) : null,
    );
  }
}

class FaceMatchInfo {
  final String status;
  final double score;
  final String? reason;
  final String? ranAt;
  final bool alert;
  final String? manualVerifiedBy;
  final String? manualVerifiedAt;

  const FaceMatchInfo({
    required this.status,
    required this.score,
    this.reason,
    this.ranAt,
    required this.alert,
    this.manualVerifiedBy,
    this.manualVerifiedAt,
  });

  factory FaceMatchInfo.fromJson(Map<String, dynamic> json) {
    return FaceMatchInfo(
      status: json['status'] as String? ?? 'pending',
      score: (json['score'] as num?)?.toDouble() ?? 0.0,
      reason: json['reason'] as String?,
      ranAt: json['ran_at'] as String?,
      alert: json['alert'] as bool? ?? false,
      manualVerifiedBy: json['manual_verified_by'] as String?,
      manualVerifiedAt: json['manual_verified_at'] as String?,
    );
  }
}

/// Service for face verification API calls
class FaceVerificationService {
  FaceVerificationService._();
  static final instance = FaceVerificationService._();

  /// Upload ID front photo
  /// Returns the URL of the stored photo
  Future<String> uploadIdPhoto(String customerId, File idFrontPhoto) async {
    final formData = FormData.fromMap({
      'id_front_photo': await MultipartFile.fromFile(
        idFrontPhoto.path,
        filename: 'id_front.jpg',
      ),
    });

    final response = await ApiClient.instance.postForm(
      '/kyc/application/$customerId/face/id-photo',
      formData,
    );

    final data = response.data['data'] as Map<String, dynamic>;
    return data['id_front_url'] as String? ?? '';
  }

  /// Submit face frame for verification
  /// This runs the face match synchronously and returns the result
  Future<FaceVerificationResult> verifyFace(
    String customerId,
    File faceFrame,
  ) async {
    final formData = FormData.fromMap({
      'face_frame': await MultipartFile.fromFile(
        faceFrame.path,
        filename: 'face_frame.jpg',
      ),
    });

    final response = await ApiClient.instance.postForm(
      '/kyc/application/$customerId/face/verify',
      formData,
    );

    return FaceVerificationResult.fromJson(
      response.data['data'] as Map<String, dynamic>,
    );
  }

  /// Get current face verification status
  Future<FaceVerificationStatus> getStatus(String customerId) async {
    final response = await ApiClient.instance.get(
      '/kyc/application/$customerId/face/status',
    );

    return FaceVerificationStatus.fromJson(
      response.data['data'] as Map<String, dynamic>,
    );
  }
}
