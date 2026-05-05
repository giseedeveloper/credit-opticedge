<?php

namespace App\Models;

use Database\Factories\VerificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id', 'reviewed_by', 'fo_id', 'type', 'status', 'stage',
    'notes', 'rejection_reason', 'reviewed_at',
    'auto_check_status', 'auto_check_results', 'auto_check_ran_at',
    'face_match_status', 'face_match_score', 'face_match_reason', 'face_match_ran_at',
    'face_match_manual_verified_by', 'face_match_manual_verified_at',
    'stage1_status', 'stage1_reviewed_by', 'stage1_reviewed_at', 'stage1_notes', 'stage1_rejection_reason',
    'stage2_status', 'stage2_reviewed_by', 'stage2_reviewed_at', 'stage2_notes', 'stage2_rejection_reason',
    'stage3_status', 'stage4_status',
    'confirmation_call_outcome', 'confirmation_call_notes', 'confirmation_called_at', 'confirmation_called_by',
    'nok_call_outcome', 'nok_call_notes', 'nok_called_at', 'nok_called_by',
])]
class Verification extends Model
{
    /** @use HasFactory<VerificationFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'stage1_reviewed_at' => 'datetime',
            'stage2_reviewed_at' => 'datetime',
            'confirmation_called_at' => 'datetime',
            'nok_called_at' => 'datetime',
            'auto_check_ran_at' => 'datetime',
            'auto_check_results' => 'array',
            'face_match_score' => 'float',
            'face_match_ran_at' => 'datetime',
            'face_match_manual_verified_at' => 'datetime',
            'stage' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fo_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function stage1ReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stage1_reviewed_by');
    }

    public function stage2ReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stage2_reviewed_by');
    }

    public function faceMatchManualVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'face_match_manual_verified_by');
    }

    public function confirmationCalledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmation_called_by');
    }

    public function nokCalledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nok_called_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function currentStageLabel(): string
    {
        return match ($this->stage ?? 1) {
            1 => 'Device Verification',
            2 => 'KYC & Financial Data',
            3 => 'Confirmation Call',
            4 => 'Next of Kin + Final',
            default => 'Completed',
        };
    }
}
