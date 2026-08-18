<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Events;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

class WebhookFailed
{
    public function __construct(
        public readonly WebhookPayload $payload,
        public readonly string $reason,
        public readonly ?\Throwable $error = null
    ) {}
}
