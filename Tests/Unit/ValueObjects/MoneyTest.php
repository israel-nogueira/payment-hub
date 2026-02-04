<?php

namespace IsraelNogueira\PaymentHub\Tests\Unit\ValueObjects;

use IsraelNogueira\PaymentHub\ValueObjects\Money;
use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testFromCreatesMoneyObject(): void
    {
        $money = Money::from(100.50, Currency::BRL);
        
        $this->assertEquals(10050, $money->cents());
        $this->assertEquals(100.50, $money->amount());
        $this->assertEquals(Currency::BRL, $money->currency());
    }

    public function testFromCentsCreatesMoneyObject(): void
    {
        $money = Money::fromCents(10050, Currency::BRL);
        
        $this->assertEquals(10050, $money->cents());
        $this->assertEquals(100.50, $money->amount());
    }

    public function testZeroCreatesZeroMoney(): void
    {
        $money = Money::zero(Currency::USD);
        
        $this->assertEquals(0, $money->cents());
        $this->assertTrue($money->isZero());
        $this->assertEquals(Currency::USD, $money->currency());
    }

    public function testNegativeAmountThrowsException(): void
    {
        $this->expectException(InvalidAmountException::class);
        Money::from(-100, Currency::BRL);
    }

    public function testAddMoney(): void
    {
        $money1 = Money::from(100, Currency::BRL);
        $money2 = Money::from(50, Currency::BRL);
        
        $result = $money1->add($money2);
        
        $this->assertEquals(150.0, $result->amount());
    }

    public function testSubtractMoney(): void
    {
        $money1 = Money::from(100, Currency::BRL);
        $money2 = Money::from(30, Currency::BRL);
        
        $result = $money1->subtract($money2);
        
        $this->assertEquals(70.0, $result->amount());
    }

    public function testMultiply(): void
    {
        $money = Money::from(100, Currency::BRL);
        
        $result = $money->multiply(1.5);
        
        $this->assertEquals(150.0, $result->amount());
    }

    public function testDivide(): void
    {
        $money = Money::from(100, Currency::BRL);
        
        $result = $money->divide(2);
        
        $this->assertEquals(50.0, $result->amount());
    }

    public function testDivideByZeroThrowsException(): void
    {
        $money = Money::from(100, Currency::BRL);
        
        $this->expectException(\InvalidArgumentException::class);
        $money->divide(0);
    }

    public function testPercentage(): void
    {
        $money = Money::from(100, Currency::BRL);
        
        $result = $money->percentage(10);
        
        $this->assertEquals(10.0, $result->amount());
    }

    public function testSplit(): void
    {
        $money = Money::from(100, Currency::BRL);
        
        $parts = $money->split(3);
        
        $this->assertCount(3, $parts);
        $this->assertEquals(33.34, $parts[0]->amount());
        $this->assertEquals(33.33, $parts[1]->amount());
        $this->assertEquals(33.33, $parts[2]->amount());
    }

    public function testGreaterThan(): void
    {
        $money1 = Money::from(100, Currency::BRL);
        $money2 = Money::from(50, Currency::BRL);
        
        $this->assertTrue($money1->greaterThan($money2));
        $this->assertFalse($money2->greaterThan($money1));
    }

    public function testLessThan(): void
    {
        $money1 = Money::from(50, Currency::BRL);
        $money2 = Money::from(100, Currency::BRL);
        
        $this->assertTrue($money1->lessThan($money2));
        $this->assertFalse($money2->lessThan($money1));
    }

    public function testEquals(): void
    {
        $money1 = Money::from(100, Currency::BRL);
        $money2 = Money::from(100, Currency::BRL);
        $money3 = Money::from(100, Currency::USD);
        
        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }

    public function testIsPositive(): void
    {
        $money = Money::from(100, Currency::BRL);
        
        $this->assertTrue($money->isPositive());
        $this->assertFalse(Money::zero()->isPositive());
    }

    public function testFormatted(): void
    {
        $money = Money::from(1234.56, Currency::BRL);
        
        $formatted = $money->formatted();
        
        $this->assertStringContainsString('1', $formatted);
        $this->assertStringContainsString('234', $formatted);
    }

    public function testDifferentCurrenciesThrowException(): void
    {
        $money1 = Money::from(100, Currency::BRL);
        $money2 = Money::from(100, Currency::USD);
        
        $this->expectException(\InvalidArgumentException::class);
        $money1->add($money2);
    }

    public function testToArray(): void
    {
        $money = Money::from(100, Currency::BRL);
        
        $array = $money->toArray();
        
        $this->assertEquals(100.0, $array['amount']);
        $this->assertEquals('BRL', $array['currency']);
    }
}