<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Processors;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

class PaymentWebhookProcessor implements WebhookProcessorInterface
{
    private const SUPPORTED_EVENTS = [
        'payment.created',
        'payment.completed',
        'payment.failed',
        'payment.cancelled',
    ];

    public function __construct(
        private readonly ?\Closure $onPaymentCompleted = null,
        private readonly ?\Closure $onPaymentFailed    = null,
        private readonly ?\Closure $onPaymentCreated   = null,
        private readonly ?\Closure $onPaymentCancelled = null,
    ) {}

    public function process(WebhookPayload $payload): bool
    {
        $data = $payload->getData();

        match ($payload->getEventType()) {
            'payment.completed' => $this->onPaymentCompleted && ($this->onPaymentCompleted)($data),
            'payment.failed'    => $this->onPaymentFailed    && ($this->onPaymentFailed)($data),
            'payment.created'   => $this->onPaymentCreated   && ($this->onPaymentCreated)($data),
            'payment.cancelled' => $this->onPaymentCancelled && ($this->onPaymentCancelled)($data),
            default             => null,
        };

        return true;
    }

    public function supports(string $eventType): bool
    {
        return in_array($eventType, self::SUPPORTED_EVENTS, true);
    }

    public function getPriority(): int
    {
        return 50;
    }

    public function validate(WebhookPayload $payload): bool
    {
        return $payload->has('id');
    }
}
