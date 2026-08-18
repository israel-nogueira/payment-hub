<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\ValueObjects\Money;

class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId,
        public readonly PaymentStatus $status,
        public readonly ?Money $money,
        public readonly ?string $message,
        public readonly ?array $rawResponse,
        public readonly ?array $metadata = null
    ) {}

    public static function create(
        bool $success,
        ?string $transactionId,
        string $status,
        ?float $amount = null,
        ?string $currency = 'BRL',
        ?string $message = null,
        ?array $rawResponse = null,
        ?array $metadata = null
    ): self {
        $statusEnum = PaymentStatus::fromString($status);

        $money = null;
        if ($amount !== null && $currency !== null) {
            $money = Money::from($amount, Currency::fromString($currency));
        }

        return new self(
            success:       $success,
            transactionId: $transactionId,
            status:        $statusEnum,
            money:         $money,
            message:       $message,
            rawResponse:   $rawResponse,
            metadata:      $metadata
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * ✅ Corrigido: delega pro enum em vez de negar $success
     * Um pagamento cancelado tem success=false mas não é isFailed()
     */
    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isRefunded(): bool
    {
        return $this->status->isRefunded();
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function getStatusColor(): string
    {
        return $this->status->color();
    }

    public function getFormattedAmount(): ?string
    {
        return $this->money?->formatted();
    }

    public function getAmount(): ?float
    {
        return $this->money?->amount();
    }

    public function getCurrency(): ?string
    {
        return $this->money?->currency()->value;
    }

    public function toArray(): array
    {
        return [
            'success'          => $this->success,
            'transaction_id'   => $this->transactionId,
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'amount'           => $this->money?->amount(),
            'currency'         => $this->money?->currency()->value,
            'formatted_amount' => $this->money?->formatted(),
            'message'          => $this->message,
            'metadata'         => $this->metadata,
            'raw_response'     => $this->rawResponse,
        ];
    }

    public function toUI(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'status' => [
                'code'  => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'amount'  => $this->money?->formatted(),
            'message' => $this->message ?? $this->getDefaultMessage(),
        ];
    }

    private function getDefaultMessage(): string
    {
        return match(true) {
            $this->status->isPaid()      => 'Pagamento aprovado com sucesso!',
            $this->status->isPending()   => 'Aguardando confirmação do pagamento...',
            $this->status->isFailed()    => 'Pagamento recusado. Tente novamente.',
            $this->status->isCancelled() => 'Pagamento cancelado.',
            default                      => 'Status do pagamento atualizado.',
        };
    }
}