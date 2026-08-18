<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks;

class WebhookResult
{
    private function __construct(
        private readonly bool           $success,
        private readonly string         $reason,
        private readonly WebhookPayload $payload,
        private readonly float          $duration,
        private readonly ?\Throwable    $error = null,
    ) {}

    public static function success(WebhookPayload $payload, float $duration): self
    {
        return new self(true, 'success', $payload, $duration);
    }

    public static function failed(WebhookPayload $payload, string $reason, ?\Throwable $error = null): self
    {
        return new self(false, $reason, $payload, 0.0, $error);
    }

    public static function alreadyProcessed(WebhookPayload $payload): self
    {
        return new self(true, 'already_processed', $payload, 0.0);
    }

    public static function invalidSignature(WebhookPayload $payload): self
    {
        return new self(false, 'invalid_signature', $payload, 0.0);
    }

    public static function noProcessorFound(WebhookPayload $payload): self
    {
        return new self(false, 'no_processor_found', $payload, 0.0);
    }

    public static function validationFailed(WebhookPayload $payload): self
    {
        return new self(false, 'validation_failed', $payload, 0.0);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getPayload(): WebhookPayload
    {
        return $this->payload;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getError(): ?\Throwable
    {
        return $this->error;
    }
}