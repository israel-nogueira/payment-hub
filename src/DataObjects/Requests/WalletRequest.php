<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;

class WalletRequest
{
    public function __construct(
        public readonly string $customerId,
        public readonly ?string $name = null,
        public readonly Currency $currency = Currency::BRL,
        public readonly ?string $description = null,
        public readonly ?float $initialBalance = null,
        public readonly ?array $metadata = null
    ) {}

    /**
     * Factory method — aceita string ou enum
     */
    public static function create(
        string $customerId,
        ?string $name = null,
        Currency|string $currency = Currency::BRL,
        ?string $description = null,
        ?float $initialBalance = null,
        ?array $metadata = null
    ): self {
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        return new self(
            customerId:     $customerId,
            name:           $name,
            currency:       $currency,
            description:    $description,
            initialBalance: $initialBalance,
            metadata:       $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id'     => $this->customerId,
            'name'            => $this->name,
            'currency'        => $this->currency->value,
            'description'     => $this->description,
            'initial_balance' => $this->initialBalance,
            'metadata'        => $this->metadata,
        ];
    }

    public function hasInitialBalance(): bool
    {
        return $this->initialBalance !== null && $this->initialBalance > 0;
    }
}