<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Processors;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

class SubscriptionWebhookProcessor implements WebhookProcessorInterface
{
    private const SUPPORTED_EVENTS = [
        'subscription.created',
        'subscription.activated',
        'subscription.cancelled',
        'subscription.renewed',
        'subscription.payment_failed',
    ];

    public function __construct(
        private readonly ?\Closure $onSubscriptionCreated       = null,
        private readonly ?\Closure $onSubscriptionActivated     = null,
        private readonly ?\Closure $onSubscriptionCancelled     = null,
        private readonly ?\Closure $onSubscriptionRenewed       = null,
        private readonly ?\Closure $onSubscriptionPaymentFailed = null,
    ) {}

    public function process(WebhookPayload $payload): bool
    {
        $data = $payload->getData();

        match ($payload->getEventType()) {
            'subscription.created'        => $this->onSubscriptionCreated       && ($this->onSubscriptionCreated)($data),
            'subscription.activated'      => $this->onSubscriptionActivated     && ($this->onSubscriptionActivated)($data),
            'subscription.cancelled'      => $this->onSubscriptionCancelled     && ($this->onSubscriptionCancelled)($data),
            'subscription.renewed'        => $this->onSubscriptionRenewed       && ($this->onSubscriptionRenewed)($data),
            'subscription.payment_failed' => $this->onSubscriptionPaymentFailed && ($this->onSubscriptionPaymentFailed)($data),
            default                       => null,
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
