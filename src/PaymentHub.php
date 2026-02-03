<?php

namespace IsraelNogueira\PaymentHub;

use IsraelNogueira\PaymentHub\Contracts\PaymentGatewayInterface;
use IsraelNogueira\PaymentHub\Exceptions\PaymentHubException;

class PaymentHub
{
    private PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function setGateway(PaymentGatewayInterface $gateway): self
    {
        $this->gateway = $gateway;
        return $this;
    }

    public function getGateway(): PaymentGatewayInterface
    {
        return $this->gateway;
    }

    public function __call(string $method, array $arguments)
    {
        if (!method_exists($this->gateway, $method)) {
            throw new PaymentHubException(
                "Method {$method} does not exist in the current gateway",
                500,
                null,
                ['method' => $method, 'gateway' => get_class($this->gateway)]
            );
        }

        return $this->gateway->$method(...$arguments);
    }
}