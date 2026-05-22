<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'crypto_gateway_id',
        'order_id',
        'transaction_reference',
        'gateway_transaction_id',
        'payment_address',
        'cryptocurrency',
        'crypto_amount',
        'fiat_amount',
        'fiat_currency',
        'exchange_rate',
        'status',
        'confirmations',
        'required_confirmations',
        'transaction_hash',
        'amount_received',
        'expires_at',
        'confirmed_at',
        'gateway_response',
        'metadata',
        'webhook_status',
        'webhook_verified_at',
    ];

    protected $casts = [
        'crypto_amount' => 'decimal:8',
        'fiat_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'amount_received' => 'decimal:8',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'webhook_verified_at' => 'datetime',
    ];

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(CryptoGateway::class, 'crypto_gateway_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isWaitingConfirmation(): bool
    {
        return $this->status === 'waiting_confirmation';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function hasConfirmed(): bool
    {
        return $this->confirmations >= $this->required_confirmations;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeUnverified($query)
    {
        return $query->where('webhook_status', 'pending');
    }
}
