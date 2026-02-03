<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class RefundRequest
{
    public function __construct(
        public readonly string $transactionId,
        public readonly ?float $amount = null,
        public readonly ?string $reason = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ];
    }

    public function isPartialRefund(): bool
    {
        return $this->amount !== null;
    }

    public function isFullRefund(): bool
    {
        return $this->amount === null;
    }
}