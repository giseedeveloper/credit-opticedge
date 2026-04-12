class CustomerProfile {
  final String id;
  final String fullName;
  final String firstName;
  final String lastName;
  final String phone;
  final String? phoneDisplay;
  final String? email;
  final String? gender;
  final String? nidaNumber;
  final String? headshotUrl;
  final BranchInfo? branch;
  final VendorInfo? vendor;

  CustomerProfile({
    required this.id,
    required this.fullName,
    required this.firstName,
    required this.lastName,
    required this.phone,
    this.phoneDisplay,
    this.email,
    this.gender,
    this.nidaNumber,
    this.headshotUrl,
    this.branch,
    this.vendor,
  });

  factory CustomerProfile.fromJson(Map<String, dynamic> json) {
    return CustomerProfile(
      id: json['id'] as String,
      fullName: json['full_name'] as String? ?? '',
      firstName: json['first_name'] as String? ?? '',
      lastName: json['last_name'] as String? ?? '',
      phone: json['phone'] as String? ?? '',
      phoneDisplay: json['phone_display'] as String?,
      email: json['email'] as String?,
      gender: json['gender'] as String?,
      nidaNumber: json['nida_number'] as String?,
      headshotUrl: json['headshot_url'] as String?,
      branch: json['branch'] != null ? BranchInfo.fromJson(json['branch']) : null,
      vendor: json['vendor'] != null ? VendorInfo.fromJson(json['vendor']) : null,
    );
  }
}

class BranchInfo {
  final String id;
  final String name;
  final String? phone;
  final String? region;
  final String? address;

  BranchInfo({required this.id, required this.name, this.phone, this.region, this.address});

  factory BranchInfo.fromJson(Map<String, dynamic> json) {
    return BranchInfo(
      id: json['id'] as String,
      name: json['name'] as String? ?? '',
      phone: json['phone'] as String?,
      region: json['region'] as String?,
      address: json['address'] as String?,
    );
  }
}

class VendorInfo {
  final String id;
  final String name;
  final String? phone;
  final String? address;

  VendorInfo({required this.id, required this.name, this.phone, this.address});

  factory VendorInfo.fromJson(Map<String, dynamic> json) {
    return VendorInfo(
      id: json['id'] as String,
      name: json['name'] as String? ?? '',
      phone: json['phone'] as String?,
      address: json['address'] as String?,
    );
  }
}
