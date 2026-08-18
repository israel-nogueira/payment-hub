<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class PaymentLinkRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
        public readonly ?string $description = null,
        public readonly ?array $acceptedPaymentMethods = null,
        public readonly ?int $maxUses = null,
        public readonly ?\DateTimeInterface $expiresAt = null,
        public readonly ?bool $reusable = false,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $externalReferenceId = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerDocument = null,
        public readonly ?string $customerEmail = null,
        public readonly ?int $maxInstallments = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'accepted_payment_methods' => $this->acceptedPaymentMethods,
            'max_uses' => $this->maxUses,
            'expires_at' => $this->expiresAt?->format('Y-m-d\TH:i:s'),
            'reusable' => $this->reusable,
            'redirect_url' => $this->redirectUrl,
            'external_reference_id' => $this->externalReferenceId,
            'customer_name' => $this->customerName,
            'customer_document' => $this->customerDocument,
            'customer_email' => $this->customerEmail,
            'max_installments' => $this->maxInstallments,
            'metadata' => $this->metadata,
        ];
    }

    public function isReusable(): bool
    {
        return $this->reusable === true;
    }

    public function isSingleUse(): bool
    {
        return $this->maxUses === 1 || (!$this->reusable && $this->maxUses === null);
    }

    public function hasExpiration(): bool
    {
        return $this->expiresAt !== null;
    }
}