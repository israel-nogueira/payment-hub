<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class RefundResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $refundId,
        public readonly string $transactionId,
        public readonly float $amount,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function isCompleted(): bool
    {
        return in_array(strtolower($this->status), ['refunded', 'completed', 'success']);
    }

    public function isPending(): bool
    {
        return in_array(strtolower($this->status), ['pending', 'processing']);
    }

    public function isFailed(): bool
    {
        return in_array(strtolower($this->status), ['failed', 'error']);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refund_id' => $this->refundId,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'status' => $this->status,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}