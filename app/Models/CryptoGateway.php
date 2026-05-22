<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoGateway extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'api_endpoint',
        'webhook_endpoint',
        'supported_currencies',
        'config',
        'is_active',
        'priority',
        'transaction_fee_percentage',
        'min_transaction_amount',
        'max_transaction_amount',
        'confirmation_required',
    ];

    protected $casts = [
        'supported_currencies' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'transaction_fee_percentage' => 'decimal:4',
        'min_transaction_amount' => 'decimal:8',
        'max_transaction_amount' => 'decimal:8',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CryptoTransaction::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function getApiKey(): ?string
    {
        // First check config in database
        if (!empty($this->config['api_key'])) {
            return $this->config['api_key'];
        }
        
        // Then check config file (which reads from .env)
        return config("crypto.gateways.{$this->slug}.api_key");
    }

    public function getWebhookSecret(): ?string
    {
        // First check config in database
        if (!empty($this->config['webhook_secret'])) {
            return $this->config['webhook_secret'];
        }
        
        // Then check config file (which reads from .env)
        return config("crypto.gateways.{$this->slug}.webhook_secret");
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey()) && $this->is_active;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }
}
