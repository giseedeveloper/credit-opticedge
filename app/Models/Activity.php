<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    use HasUuids;

    /**
     * Apply a portable case-insensitive LIKE filter for SQL drivers that do not support ILIKE.
     *
     * @param  Builder<Activity>  $query
     */
    public function scopeWhereInsensitiveLike(Builder $query, string $column, string $pattern): Builder
    {
        return $this->applyInsensitiveLike($query, $column, $pattern);
    }

    /**
     * Apply a portable case-insensitive NOT LIKE filter for SQL drivers that do not support ILIKE.
     *
     * @param  Builder<Activity>  $query
     */
    public function scopeWhereInsensitiveNotLike(Builder $query, string $column, string $pattern): Builder
    {
        return $this->applyInsensitiveLike($query, $column, $pattern, negate: true);
    }

    /**
     * @param  Builder<Activity>  $query
     */
    protected function applyInsensitiveLike(Builder $query, string $column, string $pattern, bool $negate = false): Builder
    {
        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);
        $operator = $negate ? 'NOT LIKE' : 'LIKE';

        return $query->whereRaw(
            "LOWER({$wrappedColumn}) {$operator} ?",
            [Str::lower($pattern)]
        );
    }
}
