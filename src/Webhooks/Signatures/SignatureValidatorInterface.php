<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Webhooks\Signatures;

interface SignatureValidatorInterface
{
    public function validate(string $rawBody, string $signature, string $secret): bool;
}