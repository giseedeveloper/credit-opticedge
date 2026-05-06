class TransactionModel {
  final String id;
  final String? reference;
  final String? type;
  final double amount;
  final String? channel;
  final String? externalReference;
  final String? description;
  final String? transactedAt;

  TransactionModel({
    required this.id,
    this.reference,
    this.type,
    required this.amount,
    this.channel,
    this.externalReference,
    this.description,
    this.transactedAt,
  });

  factory TransactionModel.fromJson(Map<String, dynamic> json) {
    double amt = 0;
    final raw = json['amount'];
    if (raw is num) amt = raw.toDouble();
    if (raw is String) amt = double.tryParse(raw) ?? 0;

    return TransactionModel(
      id: json['id']?.toString() ?? '',
      reference: json['reference'] as String?,
      type: json['type'] as String?,
      amount: amt,
      channel: json['channel'] as String?,
      externalReference: json['external_reference'] as String?,
      description: json['description'] as String?,
      transactedAt: json['transacted_at'] as String?,
    );
  }
}
