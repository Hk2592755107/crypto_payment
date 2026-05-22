<?php

namespace App\Services\Crypto\Contracts;

interface CryptoGatewayInterface
{
    public function createPaymentRequest(array $data): array;

    public function getPaymentStatus(string $transactionId): array;

    public function verifyWebhook(array $payload, string $signature): bool;

    public function handleWebhookPayload(array $payload): void;

    public function getSupportedCurrencies(): array;

    public function validateConfiguration(): bool;

    public function getExchangeRate(string $cryptocurrency, string $fiatCurrency): ?float;
}
