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
        public readonly ?string $expiresAt = null,
        public readonly ?bool $reusable = false,
        public readonly ?string $redirectUrl = null,
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
            'expires_at' => $this->expiresAt,
            'reusable' => $this->reusable,
            'redirect_url' => $this->redirectUrl,
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