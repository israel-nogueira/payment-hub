<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class WalletRequest
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $currency = 'BRL',
        public readonly ?string $description = null,
        public readonly ?float $initialBalance = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'currency' => $this->currency,
            'description' => $this->description,
            'initial_balance' => $this->initialBalance,
            'metadata' => $this->metadata,
        ];
    }

    public function hasInitialBalance(): bool
    {
        return $this->initialBalance !== null && $this->initialBalance > 0;
    }
}