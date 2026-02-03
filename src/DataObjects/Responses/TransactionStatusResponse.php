<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class TransactionStatusResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly string $status,
        public readonly ?float $amount,
        public readonly string $currency,
        public readonly ?array $rawResponse = null
    ) {}

    public function isPaid(): bool
    {
        return in_array(strtolower($this->status), ['paid', 'approved', 'completed', 'success']);
    }

    public function isPending(): bool
    {
        return in_array(strtolower($this->status), ['pending', 'processing', 'waiting']);
    }

    public function isFailed(): bool
    {
        return in_array(strtolower($this->status), ['failed', 'declined', 'rejected', 'error']);
    }

    public function isCancelled(): bool
    {
        return in_array(strtolower($this->status), ['cancelled', 'canceled', 'voided']);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'raw_response' => $this->rawResponse,
        ];
    }
}