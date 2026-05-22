<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = [
        'crypto_gateway_id',
        'event_type',
        'gateway_webhook_id',
        'payload',
        'signature',
        'signature_verified',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_verified' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(CryptoGateway::class, 'crypto_gateway_id');
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
