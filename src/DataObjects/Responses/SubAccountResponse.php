<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class SubAccountResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $subAccountId,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function isActive(): bool
    {
        return strtolower($this->status) === 'active';
    }

    public function isInactive(): bool
    {
        return strtolower($this->status) === 'inactive';
    }

    public function isPending(): bool
    {
        return in_array(strtolower($this->status), ['pending', 'under_review']);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'sub_account_id' => $this->subAccountId,
            'status' => $this->status,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}