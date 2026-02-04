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
 * // Custódia com liberação automática em 7 dias
 * $request = EscrowRequest::create(
 *     amount: 500.00,
 *     transactionId: 'txn_123',
 *     recipientId: 'seller_456',
 *     holdDays: 7
 * );
 * 
 * // Custódia com data específica
 * $request = EscrowRequest::create(
 *     amount: 1000.00,
 *     recipientId: 'seller_789',
 *     releaseDate: '2025-03-15'
 * );
 */
class EscrowRequest
{
    public function __construct(
        public readonly Money $money,
        public readonly ?string $transactionId = null,
        public readonly ?string $recipientId = null,
        public readonly ?int $holdDays = null,
        public readonly ?string $releaseDate = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null
    ) {
        // Validações
        if ($this->money->isZero() || $this->money->isNegative()) {
            throw new InvalidAmountException('Escrow amount must be greater than zero');
        }

        // Validar holdDays se fornecido
        if ($this->holdDays !== null && $this->holdDays < 1) {
            throw new \InvalidArgumentException('Hold days must be at least 1');
        }

        // Não pode ter holdDays e releaseDate ao mesmo tempo
        if ($this->holdDays !== null && $this->releaseDate !== null) {
            throw new \InvalidArgumentException('Cannot specify both holdDays and releaseDate');
        }

        // Validar formato de data se fornecido
        if ($this->releaseDate !== null) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->releaseDate);
            if (!$date || $date->format('Y-m-d') !== $this->releaseDate) {
                throw new \InvalidArgumentException('Invalid releaseDate format. Use Y-m-d');
            }
        }
    }

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        float $amount,
        Currency|string $currency = Currency::BRL,
        ?string $transactionId = null,
        ?string $recipientId = null,
        ?int $holdDays = null,
        ?string $releaseDate = null,
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
            transactionId: $transactionId,
            recipientId: $recipientId,
            holdDays: $holdDays,
            releaseDate: $releaseDate,
            description: $description,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'transaction_id' => $this->transactionId,
            'recipient_id' => $this->recipientId,
            'hold_days' => $this->holdDays,
            'release_date' => $this->releaseDate,
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

    public function hasAutoRelease(): bool
    {
        return $this->holdDays !== null || $this->releaseDate !== null;
    }

    public function isManualRelease(): bool
    {
        return !$this->hasAutoRelease();
    }

    public function getCalculatedReleaseDate(): ?\DateTime
    {
        if ($this->releaseDate !== null) {
            return \DateTime::createFromFormat('Y-m-d', $this->releaseDate);
        }

        if ($this->holdDays !== null) {
            $date = new \DateTime();
            $date->modify("+{$this->holdDays} days");
            return $date;
        }

        return null;
    }

    public function getReleaseType(): string
    {
        if ($this->releaseDate !== null) {
            return 'date';
        }
        if ($this->holdDays !== null) {
            return 'days';
        }
        return 'manual';
    }
}
