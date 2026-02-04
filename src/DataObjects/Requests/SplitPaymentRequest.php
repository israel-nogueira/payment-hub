<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

/**
 * VERSÃO MELHORADA com ValueObjects
 * 
 * Como usar:
 * 
 * $request = SplitPaymentRequest::create(
 *     amount: 1000.00,
 *     splits: [
 *         ['recipient_id' => 'seller_1', 'amount' => 700.00],
 *         ['recipient_id' => 'marketplace', 'amount' => 300.00]
 *     ],
 *     paymentMethod: 'credit_card'
 * );
 */
class SplitPaymentRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly array $splits,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null
    ) {
        // Validações
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('Split payment amount must be greater than zero');
        }

        if (empty($this->splits)) {
            throw new \InvalidArgumentException('At least one split recipient is required');
        }

        // Validar formato dos splits
        foreach ($this->splits as $split) {
            if (!isset($split['recipient_id']) || !isset($split['amount'])) {
                throw new \InvalidArgumentException('Each split must have recipient_id and amount');
            }
        }

        // Validar que soma dos splits não excede o total
        if (!$this->isValid()) {
            throw new InvalidAmountException('Total split amount exceeds payment amount');
        }
    }

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        float $amount,
        array $splits,
        Currency|string $currency = Currency::BRL,
        ?string $paymentMethod = null,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        // Converte Currency
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }

        // Cria Money
        $money = Money::from($amount, $currency);

        return new self(
            money: $money,
            splits: $splits,
            paymentMethod: $paymentMethod,
            description: $description,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'splits' => $this->splits,
            'payment_method' => $this->paymentMethod,
            'description' => $this->description,
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

    public function getTotalSplitAmount(): float
    {
        return array_reduce($this->splits, function ($carry, $split) {
            return $carry + ($split['amount'] ?? 0);
        }, 0.0);
    }

    public function isValid(): bool
    {
        return $this->getTotalSplitAmount() <= $this->money->amount();
    }

    public function getSplitCount(): int
    {
        return count($this->splits);
    }

    public function getRemainingAmount(): Money
    {
        $totalSplit = $this->getTotalSplitAmount();
        $remaining = $this->money->amount() - $totalSplit;
        
        return Money::from($remaining, $this->money->currency());
    }

    public function getSplitPercentages(): array
    {
        $total = $this->money->amount();
        
        return array_map(function($split) use ($total) {
            $percentage = ($split['amount'] / $total) * 100;
            return [
                'recipient_id' => $split['recipient_id'],
                'amount' => $split['amount'],
                'percentage' => round($percentage, 2)
            ];
        }, $this->splits);
    }

    public function hasRemainder(): bool
    {
        return $this->getTotalSplitAmount() < $this->money->amount();
    }
}
