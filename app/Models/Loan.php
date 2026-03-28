<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id', 'inventory_unit_id', 'vendor_id', 'branch_id', 'disbursed_by', 'approved_by',
    'loan_number', 'principal_amount', 'deposit_paid', 'interest_rate', 'interest_type',
    'total_debt', 'total_payable', 'amount_paid', 'remaining_balance', 'outstanding_balance',
    'penalty_amount', 'duration_weeks', 'grace_period_days', 'repayment_frequency',
    'status', 'disbursed_at', 'due_date', 'completed_at', 'notes',
])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'principal_amount'   => 'decimal:2',
            'deposit_paid'       => 'decimal:2',
            'total_debt'         => 'decimal:2',
            'total_payable'      => 'decimal:2',
            'amount_paid'        => 'decimal:2',
            'remaining_balance'  => 'decimal:2',
            'outstanding_balance'=> 'decimal:2',
            'penalty_amount'     => 'decimal:2',
            'interest_rate'      => 'decimal:2',
            'disbursed_at'       => 'date',
            'due_date'           => 'date',
            'completed_at'       => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function inventoryUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repaymentSchedules(): HasMany
    {
        return $this->hasMany(RepaymentSchedule::class)->orderBy('installment_number');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function commissionLedgers(): HasMany
    {
        return $this->hasMany(CommissionLedger::class);
    }

    public function recoveryTickets(): HasMany
    {
        return $this->hasMany(RecoveryTicket::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->due_date?->isPast();
    }
}
