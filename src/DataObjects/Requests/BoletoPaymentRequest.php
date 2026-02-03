<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class BoletoPaymentRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
        public readonly ?string $dueDate = null,
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerDocument = null,
        public readonly ?string $customerEmail = null,
        public readonly ?array $customerAddress = null,
        public readonly ?float $fineAmount = null,
        public readonly ?float $finePercentage = null,
        public readonly ?float $interestAmount = null,
        public readonly ?float $interestPercentage = null,
        public readonly ?float $discountAmount = null,
        public readonly ?string $discountLimitDate = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'due_date' => $this->dueDate,
            'description' => $this->description,
            'customer_name' => $this->customerName,
            'customer_document' => $this->customerDocument,
            'customer_email' => $this->customerEmail,
            'customer_address' => $this->customerAddress,
            'fine_amount' => $this->fineAmount,
            'fine_percentage' => $this->finePercentage,
            'interest_amount' => $this->interestAmount,
            'interest_percentage' => $this->interestPercentage,
            'discount_amount' => $this->discountAmount,
            'discount_limit_date' => $this->discountLimitDate,
            'metadata' => $this->metadata,
        ];
    }

    public function hasFine(): bool
    {
        return $this->fineAmount !== null || $this->finePercentage !== null;
    }

    public function hasInterest(): bool
    {
        return $this->interestAmount !== null || $this->interestPercentage !== null;
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount !== null;
    }
}