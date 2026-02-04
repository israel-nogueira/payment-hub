<?php

declare(strict_types=1);

namespace IsraelNogueira\PaymentHub\Tests\Unit\DataObjects\Requests;

use IsraelNogueira\PaymentHub\DataObjects\Requests\PixPaymentRequest;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Enums\PaymentMethod;
use IsraelNogueira\PaymentHub\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class PixPaymentRequestTest extends TestCase
{
    public function test_can_create_pix_payment_request(): void
    {
        // Arrange
        $amount = Money::fromCents(5000, Currency::BRL);
        $pixKey = '11999887766';
        $description = 'Pagamento via PIX';

        // Act
        $request = new PixPaymentRequest(
            amount: $amount,
            pixKey: $pixKey,
            description: $description
        );

        // Assert
        $this->assertInstanceOf(PixPaymentRequest::class, $request);
        $this->assertEquals($amount, $request->amount);
        $this->assertEquals($pixKey, $request->pixKey);
        $this->assertEquals($description, $request->description);
    }

    public function test_can_create_with_cpf_key(): void
    {
        // Arrange
        $cpfKey = '123.456.789-00';

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: $cpfKey,
            description: 'PIX com chave CPF'
        );

        // Assert
        $this->assertEquals($cpfKey, $request->pixKey);
        $this->assertEquals('cpf', $request->getPixKeyType());
    }

    public function test_can_create_with_cnpj_key(): void
    {
        // Arrange
        $cnpjKey = '12.345.678/0001-00';

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(15000, Currency::BRL),
            pixKey: $cnpjKey,
            description: 'PIX com chave CNPJ'
        );

        // Assert
        $this->assertEquals($cnpjKey, $request->pixKey);
        $this->assertEquals('cnpj', $request->getPixKeyType());
    }

    public function test_can_create_with_email_key(): void
    {
        // Arrange
        $emailKey = 'pagamento@example.com';

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(7500, Currency::BRL),
            pixKey: $emailKey,
            description: 'PIX com chave e-mail'
        );

        // Assert
        $this->assertEquals($emailKey, $request->pixKey);
        $this->assertEquals('email', $request->getPixKeyType());
    }

    public function test_can_create_with_phone_key(): void
    {
        // Arrange
        $phoneKey = '+5511999887766';

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(12000, Currency::BRL),
            pixKey: $phoneKey,
            description: 'PIX com chave telefone'
        );

        // Assert
        $this->assertEquals($phoneKey, $request->pixKey);
        $this->assertEquals('phone', $request->getPixKeyType());
    }

    public function test_can_create_with_random_key(): void
    {
        // Arrange
        $randomKey = '123e4567-e89b-12d3-a456-426614174000';

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(8000, Currency::BRL),
            pixKey: $randomKey,
            description: 'PIX com chave aleatória'
        );

        // Assert
        $this->assertEquals($randomKey, $request->pixKey);
        $this->assertEquals('random', $request->getPixKeyType());
    }

    public function test_can_generate_qr_code(): void
    {
        // Arrange
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'Teste QR Code'
        );

        // Act
        $qrCode = $request->generateQRCode();

        // Assert
        $this->assertIsString($qrCode);
        $this->assertNotEmpty($qrCode);
    }

    public function test_can_generate_copy_paste_code(): void
    {
        // Arrange
        $request = new PixPaymentRequest(
            amount: Money::fromCents(5000, Currency::BRL),
            pixKey: 'pagamento@example.com',
            description: 'Teste Copia e Cola'
        );

        // Act
        $code = $request->generateCopyPasteCode();

        // Assert
        $this->assertIsString($code);
        $this->assertNotEmpty($code);
        $this->assertStringContainsString('00020126', $code); // Início padrão EMV
    }

    public function test_can_set_expiration(): void
    {
        // Arrange
        $expiresIn = 3600; // 1 hora

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'PIX com expiração',
            expiresIn: $expiresIn
        );

        // Assert
        $this->assertEquals($expiresIn, $request->expiresIn);
        $this->assertNotNull($request->getExpirationDate());
    }

    public function test_defaults_to_no_expiration(): void
    {
        // Arrange & Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'PIX sem expiração'
        );

        // Assert
        $this->assertNull($request->expiresIn);
        $this->assertNull($request->getExpirationDate());
    }

    public function test_can_add_customer_data(): void
    {
        // Arrange
        $customerName = 'João Silva';
        $customerDocument = '12345678900';

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'PIX com dados do cliente',
            customerName: $customerName,
            customerDocument: $customerDocument
        );

        // Assert
        $this->assertEquals($customerName, $request->customerName);
        $this->assertEquals($customerDocument, $request->customerDocument);
    }

    public function test_can_add_additional_info(): void
    {
        // Arrange
        $additionalInfo = [
            'order_id' => '12345',
            'product' => 'Assinatura Premium',
            'notes' => 'Pagamento recorrente'
        ];

        // Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(9990, Currency::BRL),
            pixKey: 'vendas@empresa.com',
            description: 'PIX com informações adicionais',
            additionalInfo: $additionalInfo
        );

        // Assert
        $this->assertEquals($additionalInfo, $request->additionalInfo);
    }

    public function test_can_convert_to_array(): void
    {
        // Arrange
        $request = new PixPaymentRequest(
            amount: Money::fromCents(15000, Currency::BRL),
            pixKey: 'pagamento@example.com',
            description: 'Teste conversão array'
        );

        // Act
        $array = $request->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals('pix', $array['payment_method']);
        $this->assertEquals(15000, $array['amount']['cents']);
        $this->assertEquals('BRL', $array['amount']['currency']);
        $this->assertEquals('pagamento@example.com', $array['pix']['key']);
        $this->assertEquals('email', $array['pix']['key_type']);
        $this->assertEquals('Teste conversão array', $array['description']);
    }

    public function test_validates_empty_pix_key(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PIX key cannot be empty');

        new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '',
            description: 'Chave vazia'
        );
    }

    public function test_validates_invalid_email_key(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PIX email key');

        new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: 'invalid-email',
            description: 'E-mail inválido'
        );
    }

    public function test_validates_invalid_cpf_key(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PIX CPF key');

        new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '000.000.000-00', // CPF inválido
            description: 'CPF inválido'
        );
    }

    public function test_validates_negative_expiration(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration time must be positive');

        new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'Expiração negativa',
            expiresIn: -100
        );
    }

    public function test_payment_method_is_pix(): void
    {
        // Arrange & Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'Teste método'
        );

        // Assert
        $this->assertEquals(PaymentMethod::PIX, $request->getPaymentMethod());
    }

    public function test_is_instant_payment(): void
    {
        // Arrange & Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'Teste instantâneo'
        );

        // Assert
        $this->assertTrue($request->isInstantPayment());
    }

    public function test_can_check_if_expired(): void
    {
        // Arrange
        $expiredRequest = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'PIX expirado',
            expiresIn: 1 // 1 segundo
        );

        $validRequest = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'PIX válido',
            expiresIn: 3600 // 1 hora
        );

        // Act
        sleep(2); // Espera 2 segundos

        // Assert
        $this->assertTrue($expiredRequest->isExpired());
        $this->assertFalse($validRequest->isExpired());
    }

    public function test_never_expires_without_expiration_set(): void
    {
        // Arrange & Act
        $request = new PixPaymentRequest(
            amount: Money::fromCents(10000, Currency::BRL),
            pixKey: '11999887766',
            description: 'PIX sem expiração'
        );

        // Assert
        $this->assertFalse($request->isExpired());
    }
}
