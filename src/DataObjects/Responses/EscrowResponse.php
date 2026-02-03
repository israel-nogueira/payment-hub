<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class EscrowResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $escrowId,
        public readonly ?float $amount,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function isHeld(): bool
    {
        return strtolower($this->status) === 'held';
    }

    public function isReleased(): bool
    {
        return in_array(strtolower($this->status), ['released', 'completed']);
    }

    public function isPartiallyReleased(): bool
    {
        return strtolower($this->status) === 'partially_released';
    }

    public function isCancelled(): bool
    {
        return in_array(strtolower($this->status), ['cancelled', 'canceled']);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'escrow_id' => $this->escrowId,
            'amount' => $this->amount,
            'status' => $this->status,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}
