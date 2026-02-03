<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

class SubscriptionRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency = 'BRL',
        public readonly string $interval = 'monthly',
        public readonly ?string $planId = null,
        public readonly ?string $customerId = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $cardToken = null,
        public readonly ?string $description = null,
        public readonly ?int $trialDays = null,
        public readonly ?int $cycles = null,
        public readonly ?string $startDate = null,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'interval' => $this->interval,
            'plan_id' => $this->planId,
            'customer_id' => $this->customerId,
            'payment_method' => $this->paymentMethod,
            'card_token' => $this->cardToken,
            'description' => $this->description,
            'trial_days' => $this->trialDays,
            'cycles' => $this->cycles,
            'start_date' => $this->startDate,
            'metadata' => $this->metadata,
        ];
    }

    public function hasTrial(): bool
    {
        return $this->trialDays !== null && $this->trialDays > 0;
    }

    public function isUnlimited(): bool
    {
        return $this->cycles === null;
    }
}