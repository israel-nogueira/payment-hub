<?php

namespace IsraelNogueira\PaymentHub\Factories;

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;
use Psr\Log\LoggerInterface;

class PaymentHubFactory
{
    public static function createFake(?LoggerInterface $logger = null): PaymentHub
    {
        return new PaymentHub(new FakeBankGateway(), $logger);
    }

    public static function createForStripe(
        string $apiKey,
        bool $sandbox = false,
        ?LoggerInterface $logger = null
    ): PaymentHub {
        throw new \RuntimeException('Stripe gateway not implemented yet');
        // return new PaymentHub(new StripeGateway($apiKey, $sandbox), $logger);
    }

    public static function createForPagarMe(
        string $apiKey,
        bool $sandbox = false,
        ?LoggerInterface $logger = null
    ): PaymentHub {
        throw new \RuntimeException('PagarMe gateway not implemented yet');
        // return new PaymentHub(new PagarMeGateway($apiKey, $sandbox), $logger);
    }

    public static function createForMercadoPago(
        string $accessToken,
        bool $sandbox = false,
        ?LoggerInterface $logger = null
    ): PaymentHub {
        throw new \RuntimeException('MercadoPago gateway not implemented yet');
        // return new PaymentHub(new MercadoPagoGateway($accessToken, $sandbox), $logger);
    }

    public static function createForAsaas(
        string $apiKey,
        bool $sandbox = false,
        ?LoggerInterface $logger = null
    ): PaymentHub {
        throw new \RuntimeException('Asaas gateway not implemented yet');
        // return new PaymentHub(new AsaasGateway($apiKey, $sandbox), $logger);
    }
}