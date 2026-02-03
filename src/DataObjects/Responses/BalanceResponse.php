<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class BalanceResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly float $balance,
        public readonly float $availableBalance,
        public readonly float $pendingBalance,
        public readonly string $currency,
        public readonly ?array $rawResponse = null
    ) {}

    public function hasAvailableBalance(): bool
    {
        return $this->availableBalance > 0;
    }

    public function hasPendingBalance(): bool
    {
        return $this->pendingBalance > 0;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'balance' => $this->balance,
            'available_balance' => $this->availableBalance,
            'pending_balance' => $this->pendingBalance,
            'currency' => $this->currency,
            'raw_response' => $this->rawResponse,
        ];
    }
}