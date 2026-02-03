<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class PixPaymentRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerDocument = null,
        public readonly ?string $customerEmail = null,
        public readonly ?int $expiresInMinutes = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'customer_name' => $this->customerName,
            'customer_document' => $this->customerDocument,
            'customer_email' => $this->customerEmail,
            'expires_in_minutes' => $this->expiresInMinutes,
            'metadata' => $this->metadata,
        ];
    }
}