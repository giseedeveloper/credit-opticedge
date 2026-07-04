<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'role', 'dealer_id', 'joined_at', 'employee_code', 'is_active', 'avatar_url', 'email_otp_enabled'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPermissions, HasRoles, HasUuids, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    private const DEFAULT_ROUTE_BY_PERMISSION = [
        'dashboard.view' => 'dashboard',
        'loans.view' => 'credit.panel',
        'loans.create' => 'kyc.wizard',
        'devices.view' => 'stock.imei',
        'products.view' => 'stock.brands',
        'accounting.view' => 'financials.collections',
        'staff.view' => 'staff.index',
        'reports.view' => 'audits.logs',
        'sms_campaign.view' => 'comms.sms',
        'access.view' => 'access',
        'settings.view' => 'settings.health',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joined_at' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'email_otp_enabled' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * Dealer counter / shop this user works under (front-officer, back-officer, etc.).
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * Dealers where this user is the designated owner account.
     */
    public function managedDealers(): HasMany
    {
        return $this->hasMany(Dealer::class, 'owner_user_id');
    }

    public function registeredCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'registered_by');
    }

    public function disbursedLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'disbursed_by');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function requiresMandatoryTwoFactorAuthentication(): bool
    {
        return $this->isAdmin();
    }

    public function mustConfigureTwoFactorAuthentication(): bool
    {
        return $this->requiresMandatoryTwoFactorAuthentication()
            && ! $this->hasEnabledTwoFactorAuthentication();
    }

    public function hasEnabledEmailOtpAuthentication(): bool
    {
        return (bool) $this->email_otp_enabled && filled($this->email);
    }

    public function canUseEmailOtpForTwoFactorChallenge(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication()
            && $this->hasEnabledEmailOtpAuthentication();
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isDealer(): bool
    {
        return $this->hasRole('dealer');
    }

    /** @deprecated Use {@see isDealer()}. */
    public function isVendor(): bool
    {
        return $this->isDealer();
    }

    public function isAccountant(): bool
    {
        return $this->hasRole('accountant');
    }

    public function isFrontOfficer(): bool
    {
        return $this->hasRole('front-officer');
    }

    public function isBackOfficer(): bool
    {
        return $this->hasRole('back-officer');
    }

    public function canAccess(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        try {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        } catch (PermissionDoesNotExist) {
            //
        }

        $module = Str::before($permission, '.');

        if ($module === $permission) {
            return false;
        }

        try {
            return $this->hasPermissionTo("{$module}.all");
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function firstAccessibleRouteName(): string
    {
        foreach (self::DEFAULT_ROUTE_BY_PERMISSION as $permission => $routeName) {
            if ($this->canAccess($permission) && Route::has($routeName)) {
                return $routeName;
            }
        }

        return Route::has('profile.edit') ? 'profile.edit' : 'home';
    }

    public function primaryRoleName(): ?string
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->pluck('name')
                ->sort()
                ->values()
                ->first();
        }

        return $this->getRoleNames()
            ->sort()
            ->values()
            ->first();
    }

    public function syncRoleColumn(?string $roleName = null): void
    {
        $this->forceFill([
            'role' => $roleName ?? $this->primaryRoleName() ?? 'staff',
        ])->saveQuietly();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
