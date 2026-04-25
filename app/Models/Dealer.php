<?php

namespace App\Models;

use Database\Factories\DealerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'owner_user_id', 'name', 'code', 'phone', 'email',
    'address', 'tin_number', 'commission_rate', 'status',
])]
class Dealer extends Model
{
    /** @use HasFactory<DealerFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
        ];
    }

    /**
     * @param  Builder<Dealer>  $query
     */
    public function scopeWhereInsensitiveLike(Builder $query, string $column, string $pattern): Builder
    {
        return $this->applyInsensitiveLike($query, $column, $pattern);
    }

    /**
     * @param  Builder<Dealer>  $query
     */
    public function scopeOrWhereInsensitiveLike(Builder $query, string $column, string $pattern): Builder
    {
        return $this->applyInsensitiveLike($query, $column, $pattern, boolean: 'or');
    }

    /**
     * @param  Builder<Dealer>  $query
     */
    protected function applyInsensitiveLike(Builder $query, string $column, string $pattern, string $boolean = 'and'): Builder
    {
        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);

        return $query->whereRaw(
            "LOWER({$wrappedColumn}) LIKE ?",
            [Str::lower($pattern)],
            $boolean
        );
    }

    /**
     * Staff users (e.g. front-officer, back-officer) assigned to this dealer counter.
     *
     * @return HasMany<User, Dealer>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'dealer_id');
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(DealerWallet::class, 'dealer_id');
    }

    public function inventoryUnits(): HasMany
    {
        return $this->hasMany(InventoryUnit::class, 'dealer_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'dealer_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'dealer_id');
    }

    public function commissionLedgers(): HasMany
    {
        return $this->hasMany(CommissionLedger::class, 'dealer_id');
    }
}
