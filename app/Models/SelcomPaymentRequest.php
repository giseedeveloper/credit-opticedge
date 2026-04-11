<?php

namespace App\Models;

use Database\Factories\SelcomPaymentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'draft_reference',
    'customer_id',
    'initiated_by',
    'order_id',
    'transid',
    'phone',
    'amount',
    'currency',
    'provider',
    'channel',
    'status',
    'payment_status',
    'result',
    'resultcode',
    'selcom_reference',
    'gateway_buyer_uuid',
    'payment_token',
    'payment_gateway_url',
    'request_payload',
    'response_payload',
    'status_payload',
    'webhook_payload',
    'paid_at',
    'webhook_received_at',
])]
class SelcomPaymentRequest extends Model
{
    /** @use HasFactory<SelcomPaymentRequestFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'status_payload' => 'array',
            'webhook_payload' => 'array',
            'paid_at' => 'datetime',
            'webhook_received_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isCompleted(): bool
    {
        return $this->payment_status === 'COMPLETED' || $this->status === 'completed';
    }
}
