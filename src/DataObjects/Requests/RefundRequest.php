<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Requests;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Money;

/**
 * VERSÃO MELHORADA com ValueObjects
 * 
 * Como usar:
 * 
 * // Reembolso total
 * $request = RefundRequest::create(
 *     transactionId: 'txn_123',
 *     reason: 'Cliente solicitou cancelamento'
 * );
 * 
 * // Reembolso parcial
 * $request = RefundRequest::create(
 *     transactionId: 'txn_123',
 *     amount: 50.00,
 *     reason: 'Reembolso parcial acordado'
 * );
 */
class RefundRequest
{
    public function __construct(
        public readonly string $transactionId,
        public readonly ?Money $money = null,
        public readonly ?string $reason = null,
        public readonly ?array $metadata = null
    ) {}

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        string $transactionId,
        ?float $amount = null,
        ?string $currency = 'BRL',
        ?string $reason = null,
        ?array $metadata = null
    ): self {
        // Cria Money se amount fornecido
        $money = null;
        if ($amount !== null) {
            $currencyEnum = is_string($currency) 
                ? Currency::fromString($currency) 
                : $currency;
            $money = Money::from($amount, $currencyEnum);
        }

        return new self(
            transactionId: $transactionId,
            money: $money,
            reason: $reason,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'amount' => $this->money?->amount(),
            'currency' => $this->money?->currency()->value,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ];
    }

    // Getters para compatibilidade
    public function getAmount(): ?float
    {
        return $this->money?->amount();
    }

    public function getCurrency(): ?string
    {
        return $this->money?->currency()->value;
    }

    public function getFormattedAmount(): ?string
    {
        return $this->money?->formatted();
    }

    public function isPartialRefund(): bool
    {
        return $this->money !== null;
    }

    public function isFullRefund(): bool
    {
        return $this->money === null;
    }
}
