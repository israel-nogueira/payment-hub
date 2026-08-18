<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Processors;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

class RefundWebhookProcessor implements WebhookProcessorInterface
{
    private const SUPPORTED_EVENTS = [
        'refund.created',
        'refund.completed',
        'refund.failed',
    ];

    public function __construct(
        private readonly ?\Closure $onRefundCompleted = null,
        private readonly ?\Closure $onRefundCreated   = null,
        private readonly ?\Closure $onRefundFailed    = null,
    ) {}

    public function process(WebhookPayload $payload): bool
    {
        $data = $payload->getData();

        match ($payload->getEventType()) {
            'refund.completed' => $this->onRefundCompleted && ($this->onRefundCompleted)($data),
            'refund.created'   => $this->onRefundCreated   && ($this->onRefundCreated)($data),
            'refund.failed'    => $this->onRefundFailed    && ($this->onRefundFailed)($data),
            default            => null,
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
