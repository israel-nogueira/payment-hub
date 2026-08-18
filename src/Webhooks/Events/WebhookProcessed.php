<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Events;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

class WebhookProcessed
{
    public function __construct(
        public readonly WebhookPayload $payload,
        public readonly float $duration
    ) {}
}
