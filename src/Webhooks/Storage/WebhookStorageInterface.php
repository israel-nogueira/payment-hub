<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Storage;

use IsraelNogueira\PaymentHub\Webhooks\WebhookPayload;

interface WebhookStorageInterface
{
    public function wasProcessed(string $id): bool;
    public function store(WebhookPayload $payload, bool $success): bool;
    public function getStats(): array;
    public function cleanup(int $olderThanDays = 30): int;
}
