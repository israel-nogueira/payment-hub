<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId,
        public readonly string $status,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly ?string $message,
        public readonly ?array $rawResponse,
        public readonly ?array $metadata = null
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailed(): bool
    {
        return !$this->success;
    }

    public function isPending(): bool
    {
        return in_array(strtolower($this->status), ['pending', 'processing', 'waiting']);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'raw_response' => $this->rawResponse,
        ];
    }
}