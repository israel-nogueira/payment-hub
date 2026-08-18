<?php

namespace IsraelNogueira\PaymentHub\Factories;

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Contracts\PaymentGatewayInterface;
use Psr\Log\LoggerInterface;

class PaymentHubFactory
{
    public static function create(
        PaymentGatewayInterface $gateway,
        ?LoggerInterface $logger = null
    ): PaymentHub {
        return new PaymentHub($gateway, $logger);
    }
}