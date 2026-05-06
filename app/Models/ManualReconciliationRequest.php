<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'loan_id',
    'requested_by',
    'reviewed_by',
    'processed_transaction_id',
    'amount',
    'reference',
    'method',
    'override_reason',
    'status',
    'review_note',
    'approved_at',
    'rejected_at',
    'request_snapshot',
])]
class ManualReconciliationRequest extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'request_snapshot' => 'array',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function processedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'processed_transaction_id');
    }
}
