<?php

namespace IsraelNogueira\PaymentHub\Exceptions;

class InvalidAmountException extends PaymentHubException
{
    public function __construct(
        string $message = "Invalid amount",
        int $code = 422,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}