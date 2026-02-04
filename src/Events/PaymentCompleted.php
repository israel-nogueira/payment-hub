<?php

namespace IsraelNogueira\PaymentHub\Events;

use IsraelNogueira\PaymentHub\Enums\PaymentStatus;

final class PaymentCompleted implements PaymentEventInterface
{
    private \DateTimeImmutable $timestamp;

    public function __construct(
        private string $transactionId,
        private float $amount,
        private string $currency,
        private PaymentStatus $status,
        private array $metadata = []
    ) {
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getEventName(): string
    {
        return 'payment.completed';
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ISO8601),
        ];
    }
}