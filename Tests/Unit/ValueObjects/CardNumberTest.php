<?php

namespace IsraelNogueira\PaymentHub\Tests\Unit\ValueObjects;

use IsraelNogueira\PaymentHub\ValueObjects\CardNumber;
use IsraelNogueira\PaymentHub\Exceptions\InvalidCardNumberException;
use PHPUnit\Framework\TestCase;

class CardNumberTest extends TestCase
{
    public function testValidVisaCard(): void
    {
        $card = CardNumber::fromString('4111111111111111');
        
        $this->assertEquals('4111111111111111', $card->value());
        $this->assertEquals('visa', $card->brand());
    }

    public function testValidMastercardCard(): void
    {
        $card = CardNumber::fromString('5555555555554444');
        
        $this->assertEquals('mastercard', $card->brand());
    }

    public function testValidAmexCard(): void
    {
        $card = CardNumber::fromString('378282246310005');
        
        $this->assertEquals('amex', $card->brand());
    }

    public function testCardWithSpaces(): void
    {
        $card = CardNumber::fromString('4111 1111 1111 1111');
        
        $this->assertEquals('4111111111111111', $card->value());
    }

    public function testCardWithDashes(): void
    {
        $card = CardNumber::fromString('4111-1111-1111-1111');
        
        $this->assertEquals('4111111111111111', $card->value());
    }

    public function testMasked(): void
    {
        $card = CardNumber::fromString('4111111111111111');
        
        $this->assertEquals('************1111', $card->masked());
    }

    public function testFormattedMasked(): void
    {
        $card = CardNumber::fromString('4111111111111111');
        
        $this->assertEquals('**** **** **** 1111', $card->formattedMasked());
    }

    public function testInvalidCardNumberLength(): void
    {
        $this->expectException(InvalidCardNumberException::class);
        CardNumber::fromString('4111');
    }

    public function testInvalidLuhnChecksum(): void
    {
        $this->expectException(InvalidCardNumberException::class);
        CardNumber::fromString('4111111111111112');
    }

    public function testEmptyCardNumber(): void
    {
        $this->expectException(InvalidCardNumberException::class);
        CardNumber::fromString('');
    }

    public function testNonNumericCardNumber(): void
    {
        $this->expectException(InvalidCardNumberException::class);
        CardNumber::fromString('abcd1234efgh5678');
    }

    public function testBrandIcon(): void
    {
        $card = CardNumber::fromString('4111111111111111');
        
        $icon = $card->brandIcon();
        
        $this->assertNotEmpty($icon);
        $this->assertStringContainsString('Visa', $icon);
    }

    public function testToString(): void
    {
        $card = CardNumber::fromString('4111111111111111');
        
        $this->assertEquals('************1111', (string) $card);
    }

    public function testJsonSerialize(): void
    {
        $card = CardNumber::fromString('4111111111111111');
        
        $this->assertEquals('************1111', $card->jsonSerialize());
    }
}