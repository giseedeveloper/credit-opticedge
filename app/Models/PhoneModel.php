<?php

namespace App\Models;

use Database\Factories\PhoneModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_id',
    'name',
    'slug',
    'retail_price',
    'cost_price',
    'specifications',
    'external_source',
    'external_id',
    'last_synced_at',
    'is_active',
])]
class PhoneModel extends Model
{
    /** @use HasFactory<PhoneModelFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'retail_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
