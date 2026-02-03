<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class EscrowRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
        public readonly ?string $transactionId = null,
        public readonly ?string $recipientId = null,
        public readonly ?int $holdDays = null,
        public readonly ?string $releaseDate = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_id' => $this->transactionId,
            'recipient_id' => $this->recipientId,
            'hold_days' => $this->holdDays,
            'release_date' => $this->releaseDate,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }

    public function hasAutoRelease(): bool
    {
        return $this->holdDays !== null || $this->releaseDate !== null;
    }

    public function isManualRelease(): bool
    {
        return !$this->hasAutoRelease();
    }
}