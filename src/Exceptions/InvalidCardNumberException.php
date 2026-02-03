<?php

namespace IsraelNogueira\PaymentHub\Exceptions;

class InvalidCardNumberException extends PaymentHubException
{
    public function __construct(
        string $message = "Invalid card number",
        int $code = 422,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}