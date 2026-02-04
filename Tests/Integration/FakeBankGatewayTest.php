<?php

namespace IsraelNogueira\PaymentHub\Tests\Integration;

use IsraelNogueira\PaymentHub\PaymentHub;
use IsraelNogueira\PaymentHub\Gateways\FakeBankGateway;
use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\BoletoPaymentRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\SubscriptionRequest;
use IsraelNogueira\PaymentHub\DataObjects\Requests\RefundRequest;
use PHPUnit\Framework\TestCase;

class FakeBankGatewayTest extends TestCase
{
    private PaymentHub $hub;

    protected function setUp(): void
    {
        $this->hub = new PaymentHub(new FakeBankGateway());
    }

    public function testCreatePixPayment(): void
    {
        $request = new PixPaymentRequest(
            amount: 100.50,
            currency: 'BRL',
            description: 'Test payment',
            customerName: 'John Doe',
            customerDocument: '12345678909',
            customerEmail: 'john@example.com'
        );

        $response = $this->hub->createPixPayment($request);

        $this->assertTrue($response->success);
        $this->assertNotEmpty($response->transactionId);
        $this->assertEquals(100.50, $response->amount);
        $this->assertEquals('BRL', $response->currency);
    }

    public function testGetPixQrCode(): void
    {
        $request = new PixPaymentRequest(
            amount: 100.50,
            currency: 'BRL',
            description: 'Test payment',
            customerName: 'John Doe',
            customerDocument: '12345678909',
            customerEmail: 'john@example.com'
        );

        $response = $this->hub->createPixPayment($request);
        $qrCode = $this->hub->getPixQrCode($response->transactionId);

        $this->assertNotEmpty($qrCode);
    }

    public function testCreateCreditCardPayment(): void
    {
        $request = new CreditCardPaymentRequest(
            amount: 250.00,
            currency: 'BRL',
            cardNumber: '4111111111111111',
            cardHolderName: 'JOHN DOE',
            cardExpiryMonth: '12',
            cardExpiryYear: '2028',
            cardCvv: '123',
            installments: 3,
            capture: true,
            description: 'Test payment'
        );

        $response = $this->hub->createCreditCardPayment($request);

        $this->assertTrue($response->success);
        $this->assertNotEmpty($response->transactionId);
        $this->assertEquals(250.00, $response->amount);
    }

    public function testCreateBoleto(): void
    {
        $request = new BoletoPaymentRequest(
            amount: 500.00,
            currency: 'BRL',
            dueDate: '2026-03-15',
            description: 'Test payment',
            customerName: 'John Doe',
            customerDocument: '12345678909',
            customerEmail: 'john@example.com'
        );

        $response = $this->hub->createBoleto($request);

        $this->assertTrue($response->success);
        $this->assertNotEmpty($response->transactionId);
        $this->assertEquals(500.00, $response->amount);
    }

    public function testGetBoletoUrl(): void
    {
        $request = new BoletoPaymentRequest(
            amount: 500.00,
            currency: 'BRL',
            dueDate: '2026-03-15',
            description: 'Test payment',
            customerName: 'John Doe',
            customerDocument: '12345678909',
            customerEmail: 'john@example.com'
        );

        $response = $this->hub->createBoleto($request);
        $url = $this->hub->getBoletoUrl($response->transactionId);

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('http', $url);
    }

    public function testCreateSubscription(): void
    {
        $request = new SubscriptionRequest(
            amount: 99.90,
            currency: 'BRL',
            interval: 'monthly',
            customerId: 'cust_123',
            cardToken: 'tok_abc123',
            description: 'Test subscription'
        );

        $response = $this->hub->createSubscription($request);

        $this->assertTrue($response->success);
        $this->assertNotEmpty($response->subscriptionId);
    }

    public function testCancelSubscription(): void
    {
        $request = new SubscriptionRequest(
            amount: 99.90,
            currency: 'BRL',
            interval: 'monthly',
            customerId: 'cust_123',
            cardToken: 'tok_abc123',
            description: 'Test subscription'
        );

        $createResponse = $this->hub->createSubscription($request);
        $cancelResponse = $this->hub->cancelSubscription($createResponse->subscriptionId);

        $this->assertTrue($cancelResponse->success);
    }

    public function testGetTransactionStatus(): void
    {
        $request = new PixPaymentRequest(
            amount: 100.50,
            currency: 'BRL',
            description: 'Test payment',
            customerName: 'John Doe',
            customerDocument: '12345678909',
            customerEmail: 'john@example.com'
        );

        $payment = $this->hub->createPixPayment($request);
        $status = $this->hub->getTransactionStatus($payment->transactionId);

        $this->assertTrue($status->success);
        $this->assertEquals($payment->transactionId, $status->transactionId);
    }

    public function testRefund(): void
    {
        $pixRequest = new PixPaymentRequest(
            amount: 100.50,
            currency: 'BRL',
            description: 'Test payment',
            customerName: 'John Doe',
            customerDocument: '12345678909',
            customerEmail: 'john@example.com'
        );

        $payment = $this->hub->createPixPayment($pixRequest);

        $refundRequest = new RefundRequest(
            transactionId: $payment->transactionId,
            amount: 100.50,
            reason: 'Customer request'
        );

        $refund = $this->hub->refund($refundRequest);

        $this->assertTrue($refund->success);
        $this->assertNotEmpty($refund->refundId);
        $this->assertEquals($payment->transactionId, $refund->transactionId);
    }

    public function testGetBalance(): void
    {
        $balance = $this->hub->getBalance();

        $this->assertTrue($balance->success);
        $this->assertGreaterThanOrEqual(0, $balance->balance);
    }

    public function testTokenizeCard(): void
    {
        $cardData = [
            'number' => '4111111111111111',
            'holder_name' => 'JOHN DOE',
            'expiry_month' => '12',
            'expiry_year' => '2028',
            'cvv' => '123'
        ];

        $token = $this->hub->tokenizeCard($cardData);

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('FAKE_TOKEN_', $token);
    }
}