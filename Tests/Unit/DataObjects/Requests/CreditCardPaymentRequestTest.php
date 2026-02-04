<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Tests\Unit\DataObjects\Requests;

use IsraelNogueira\PaymentHub\DataObjects\Requests\CreditCardPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Enums\PaymentMethod;
use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class CreditCardPaymentRequestTest extends TestCase
{
    public function test_can_create_credit_card_payment_request(): void
    {
        // Arrange
        $amount = Money::fromCents(10000, Currency::BRL);
        $cardNumber = new CardNumber('4111111111111111');
        $cardHolder = 'João Silva';
        $expiryMonth = '12';
        $expiryYear = '2025';
        $cvv = '123';

        // Act
        $request = new CreditCardPaymentRequest(
            amount: $amount,
            cardNumber: $cardNumber,
            cardHolderName: $cardHolder,
            expiryMonth: $expiryMonth,
            expiryYear: $expiryYear,
            cvv: $cvv
        );

        // Assert
        $this->assertInstanceOf(CreditCardPaymentRequest::class, $request);
        $this->assertEquals($amount, $request->amount);
        $this->assertEquals($cardNumber, $request->cardNumber);
        $this->assertEquals($cardHolder, $request->cardHolderName);
        $this->assertEquals($expiryMonth, $request->expiryMonth);
        $this->assertEquals($expiryYear, $request->expiryYear);
        $this->assertEquals($cvv, $request->cvv);
    }

    public function test_can_create_with_installments(): void
    {
        // Arrange
        $amount = Money::fromCents(30000, Currency::BRL);
        $cardNumber = new CardNumber('5555555555554444');
        $installments = 3;

        // Act
        $request = new CreditCardPaymentRequest(
            amount: $amount,
            cardNumber: $cardNumber,
            cardHolderName: 'Maria Santos',
            expiryMonth: '06',
            expiryYear: '2026',
            cvv: '456',
            installments: $installments
        );

        // Assert
        $this->assertEquals($installments, $request->installments);
        $this->assertEquals(10000, $request->getInstallmentAmount()->getCents());
    }

    public function test_defaults_to_one_installment(): void
    {
        // Arrange & Act
        $request = new CreditCardPaymentRequest(
            amount: Money::fromCents(5000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Pedro Oliveira',
            expiryMonth: '03',
            expiryYear: '2027',
            cvv: '789'
        );

        // Assert
        $this->assertEquals(1, $request->installments);
    }

    public function test_can_add_description(): void
    {
        // Arrange
        $description = 'Compra em loja virtual - Pedido #12345';

        // Act
        $request = new CreditCardPaymentRequest(
            amount: Money::fromCents(15000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Ana Costa',
            expiryMonth: '09',
            expiryYear: '2025',
            cvv: '321',
            description: $description
        );

        // Assert
        $this->assertEquals($description, $request->description);
    }

    public function test_can_add_customer_data(): void
    {
        // Arrange
        $customerId = 'cust_123456';
        $customerEmail = 'cliente@example.com';
        $customerDocument = '12345678900';

        // Act
        $request = new CreditCardPaymentRequest(
            amount: Money::fromCents(20000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Carlos Ferreira',
            expiryMonth: '11',
            expiryYear: '2026',
            cvv: '654',
            customerId: $customerId,
            customerEmail: $customerEmail,
            customerDocument: $customerDocument
        );

        // Assert
        $this->assertEquals($customerId, $request->customerId);
        $this->assertEquals($customerEmail, $request->customerEmail);
        $this->assertEquals($customerDocument, $request->customerDocument);
    }

    public function test_can_convert_to_array(): void
    {
        // Arrange
        $request = new CreditCardPaymentRequest(
            amount: Money::fromCents(25000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Beatriz Alves',
            expiryMonth: '08',
            expiryYear: '2027',
            cvv: '147',
            installments: 2,
            description: 'Teste de conversão'
        );

        // Act
        $array = $request->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals('credit_card', $array['payment_method']);
        $this->assertEquals(25000, $array['amount']['cents']);
        $this->assertEquals('BRL', $array['amount']['currency']);
        $this->assertEquals('411111******1111', $array['card']['number_masked']);
        $this->assertEquals('Beatriz Alves', $array['card']['holder_name']);
        $this->assertEquals('08', $array['card']['expiry_month']);
        $this->assertEquals('2027', $array['card']['expiry_year']);
        $this->assertEquals(2, $array['installments']);
        $this->assertEquals('Teste de conversão', $array['description']);
    }

    public function test_cvv_is_not_included_in_array(): void
    {
        // Arrange
        $request = new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Security Test',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '999'
        );

        // Act
        $array = $request->toArray();

        // Assert
        $this->assertArrayNotHasKey('cvv', $array);
        $this->assertArrayNotHasKey('cvv', $array['card'] ?? []);
    }

    public function test_card_number_is_masked_in_array(): void
    {
        // Arrange
        $request = new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('5555555555554444'),
            cardHolderName: 'Mask Test',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '123'
        );

        // Act
        $array = $request->toArray();

        // Assert
        $this->assertEquals('555555******4444', $array['card']['number_masked']);
        $this->assertArrayNotHasKey('number', $array['card']);
    }

    public function test_validates_expiry_date_format(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        
        new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Invalid Date',
            expiryMonth: '13', // Mês inválido
            expiryYear: '2025',
            cvv: '123'
        );
    }

    public function test_validates_cvv_length(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        
        new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Invalid CVV',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '12' // CVV muito curto
        );
    }

    public function test_validates_minimum_installments(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        
        new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Invalid Installments',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '123',
            installments: 0 // Deve ser >= 1
        );
    }

    public function test_validates_maximum_installments(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        
        new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Too Many Installments',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '123',
            installments: 25 // Máximo geralmente é 24
        );
    }

    public function test_can_check_if_expired(): void
    {
        // Arrange
        $expiredCard = new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Expired Card',
            expiryMonth: '01',
            expiryYear: '2020',
            cvv: '123'
        );

        $validCard = new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Valid Card',
            expiryMonth: '12',
            expiryYear: '2030',
            cvv: '123'
        );

        // Assert
        $this->assertTrue($expiredCard->isExpired());
        $this->assertFalse($validCard->isExpired());
    }

    public function test_payment_method_is_credit_card(): void
    {
        // Arrange & Act
        $request = new CreditCardPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            cardNumber: new CardNumber('4111111111111111'),
            cardHolderName: 'Method Test',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '123'
        );

        // Assert
        $this->assertEquals(PaymentMethod::CREDIT_CARD, $request->getPaymentMethod());
    }
}
