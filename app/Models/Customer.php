<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'branch_id', 'vendor_id', 'registered_by', 'first_name', 'last_name', 'middle_name',
    'phone', 'alt_phone', 'email', 'nida_number', 'date_of_birth', 'gender',
    'occupation', 'employer', 'monthly_income', 'monthly_expenses',
    'address', 'latitude', 'longitude', 'region', 'district',
    'kyc_status', 'kyc_stage', 'credit_status', 'status', 'location_metadata', 'metadata',
    'imei_number', 'device_specs', 'imei_photo_path',
    'id_front_photo_path', 'id_back_photo_path', 'headshot_photo_path', 'client_fo_photo_path',
    'nok_name', 'nok_phone', 'nok_relationship',
])]
class Customer extends Model implements HasMedia
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'monthly_income' => 'decimal:2',
            'monthly_expenses' => 'decimal:2',
            'kyc_stage' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
            'location_metadata' => 'array',
            'metadata' => 'array',
        ];
    }

    public function kycStageLabel(): string
    {
        return match ($this->kyc_stage ?? 1) {
            1 => 'Device Verification',
            2 => 'KYC & Financial Data',
            3 => 'Confirmation Call',
            4 => 'Next of Kin + Final',
            default => 'Unknown',
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('nida_card')->singleFile();
        $this->addMediaCollection('selfie')->singleFile();
        $this->addMediaCollection('handover_photo');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(Verification::class)->latest('created_at');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->where('status', 'active');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
