<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class WalletResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $walletId,
        public readonly float $balance,
        public readonly string $currency,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function hasBalance(): bool
    {
        return $this->balance > 0;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'wallet_id' => $this->walletId,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}