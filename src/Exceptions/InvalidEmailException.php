<?php

namespace IsraelNogueira\PaymentHub\Exceptions;

class InvalidEmailException extends PaymentHubException
{
    public function __construct(
        string $message = "Invalid email address",
        int $code = 422,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}