class UserModel {
  final String id;
  final String name;
  final String email;
  final String? phone;
  final String? role;
  final BranchInfo? branch;
  final DealerInfo? dealer;
  final String? avatarUrl;
  final bool isActive;
  final bool canRegisterCustomers;
  final bool canViewStock;
  final bool canViewStaffMetrics;
  final bool canViewRecovery;
  final bool canViewReports;
  final bool isAdmin;
  final List<String> apiPermissions;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.role,
    this.branch,
    this.dealer,
    this.avatarUrl,
    this.isActive = true,
    this.canRegisterCustomers = false,
    this.canViewStock = false,
    this.canViewStaffMetrics = false,
    this.canViewRecovery = false,
    this.canViewReports = false,
    this.isAdmin = false,
    this.apiPermissions = const [],
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    final perms = _asMap(json['permissions']) ?? const <String, dynamic>{};
    return UserModel(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString(),
      role: json['role']?.toString(),
      branch: _asMap(json['branch']) != null
          ? BranchInfo.fromJson(_asMap(json['branch'])!)
          : null,
      dealer: _asMap(json['dealer']) != null
          ? DealerInfo.fromJson(_asMap(json['dealer'])!)
          : null,
      avatarUrl: json['avatar_url']?.toString(),
      isActive: json['is_active'] == true,
      canRegisterCustomers: perms['can_register_customers'] == true,
      canViewStock: perms['can_view_stock'] == true,
      canViewStaffMetrics: perms['can_view_staff_metrics'] == true,
      canViewRecovery: perms['can_view_recovery'] == true,
      canViewReports: perms['can_view_reports'] == true,
      isAdmin: perms['is_admin'] == true,
      apiPermissions: (json['api_permissions'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          const [],
    );
  }

  String get initials {
    final parts = name.trim().split(' ');
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return name.isNotEmpty ? name[0].toUpperCase() : 'FO';
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'phone': phone,
        'role': role,
        'branch': branch?.toJson(),
        'dealer': dealer?.toJson(),
        'avatar_url': avatarUrl,
        'is_active': isActive,
        'api_permissions': apiPermissions,
        'permissions': {
          'can_register_customers': canRegisterCustomers,
          'can_view_stock': canViewStock,
          'can_view_staff_metrics': canViewStaffMetrics,
          'can_view_recovery': canViewRecovery,
          'can_view_reports': canViewReports,
          'is_admin': isAdmin,
        },
      };
}

Map<String, dynamic>? _asMap(dynamic value) {
  if (value is Map<String, dynamic>) {
    return value;
  }
  if (value is Map) {
    return Map<String, dynamic>.from(value);
  }

  return null;
}

class BranchInfo {
  final String id;
  final String name;

  const BranchInfo({required this.id, required this.name});

  factory BranchInfo.fromJson(Map<String, dynamic> json) => BranchInfo(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name};
}

class DealerInfo {
  final String id;
  final String name;

  const DealerInfo({required this.id, required this.name});

  factory DealerInfo.fromJson(Map<String, dynamic> json) => DealerInfo(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name};
}
