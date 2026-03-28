<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['reference', 'date', 'description', 'source', 'created_by'])]
class JournalEntry extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalDebits(): string
    {
        return (string) $this->lines()->sum('debit');
    }

    public function isBalanced(): bool
    {
        $d = (string) $this->lines()->sum('debit');
        $c = (string) $this->lines()->sum('credit');

        return bccomp($d, $c, 4) === 0;
    }
}
