<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Money;

/**
 * VERSÃO MELHORADA com Enums e ValueObjects
 */
class TransactionStatusResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly PaymentStatus $status,
        public readonly ?Money $money,
        public readonly ?array $rawResponse = null
    ) {}

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        bool $success,
        string $transactionId,
        string $status,
        ?float $amount = null,
        ?string $currency = 'BRL',
        ?array $rawResponse = null
    ): self {
        // Converte status para Enum
        $statusEnum = PaymentStatus::fromString($status);

        // Cria Money se amount fornecido
        $money = null;
        if ($amount !== null && $currency !== null) {
            $money = Money::from($amount, Currency::fromString($currency));
        }

        return new self(
            success: $success,
            transactionId: $transactionId,
            status: $statusEnum,
            money: $money,
            rawResponse: $rawResponse
        );
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

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isRefunded(): bool
    {
        return $this->status->isRefunded();
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'amount' => $this->money?->amount(),
            'currency' => $this->money?->currency()->value,
            'formatted_amount' => $this->money?->formatted(),
            'raw_response' => $this->rawResponse,
        ];
    }
}
