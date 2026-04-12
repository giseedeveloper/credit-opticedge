import 'kyc_flow_model.dart';

class CustomerListItem {
  final String id;
  final String fullName;
  final String phone;
  final String? gender;
  final String kycStatus;
  final String? autoCheck;
  final String? branch;
  final String? headshotUrl;
  final String registeredAt;

  const CustomerListItem({
    required this.id,
    required this.fullName,
    required this.phone,
    this.gender,
    this.kycStatus = 'draft',
    this.autoCheck,
    this.branch,
    this.headshotUrl,
    required this.registeredAt,
  });

  factory CustomerListItem.fromJson(Map<String, dynamic> json) =>
      CustomerListItem(
        id: json['id']?.toString() ?? '',
        fullName: json['full_name']?.toString() ?? '',
        phone: json['phone']?.toString() ?? '',
        gender: json['gender']?.toString(),
        kycStatus: json['kyc_status']?.toString() ?? 'draft',
        autoCheck: json['auto_check']?.toString(),
        branch: json['branch']?.toString(),
        headshotUrl: json['headshot_url']?.toString(),
        registeredAt: json['registered_at']?.toString() ?? '',
      );
}

class CustomerDetail {
  final String id;
  final String fullName;
  final String firstName;
  final String? middleName;
  final String lastName;
  final String? gender;
  final String? dateOfBirth;
  final String? nidaNumber;
  final String? idType;
  final String phone;
  final String? altPhone;
  final String? email;
  final String? address;
  final String? landmark;
  final String? region;
  final String? district;
  final double? latitude;
  final double? longitude;
  final Map<String, dynamic>? branch;
  final Map<String, dynamic>? vendor;
  final Map<String, dynamic> device;
  final Map<String, dynamic> income;
  final Map<String, dynamic> nok;
  final Map<String, dynamic> consent;
  final Map<String, dynamic> phoneMetadata;
  final Map<String, String?> photos;
  final String? foNotes;
  final String? applicationSource;
  final String kycStatus;
  final String registeredAt;
  final Map<String, dynamic>? verification;
  final KycPaymentContext? payment;
  final KycAgreementContext? agreement;
  final KycReleaseContext? release;
  final bool canReleaseAsset;
  final bool canResumeDraft;
  final int resumeStep;

  const CustomerDetail({
    required this.id,
    required this.fullName,
    required this.firstName,
    this.middleName,
    required this.lastName,
    this.gender,
    this.dateOfBirth,
    this.nidaNumber,
    this.idType,
    required this.phone,
    this.altPhone,
    this.email,
    this.address,
    this.landmark,
    this.region,
    this.district,
    this.latitude,
    this.longitude,
    this.branch,
    this.vendor,
    this.device = const {},
    this.income = const {},
    this.nok = const {},
    this.consent = const {},
    this.phoneMetadata = const {},
    this.photos = const {},
    this.foNotes,
    this.applicationSource,
    this.kycStatus = 'draft',
    required this.registeredAt,
    this.verification,
    this.payment,
    this.agreement,
    this.release,
    this.canReleaseAsset = false,
    this.canResumeDraft = false,
    this.resumeStep = 2,
  });

  factory CustomerDetail.fromJson(Map<String, dynamic> json) {
    final photosRaw = _jsonMap(json['photos']) ?? {};
    final photos = photosRaw.map((k, v) => MapEntry(k, v?.toString()));

    return CustomerDetail(
      id: json['id']?.toString() ?? '',
      fullName: json['full_name']?.toString() ?? '',
      firstName: json['first_name']?.toString() ?? '',
      middleName: json['middle_name']?.toString(),
      lastName: json['last_name']?.toString() ?? '',
      gender: json['gender']?.toString(),
      dateOfBirth: json['date_of_birth']?.toString(),
      nidaNumber: json['nida_number']?.toString(),
      idType: json['id_type']?.toString(),
      phone: json['phone']?.toString() ?? '',
      altPhone: json['alt_phone']?.toString(),
      email: json['email']?.toString(),
      address: json['address']?.toString(),
      landmark: json['landmark']?.toString(),
      region: json['region']?.toString(),
      district: json['district']?.toString(),
      latitude: _nullableDouble(json['latitude']),
      longitude: _nullableDouble(json['longitude']),
      branch: _jsonMap(json['branch']),
      vendor: _jsonMap(json['vendor']),
      device: _jsonMap(json['device']) ?? {},
      income: _jsonMap(json['income']) ?? {},
      nok: _jsonMap(json['nok']) ?? {},
      consent: _jsonMap(json['consent']) ?? {},
      phoneMetadata: _jsonMap(json['phone_metadata']) ?? {},
      photos: photos,
      foNotes: json['fo_notes']?.toString(),
      applicationSource: json['application_source']?.toString(),
      kycStatus: json['kyc_status']?.toString() ?? 'draft',
      registeredAt: json['registered_at']?.toString() ?? '',
      verification: _jsonMap(json['verification']),
      payment: _jsonMap(json['payment']) != null
          ? KycPaymentContext.fromJson(_jsonMap(json['payment'])!)
          : null,
      agreement: _jsonMap(json['agreement']) != null
          ? KycAgreementContext.fromJson(_jsonMap(json['agreement'])!)
          : null,
      release: _jsonMap(json['release']) != null
          ? KycReleaseContext.fromJson(_jsonMap(json['release'])!)
          : null,
      canReleaseAsset: json['can_release_asset'] == true,
      canResumeDraft: json['can_resume_draft'] == true,
      resumeStep: _nullableInt(json['resume_step']) ?? 2,
    );
  }
}

Map<String, dynamic>? _jsonMap(dynamic value) {
  if (value is Map<String, dynamic>) {
    return value;
  }
  if (value is Map) {
    return Map<String, dynamic>.from(value);
  }

  return null;
}

double? _nullableDouble(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is num) {
    return value.toDouble();
  }
  if (value is String) {
    return double.tryParse(value);
  }

  return null;
}

int? _nullableInt(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  if (value is String) {
    return int.tryParse(value);
  }

  return null;
}
