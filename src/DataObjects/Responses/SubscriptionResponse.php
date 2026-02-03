<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class SubscriptionResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $subscriptionId,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function isActive(): bool
    {
        return strtolower($this->status) === 'active';
    }

    public function isCancelled(): bool
    {
        return in_array(strtolower($this->status), ['cancelled', 'canceled']);
    }

    public function isSuspended(): bool
    {
        return strtolower($this->status) === 'suspended';
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'subscription_id' => $this->subscriptionId,
            'status' => $this->status,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}