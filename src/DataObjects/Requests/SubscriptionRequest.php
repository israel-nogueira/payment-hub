<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Enums\SubscriptionInterval;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

/**
 * VERSÃO MELHORADA com ValueObjects e Enums
 * 
 * Como usar:
 * 
 * $request = SubscriptionRequest::create(
 *     amount: 49.90,
 *     interval: 'monthly',
 *     customerId: 'cust_123',
 *     cardToken: 'tok_456',
 *     description: 'Assinatura Premium',
 *     trialDays: 7
 * );
 */
class SubscriptionRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly SubscriptionInterval $interval,
        public readonly ?string $planId = null,
        public readonly ?string $customerId = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $cardToken = null,
        public readonly ?string $description = null,
        public readonly ?int $trialDays = null,
        public readonly ?int $cycles = null,
        public readonly ?string $startDate = null,
        public readonly ?array $metadata = null
    ) {
        // Validações
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('Subscription amount must be greater than zero');
        }

        // Validar trial days
        if ($this->trialDays !== null && $this->trialDays < 0) {
            throw new \InvalidArgumentException('Trial days cannot be negative');
        }

        // Validar cycles
        if ($this->cycles !== null && $this->cycles < 1) {
            throw new \InvalidArgumentException('Cycles must be at least 1');
        }

        // Validar que tem customer ou payment method
        if (!$this->customerId && !$this->cardToken) {
            throw new \InvalidArgumentException('Must provide customerId or cardToken');
        }
    }

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        float $amount,
        Currency|string $currency = Currency::BRL,
        string|SubscriptionInterval $interval = 'monthly',
        ?string $planId = null,
        ?string $customerId = null,
        ?string $paymentMethod = null,
        ?string $cardToken = null,
        ?string $description = null,
        ?int $trialDays = null,
        ?int $cycles = null,
        ?string $startDate = null,
        ?array $metadata = null
    ): self {
        // Converte Currency
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        // Converte SubscriptionInterval
        if (is_string($interval)) {
            $interval = SubscriptionInterval::fromString($interval);
        }

        // Cria Money
        $money = Money::from($amount, $currency);

        return new self(
            money: $money,
            interval: $interval,
            planId: $planId,
            customerId: $customerId,
            paymentMethod: $paymentMethod,
            cardToken: $cardToken,
            description: $description,
            trialDays: $trialDays,
            cycles: $cycles,
            startDate: $startDate,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'interval' => $this->interval->value,
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

    // Getters para compatibilidade
    public function getAmount(): float
    {
        return $this->money->amount();
    }

    public function getCurrency(): string
    {
        return $this->money->currency()->value;
    }

    public function getFormattedAmount(): string
    {
        return $this->money->formatted();
    }

    public function getInterval(): string
    {
        return $this->interval->value;
    }

    public function getIntervalLabel(): string
    {
        return $this->interval->label();
    }

    public function hasTrial(): bool
    {
        return $this->trialDays !== null && $this->trialDays > 0;
    }

    public function isUnlimited(): bool
    {
        return $this->cycles === null;
    }

    public function getTotalValue(): ?Money
    {
        if ($this->isUnlimited()) {
            return null;
        }

        return $this->money->multiply($this->cycles);
    }

    public function getFirstChargeDate(): ?\DateTime
    {
        $startDate = $this->startDate 
            ? \DateTime::createFromFormat('Y-m-d', $this->startDate)
            : new \DateTime();

        if ($this->hasTrial()) {
            $startDate->modify("+{$this->trialDays} days");
        }

        return $startDate;
    }

    public function getFormattedDescription(): string
    {
        $desc = $this->description ?? 'Subscription';
        $intervalLabel = $this->interval->label();
        
        return "{$desc} - {$this->money->formatted()}/{$intervalLabel}";
    }
}
