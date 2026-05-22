<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    protected $fillable = [
        'crypto_transaction_id',
        'action',
        'status',
        'message',
        'data',
        'ip_address',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public $timestamps = true;

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CryptoTransaction::class, 'crypto_transaction_id');
    }

    public static function log(CryptoTransaction $transaction, string $action, string $status, ?string $message = null, ?array $data = null): self
    {
        return self::create([
            'crypto_transaction_id' => $transaction->id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'ip_address' => request()->ip(),
        ]);
    }
}
