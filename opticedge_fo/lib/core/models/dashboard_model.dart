class DashboardAction {
  final String key;
  final String title;
  final String subtitle;
  final int count;
  final String tab;
  final String severity;

  const DashboardAction({
    required this.key,
    required this.title,
    required this.subtitle,
    required this.count,
    required this.tab,
    this.severity = 'info',
  });

  factory DashboardAction.fromJson(Map<String, dynamic> json) => DashboardAction(
        key: json['key']?.toString() ?? '',
        title: json['title']?.toString() ?? '',
        subtitle: json['subtitle']?.toString() ?? '',
        count: (json['count'] as num?)?.toInt() ?? 0,
        tab: json['tab']?.toString() ?? 'all',
        severity: json['severity']?.toString() ?? 'info',
      );
}

class DashboardStats {
  final int totalRegistered;
  final int pending;
  final int verified;
  final int declined;
  final int drafts;
  final int staleDrafts;
  final int faceReview;
  final int readyForRelease;
  final int actionableCount;
  final List<DashboardAction> actions;

  const DashboardStats({
    this.totalRegistered = 0,
    this.pending = 0,
    this.verified = 0,
    this.declined = 0,
    this.drafts = 0,
    this.staleDrafts = 0,
    this.faceReview = 0,
    this.readyForRelease = 0,
    this.actionableCount = 0,
    this.actions = const [],
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    final actionsJson = json['actions'] as List<dynamic>? ?? [];

    return DashboardStats(
      totalRegistered: (json['total_registered'] as num?)?.toInt() ?? 0,
      pending: (json['pending'] as num?)?.toInt() ?? 0,
      verified: (json['verified'] as num?)?.toInt() ?? 0,
      declined: (json['declined'] as num?)?.toInt() ?? 0,
      drafts: (json['drafts'] as num?)?.toInt() ?? 0,
      staleDrafts: (json['stale_drafts'] as num?)?.toInt() ?? 0,
      faceReview: (json['face_review'] as num?)?.toInt() ?? 0,
      readyForRelease: (json['ready_for_release'] as num?)?.toInt() ?? 0,
      actionableCount: (json['actionable_count'] as num?)?.toInt() ?? 0,
      actions: actionsJson
          .map((e) => DashboardAction.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

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
