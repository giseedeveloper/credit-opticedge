<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'branch_id', 'vendor_id', 'registered_by', 'phone_model_id', 'inventory_unit_id',
    'application_draft_reference',
    // Identity
    'first_name', 'last_name', 'middle_name', 'gender', 'date_of_birth', 'email',
    'nida_number', 'id_type',
    // Contact & Location
    'phone', 'alt_phone', 'phone_metadata', 'address', 'latitude', 'longitude', 'region', 'district', 'landmark',
    // KYC state
    'kyc_status', 'kyc_stage', 'credit_status', 'status', 'location_metadata', 'metadata',
    // Step 1 – Device
    'imei_number', 'imei_2', 'serial_number', 'device_specs',
    'cash_price', 'deposit_amount', 'preferred_repayment',
    'loan_interest_rate', 'loan_interest_type', 'loan_duration_weeks', 'loan_grace_period_days',
    'imei_photo_path', 'device_box_photo_path', 'device_photo_path', 'device_scan_metadata', 'device_accessories', 'store_offer_notes',
    // Step 2 – Identity docs
    'id_front_photo_path', 'id_back_photo_path', 'headshot_photo_path', 'client_fo_photo_path',
    // Step 4 – Work/Income
    'occupation', 'employer', 'work_location', 'monthly_income', 'monthly_expenses',
    'income_payment_cycle', 'duration_at_work', 'business_photo_path',
    // Step 5 – NOK
    'nok_name', 'nok_phone', 'nok_relationship',
    'nok2_name', 'nok2_phone', 'nok2_relationship',
    // Step 6 – Consent
    'terms_accepted', 'data_consent_accepted', 'call_consent_accepted', 'consent_timestamp',
    // Payment, agreement & release
    'agreement_document_id', 'agreement_accepted', 'agreement_presented_at', 'agreement_decision_at',
    'customer_signature_path', 'fo_signature_path', 'asset_handover_list_path', 'asset_handover_notes',
    'deposit_payment_status', 'deposit_payment_amount', 'deposit_payment_reference', 'deposit_paid_at',
    'asset_release_status', 'asset_released_at', 'asset_released_by',
    // Step 7 – Submit metadata
    'fo_notes', 'application_source',
    // Customer portal auth
    'pin',
])]
class Customer extends Model implements Authenticatable, HasMedia
{
    /** @use HasFactory<CustomerFactory> */
    use AuthenticatableTrait, HasApiTokens, HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['pin', 'remember_token'];

    /**
     * @return array<int, string>
     */
    public static function approvedKycStatuses(): array
    {
        return ['approved', 'verified'];
    }

    /**
     * The customer portal uses `pin` as the password column.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->pin;
    }

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
            'date_of_birth' => 'date',
            'monthly_income' => 'decimal:2',
            'monthly_expenses' => 'decimal:2',
            'cash_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'loan_interest_rate' => 'decimal:2',
            'loan_duration_weeks' => 'integer',
            'loan_grace_period_days' => 'integer',
            'kyc_stage' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
            'terms_accepted' => 'boolean',
            'data_consent_accepted' => 'boolean',
            'call_consent_accepted' => 'boolean',
            'consent_timestamp' => 'datetime',
            'agreement_accepted' => 'boolean',
            'agreement_presented_at' => 'datetime',
            'agreement_decision_at' => 'datetime',
            'deposit_payment_amount' => 'decimal:2',
            'deposit_paid_at' => 'datetime',
            'asset_released_at' => 'datetime',
            'location_metadata' => 'array',
            'metadata' => 'array',
            'phone_metadata' => 'array',
            'device_scan_metadata' => 'array',
            'device_accessories' => 'array',
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
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter(fn (?string $part) => filled($part))
            ->implode(' ');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function phoneMeta(string $field): ?array
    {
        $metadata = $this->phone_metadata ?? [];

        if (! is_array($metadata)) {
            return null;
        }

        $fieldMetadata = $metadata[$field] ?? null;

        return is_array($fieldMetadata) ? $fieldMetadata : null;
    }

    public function formattedPhone(string $field): ?string
    {
        $value = $this->{$field};

        if (! is_string($value) || $value === '') {
            return null;
        }

        $metadata = $this->phoneMeta($field);

        if ($metadata && ($metadata['display'] ?? null)) {
            return (string) $metadata['display'];
        }

        return $value;
    }

    public function hasApprovedKyc(): bool
    {
        return in_array($this->kyc_status, self::approvedKycStatuses(), true);
    }

    public function hasSuccessfulDepositPayment(): bool
    {
        if ($this->deposit_payment_status === 'completed') {
            return true;
        }

        // Legacy rows: deposit was completed in Selcom while snapshot stored a non-completed `status` string
        return $this->deposit_paid_at !== null
            && filled($this->deposit_payment_reference);
    }

    public function hasAcceptedAgreement(): bool
    {
        return $this->agreement_accepted === true;
    }

    public function hasCapturedSignatures(): bool
    {
        return filled($this->customer_signature_path) && filled($this->fo_signature_path);
    }

    public function hasAssetHandoverRecord(): bool
    {
        return filled($this->asset_handover_list_path);
    }

    public function isAssetReleased(): bool
    {
        return $this->asset_release_status === 'released';
    }

    public function isReadyForAssetRelease(): bool
    {
        return $this->hasApprovedKyc()
            && $this->hasSuccessfulDepositPayment()
            && $this->hasAcceptedAgreement()
            && $this->hasCapturedSignatures()
            && $this->hasAssetHandoverRecord()
            && filled($this->agreement_document_id)
            && filled($this->inventory_unit_id)
            && ! $this->isAssetReleased();
    }

    public function scopeKycApproved(Builder $query): Builder
    {
        return $query->whereIn('kyc_status', self::approvedKycStatuses());
    }

    public function scopeKycNotApproved(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->whereNull('kyc_status')
                ->orWhereNotIn('kyc_status', self::approvedKycStatuses());
        });
    }

    public function scopeSearchDirectory(Builder $query, ?string $term): Builder
    {
        $searchTerm = strtolower(trim((string) $term));

        if ($searchTerm === '') {
            return $query;
        }

        $like = '%'.$searchTerm.'%';

        return $query->where(function (Builder $builder) use ($like): void {
            $builder->whereRaw('LOWER(first_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(middle_name, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(phone) LIKE ?', [$like])
                ->orWhereRaw('LOWER(nida_number) LIKE ?', [$like]);
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function phoneModel(): BelongsTo
    {
        return $this->belongsTo(PhoneModel::class);
    }

    public function agreementDocument(): BelongsTo
    {
        return $this->belongsTo(SystemDocument::class, 'agreement_document_id');
    }

    public function inventoryUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function assetReleasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asset_released_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(Verification::class)->latest('created_at');
    }

    public function latestKycVerification(): HasOne
    {
        return $this->hasOne(Verification::class)
            ->where('type', 'kyc')
            ->latest('created_at');
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

    public function selcomPaymentRequests(): HasMany
    {
        return $this->hasMany(SelcomPaymentRequest::class);
    }
}
