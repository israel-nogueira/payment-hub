<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class CustomerResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $customerId,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'customer_id' => $this->customerId,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}