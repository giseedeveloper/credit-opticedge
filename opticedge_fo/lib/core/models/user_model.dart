class UserModel {
  final String id;
  final String name;
  final String email;
  final String? phone;
  final String? role;
  final BranchInfo? branch;
  final String? avatarUrl;
  final bool isActive;
  final bool canRegisterCustomers;
  final bool isAdmin;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.role,
    this.branch,
    this.avatarUrl,
    this.isActive = true,
    this.canRegisterCustomers = false,
    this.isAdmin = false,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    final perms = json['permissions'] as Map<String, dynamic>? ?? {};
    return UserModel(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString(),
      role: json['role']?.toString(),
      branch: json['branch'] != null
          ? BranchInfo.fromJson(json['branch'] as Map<String, dynamic>)
          : null,
      avatarUrl: json['avatar_url']?.toString(),
      isActive: json['is_active'] == true,
      canRegisterCustomers: perms['can_register_customers'] == true,
      isAdmin: perms['is_admin'] == true,
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
        'avatar_url': avatarUrl,
        'is_active': isActive,
        'permissions': {
          'can_register_customers': canRegisterCustomers,
          'is_admin': isAdmin,
        },
      };
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
