<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Money;

/**
 * VERSÃO MELHORADA com Enums e ValueObjects
 */
class RefundResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $refundId,
        public readonly string $transactionId,
        public readonly Money $money,
        public readonly PaymentStatus $status,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    /**
     * Factory method - mantém compatibilidade
     */
    public static function create(
        bool $success,
        string $refundId,
        string $transactionId,
        float $amount,
        string $status,
        ?string $currency = 'BRL',
        ?string $message = null,
        ?array $rawResponse = null
    ): self {
        // Converte status para Enum
        $statusEnum = PaymentStatus::fromString($status);

        // Cria Money
        $money = Money::from($amount, Currency::fromString($currency));

        return new self(
            success: $success,
            refundId: $refundId,
            transactionId: $transactionId,
            money: $money,
            status: $statusEnum,
            message: $message,
            rawResponse: $rawResponse
        );
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

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function isCompleted(): bool
    {
        return $this->status->isPaid(); // Refunded usa mesmo status de paid
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function isRefunded(): bool
    {
        return $this->status->isRefunded();
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refund_id' => $this->refundId,
            'transaction_id' => $this->transactionId,
            'amount' => $this->money->amount(),
            'currency' => $this->money->currency()->value,
            'formatted_amount' => $this->money->formatted(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}
