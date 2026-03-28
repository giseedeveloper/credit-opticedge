<?php

namespace App\Models;

use Database\Factories\RepaymentScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'loan_id', 'installment_number', 'amount_due', 'principal_component',
    'interest_component', 'penalty_component', 'amount_paid', 'balance_remaining',
    'due_date', 'paid_at', 'status', 'days_overdue',
])]
class RepaymentSchedule extends Model
{
    /** @use HasFactory<RepaymentScheduleFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'principal_component' => 'decimal:2',
            'interest_component' => 'decimal:2',
            'penalty_component' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_remaining' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }
}
