<?php

namespace IsraelNogueira\PaymentHub\Exceptions;

class GatewayException extends PaymentHubException
{
    public function __construct(
        string $message = "Gateway error occurred",
        int $code = 500,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Create from gateway response
     */
    public static function fromResponse(array $response, string $gateway): self
    {
        return new self(
            $response['message'] ?? 'Unknown gateway error',
            $response['code'] ?? 500,
            null,
            [
                'gateway' => $gateway,
                'response' => $response,
            ]
        );
    }

    /**
     * Get gateway name from context
     */
    public function getGateway(): ?string
    {
        return $this->context['gateway'] ?? null;
    }

    /**
     * Get gateway response from context
     */
    public function getGatewayResponse(): ?array
    {
        return $this->context['response'] ?? null;
    }
}