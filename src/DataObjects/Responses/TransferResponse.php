<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class TransferResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transferId,
        public readonly ?float $amount,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function isCompleted(): bool
    {
        return in_array(strtolower($this->status), ['completed', 'success', 'transferred']);
    }

    public function isScheduled(): bool
    {
        return strtolower($this->status) === 'scheduled';
    }

    public function isPending(): bool
    {
        return in_array(strtolower($this->status), ['pending', 'processing']);
    }

    public function isFailed(): bool
    {
        return in_array(strtolower($this->status), ['failed', 'error', 'rejected']);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transfer_id' => $this->transferId,
            'amount' => $this->amount,
            'status' => $this->status,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}