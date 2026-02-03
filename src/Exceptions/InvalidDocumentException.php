<?php

namespace IsraelNogueira\PaymentHub\Exceptions;

class InvalidDocumentException extends PaymentHubException
{
    public function __construct(
        string $message = "Invalid document number",
        int $code = 422,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}