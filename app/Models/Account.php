<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'type', 'balance', 'description', 'is_active'])]
class Account extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'balance'   => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function computedBalance(): string
    {
        $debits  = (string) $this->journalEntryLines()->sum('debit');
        $credits = (string) $this->journalEntryLines()->sum('credit');

        return match ($this->type) {
            'Asset', 'Expense' => bcsub($debits, $credits, 4),
            default            => bcsub($credits, $debits, 4),
        };
    }

    public function scopeOfType($query, string $type): mixed
    {
        return $query->where('type', $type);
    }
}
