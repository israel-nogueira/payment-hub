<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class DebitCardPaymentRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
        public readonly ?string $cardToken = null,
        public readonly ?string $cardNumber = null,
        public readonly ?string $cardHolderName = null,
        public readonly ?string $cardExpiryMonth = null,
        public readonly ?string $cardExpiryYear = null,
        public readonly ?string $cardCvv = null,
        public readonly ?string $description = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerDocument = null,
        public readonly ?string $customerEmail = null,
        public readonly ?array $billingAddress = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'card_token' => $this->cardToken,
            'card_number' => $this->cardNumber,
            'card_holder_name' => $this->cardHolderName,
            'card_expiry_month' => $this->cardExpiryMonth,
            'card_expiry_year' => $this->cardExpiryYear,
            'card_cvv' => $this->cardCvv,
            'description' => $this->description,
            'customer_name' => $this->customerName,
            'customer_document' => $this->customerDocument,
            'customer_email' => $this->customerEmail,
            'billing_address' => $this->billingAddress,
            'metadata' => $this->metadata,
        ];
    }

    public function hasCardToken(): bool
    {
        return $this->cardToken !== null;
    }

    public function hasRawCardData(): bool
    {
        return $this->cardNumber !== null;
    }
}