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
  final Map<String, dynamic> device;
  final Map<String, dynamic> income;
  final Map<String, dynamic> nok;
  final Map<String, dynamic> consent;
  final Map<String, String?> photos;
  final String? foNotes;
  final String? applicationSource;
  final String kycStatus;
  final String registeredAt;
  final Map<String, dynamic>? verification;

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
    this.device = const {},
    this.income = const {},
    this.nok = const {},
    this.consent = const {},
    this.photos = const {},
    this.foNotes,
    this.applicationSource,
    this.kycStatus = 'draft',
    required this.registeredAt,
    this.verification,
  });

  factory CustomerDetail.fromJson(Map<String, dynamic> json) {
    final photosRaw = json['photos'] as Map<String, dynamic>? ?? {};
    final photos = photosRaw.map(
        (k, v) => MapEntry(k, v?.toString()));
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
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
      branch: json['branch'] as Map<String, dynamic>?,
      device: json['device'] as Map<String, dynamic>? ?? {},
      income: json['income'] as Map<String, dynamic>? ?? {},
      nok: json['nok'] as Map<String, dynamic>? ?? {},
      consent: json['consent'] as Map<String, dynamic>? ?? {},
      photos: photos,
      foNotes: json['fo_notes']?.toString(),
      applicationSource: json['application_source']?.toString(),
      kycStatus: json['kyc_status']?.toString() ?? 'draft',
      registeredAt: json['registered_at']?.toString() ?? '',
      verification: json['verification'] as Map<String, dynamic>?,
    );
  }
}
