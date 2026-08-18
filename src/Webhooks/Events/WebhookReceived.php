<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Events;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

class WebhookReceived
{
    public function __construct(
        public readonly WebhookPayload $payload
    ) {}
}
