<?php

namespace IsraelNogueira\PaymentHub\Tests\Unit\ValueObjects;

use IsraelNogueira\PaymentHub\ValueObjects\CPF;
use IsraelNogueira\PaymentHub\Exceptions\InvalidDocumentException;
use PHPUnit\Framework\TestCase;

class CPFTest extends TestCase
{
    public function testValidCPF(): void
    {
        $cpf = CPF::fromString('123.456.789-09');
        
        $this->assertEquals('12345678909', $cpf->value());
    }

    public function testValidCPFWithoutMask(): void
    {
        $cpf = CPF::fromString('12345678909');
        
        $this->assertEquals('12345678909', $cpf->value());
    }

    public function testFormatted(): void
    {
        $cpf = CPF::fromString('12345678909');
        
        $this->assertEquals('123.456.789-09', $cpf->formatted());
    }

    public function testMasked(): void
    {
        $cpf = CPF::fromString('12345678909');
        
        $this->assertEquals('***.456.789-09', $cpf->masked());
    }

    public function testInvalidCPFLength(): void
    {
        $this->expectException(InvalidDocumentException::class);
        CPF::fromString('123456789');
    }

    public function testInvalidCPFAllSameDigits(): void
    {
        $this->expectException(InvalidDocumentException::class);
        CPF::fromString('11111111111');
    }

    public function testInvalidCPFCheckDigits(): void
    {
        $this->expectException(InvalidDocumentException::class);
        CPF::fromString('12345678900');
    }

    public function testEmptyCPF(): void
    {
        $this->expectException(InvalidDocumentException::class);
        CPF::fromString('');
    }

    public function testEquals(): void
    {
        $cpf1 = CPF::fromString('123.456.789-09');
        $cpf2 = CPF::fromString('12345678909');
        
        $this->assertTrue($cpf1->equals($cpf2));
    }

    public function testNotEquals(): void
    {
        $cpf1 = CPF::fromString('123.456.789-09');
        $cpf2 = CPF::fromString('987.654.321-00');
        
        $this->assertFalse($cpf1->equals($cpf2));
    }

    public function testToString(): void
    {
        $cpf = CPF::fromString('12345678909');
        
        $this->assertEquals('123.456.789-09', (string) $cpf);
    }

    public function testJsonSerialize(): void
    {
        $cpf = CPF::fromString('12345678909');
        
        $this->assertEquals('12345678909', $cpf->jsonSerialize());
    }
}