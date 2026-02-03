<?php

namespace IsraelNogueira\PaymentHub\DataObjects\Responses;

class PaymentLinkResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $linkId,
        public readonly ?string $url,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?array $rawResponse = null
    ) {}

    public function isActive(): bool
    {
        return strtolower($this->status) === 'active';
    }

    public function isExpired(): bool
    {
        return strtolower($this->status) === 'expired';
    }

    public function isUsed(): bool
    {
        return strtolower($this->status) === 'used';
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'link_id' => $this->linkId,
            'url' => $this->url,
            'status' => $this->status,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}