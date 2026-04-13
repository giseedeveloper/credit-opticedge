class LoanReleaseContext {
  final String? assetReleaseStatus;
  final String? assetReleasedAt;
  final double cashPrice;
  final double depositAmount;
  final String? preferredRepayment;
  final String? inventoryUnitId;

  const LoanReleaseContext({
    this.assetReleaseStatus,
    this.assetReleasedAt,
    this.cashPrice = 0,
    this.depositAmount = 0,
    this.preferredRepayment,
    this.inventoryUnitId,
  });

  factory LoanReleaseContext.fromJson(Map<String, dynamic> json) {
    return LoanReleaseContext(
      assetReleaseStatus: json['asset_release_status'] as String?,
      assetReleasedAt: json['asset_released_at'] as String?,
      cashPrice: LoanModel._toDouble(json['cash_price']),
      depositAmount: LoanModel._toDouble(json['deposit_amount']),
      preferredRepayment: json['preferred_repayment'] as String?,
      inventoryUnitId: json['inventory_unit_id'] as String?,
    );
  }
}

class LoanPortalPayload {
  final String portalState;
  final String? portalMessage;
  final LoanModel? loan;
  final LoanReleaseContext? release;

  const LoanPortalPayload({
    this.portalState = 'no_loan',
    this.portalMessage,
    this.loan,
    this.release,
  });

  bool get isLoanActive => portalState == 'loan_active' && loan != null;
  bool get isReleasedPendingDisbursement =>
      portalState == 'released_pending_disbursement';
  bool get isNoLoan => portalState == 'no_loan' && loan == null;

  factory LoanPortalPayload.fromJson(Map<String, dynamic> json) {
    if (json.containsKey('loan') ||
        json.containsKey('portal_state') ||
        json.containsKey('release')) {
      return LoanPortalPayload(
        portalState: json['portal_state'] as String? ?? 'no_loan',
        portalMessage: json['portal_message'] as String?,
        loan: json['loan'] is Map<String, dynamic>
            ? LoanModel.fromJson(json['loan'] as Map<String, dynamic>)
            : null,
        release: json['release'] is Map<String, dynamic>
            ? LoanReleaseContext.fromJson(json['release'] as Map<String, dynamic>)
            : null,
      );
    }

    return LoanPortalPayload(
      portalState: json.isEmpty ? 'no_loan' : 'loan_active',
      loan: json.isEmpty ? null : LoanModel.fromJson(json),
    );
  }
}

class LoanModel {
  final String id;
  final String loanNumber;
  final String status;
  final double principalAmount;
  final double depositPaid;
  final double interestRate;
  final String interestType;
  final double totalPayable;
  final double amountPaid;
  final double remainingBalance;
  final double outstandingBalance;
  final double penaltyAmount;
  final int durationWeeks;
  final String repaymentFrequency;
  final String? disbursedAt;
  final String? dueDate;
  final int paidInstallments;
  final int totalInstallments;
  final double progressPercent;
  final NextInstallment? nextInstallment;
  final bool isOverdue;

  LoanModel({
    required this.id,
    required this.loanNumber,
    required this.status,
    required this.principalAmount,
    required this.depositPaid,
    required this.interestRate,
    required this.interestType,
    required this.totalPayable,
    required this.amountPaid,
    required this.remainingBalance,
    required this.outstandingBalance,
    required this.penaltyAmount,
    required this.durationWeeks,
    required this.repaymentFrequency,
    this.disbursedAt,
    this.dueDate,
    required this.paidInstallments,
    required this.totalInstallments,
    required this.progressPercent,
    this.nextInstallment,
    required this.isOverdue,
  });

  factory LoanModel.fromJson(Map<String, dynamic> json) {
    return LoanModel(
      id: json['id'] as String,
      loanNumber: json['loan_number'] as String? ?? '',
      status: json['status'] as String? ?? 'active',
      principalAmount: _toDouble(json['principal_amount']),
      depositPaid: _toDouble(json['deposit_paid']),
      interestRate: _toDouble(json['interest_rate']),
      interestType: json['interest_type'] as String? ?? 'flat',
      totalPayable: _toDouble(json['total_payable']),
      amountPaid: _toDouble(json['amount_paid']),
      remainingBalance: _toDouble(json['remaining_balance']),
      outstandingBalance: _toDouble(json['outstanding_balance']),
      penaltyAmount: _toDouble(json['penalty_amount']),
      durationWeeks: (json['duration_weeks'] as num?)?.toInt() ?? 0,
      repaymentFrequency: json['repayment_frequency'] as String? ?? 'weekly',
      disbursedAt: json['disbursed_at'] as String?,
      dueDate: json['due_date'] as String?,
      paidInstallments: (json['paid_installments'] as num?)?.toInt() ?? 0,
      totalInstallments: (json['total_installments'] as num?)?.toInt() ?? 0,
      progressPercent: _toDouble(json['progress_percent']),
      nextInstallment: json['next_installment'] != null
          ? NextInstallment.fromJson(json['next_installment'])
          : null,
      isOverdue: json['is_overdue'] as bool? ?? false,
    );
  }

  static double _toDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    return double.tryParse(value.toString()) ?? 0.0;
  }
}

class NextInstallment {
  final int installmentNumber;
  final double amountDue;
  final String? dueDate;
  final String status;
  final int daysOverdue;

  NextInstallment({
    required this.installmentNumber,
    required this.amountDue,
    this.dueDate,
    required this.status,
    required this.daysOverdue,
  });

  factory NextInstallment.fromJson(Map<String, dynamic> json) {
    return NextInstallment(
      installmentNumber: (json['installment_number'] as num?)?.toInt() ?? 0,
      amountDue: LoanModel._toDouble(json['amount_due']),
      dueDate: json['due_date'] as String?,
      status: json['status'] as String? ?? 'pending',
      daysOverdue: (json['days_overdue'] as num?)?.toInt() ?? 0,
    );
  }
}
