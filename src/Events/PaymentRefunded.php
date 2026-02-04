<?php

namespace IsraelNogueira\PaymentHub\Events;

final class PaymentRefunded implements PaymentEventInterface
{
    private \DateTimeImmutable $timestamp;

    public function __construct(
        private string $transactionId,
        private string $refundId,
        private float $amount,
        private string $currency,
        private string $reason,
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
        return 'payment.refunded';
    }

    public function getRefundId(): string
    {
        return $this->refundId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getReason(): string
    {
        return $this->reason;
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
            'refund_id' => $this->refundId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ISO8601),
        ];
    }
}