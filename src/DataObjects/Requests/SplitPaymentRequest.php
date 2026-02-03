<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class SplitPaymentRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
        public readonly array $splits,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'splits' => $this->splits,
            'payment_method' => $this->paymentMethod,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }

    public function getTotalSplitAmount(): float
    {
        return array_reduce($this->splits, function ($carry, $split) {
            return $carry + ($split['amount'] ?? 0);
        }, 0.0);
    }

    public function isValid(): bool
    {
        return $this->getTotalSplitAmount() <= $this->amount;
    }

    public function getSplitCount(): int
    {
        return count($this->splits);
    }
}