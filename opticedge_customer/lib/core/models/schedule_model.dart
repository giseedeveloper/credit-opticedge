class ScheduleItem {
  final String id;
  final int installmentNumber;
  final double amountDue;
  final double principalComponent;
  final double interestComponent;
  final double penaltyComponent;
  final double amountPaid;
  final double balanceRemaining;
  final String? dueDate;
  final String? paidAt;
  final String status;
  final int daysOverdue;

  ScheduleItem({
    required this.id,
    required this.installmentNumber,
    required this.amountDue,
    required this.principalComponent,
    required this.interestComponent,
    required this.penaltyComponent,
    required this.amountPaid,
    required this.balanceRemaining,
    this.dueDate,
    this.paidAt,
    required this.status,
    required this.daysOverdue,
  });

  factory ScheduleItem.fromJson(Map<String, dynamic> json) {
    return ScheduleItem(
      id: json['id'] as String,
      installmentNumber: (json['installment_number'] as num?)?.toInt() ?? 0,
      amountDue: _d(json['amount_due']),
      principalComponent: _d(json['principal_component']),
      interestComponent: _d(json['interest_component']),
      penaltyComponent: _d(json['penalty_component']),
      amountPaid: _d(json['amount_paid']),
      balanceRemaining: _d(json['balance_remaining']),
      dueDate: json['due_date'] as String?,
      paidAt: json['paid_at'] as String?,
      status: json['status'] as String? ?? 'pending',
      daysOverdue: (json['days_overdue'] as num?)?.toInt() ?? 0,
    );
  }

  bool get isPaid => status == 'paid';
  bool get isOverdue => status == 'overdue';
  bool get isPending => status == 'pending' || status == 'partial';

  static double _d(dynamic v) {
    if (v == null) return 0.0;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString()) ?? 0.0;
  }
}

class ScheduleResponse {
  final String loanId;
  final String loanNumber;
  final int totalInstallments;
  final int paidInstallments;
  final ScheduleItem? nextDue;
  final List<ScheduleItem> schedule;

  ScheduleResponse({
    required this.loanId,
    required this.loanNumber,
    required this.totalInstallments,
    required this.paidInstallments,
    this.nextDue,
    required this.schedule,
  });

  factory ScheduleResponse.fromJson(Map<String, dynamic> json) {
    return ScheduleResponse(
      loanId: json['loan_id'] as String? ?? '',
      loanNumber: json['loan_number'] as String? ?? '',
      totalInstallments: (json['total_installments'] as num?)?.toInt() ?? 0,
      paidInstallments: (json['paid_installments'] as num?)?.toInt() ?? 0,
      nextDue: json['next_due'] != null ? ScheduleItem.fromJson(json['next_due']) : null,
      schedule: (json['schedule'] as List<dynamic>?)
              ?.map((e) => ScheduleItem.fromJson(e as Map<String, dynamic>))
              .toList() ??
          [],
    );
  }
}
