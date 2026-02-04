<?php

namespace IsraelNogueira\PaymentHub\Events;

use IsraelNogueira\PaymentHub\Enums\PaymentMethod;

final class PaymentCreated implements PaymentEventInterface
{
    private \DateTimeImmutable $timestamp;

    public function __construct(
        private string $transactionId,
        private float $amount,
        private string $currency,
        private PaymentMethod $method,
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
        return 'payment.created';
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
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
            'method' => $this->method->value,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp->format(\DateTimeInterface::ISO8601),
        ];
    }
}