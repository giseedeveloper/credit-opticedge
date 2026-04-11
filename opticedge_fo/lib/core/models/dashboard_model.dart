class DashboardStats {
  final int totalRegistered;
  final int pending;
  final int verified;
  final int declined;
  final int drafts;

  const DashboardStats({
    this.totalRegistered = 0,
    this.pending = 0,
    this.verified = 0,
    this.declined = 0,
    this.drafts = 0,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) => DashboardStats(
        totalRegistered: (json['total_registered'] as num?)?.toInt() ?? 0,
        pending: (json['pending'] as num?)?.toInt() ?? 0,
        verified: (json['verified'] as num?)?.toInt() ?? 0,
        declined: (json['declined'] as num?)?.toInt() ?? 0,
        drafts: (json['drafts'] as num?)?.toInt() ?? 0,
      );

  static const DashboardStats empty = DashboardStats();
}

class BranchModel {
  final String id;
  final String name;

  const BranchModel({required this.id, required this.name});

  factory BranchModel.fromJson(Map<String, dynamic> json) => BranchModel(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
      );
}
