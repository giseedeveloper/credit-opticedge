<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoveryTicket extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'loan_id',
        'assigned_agent_id',
        'status',
        'reward_amount',
        'notes',
        'assigned_at',
        'completed_at',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The associated defaulted loan.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * The recovery officer assigned.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }
}
