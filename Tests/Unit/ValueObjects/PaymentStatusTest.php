<?php

namespace IsraelNogueira\PaymentHub\Tests\Unit\Enums;

use IsraelNogueira\PaymentHub\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function testIsPaid(): void
    {
        $this->assertTrue(PaymentStatus::PAID->isPaid());
        $this->assertTrue(PaymentStatus::APPROVED->isPaid());
        $this->assertTrue(PaymentStatus::COMPLETED->isPaid());
        $this->assertTrue(PaymentStatus::SUCCESS->isPaid());
        
        $this->assertFalse(PaymentStatus::PENDING->isPaid());
        $this->assertFalse(PaymentStatus::FAILED->isPaid());
    }

    public function testIsPending(): void
    {
        $this->assertTrue(PaymentStatus::PENDING->isPending());
        $this->assertTrue(PaymentStatus::PROCESSING->isPending());
        $this->assertTrue(PaymentStatus::WAITING->isPending());
        
        $this->assertFalse(PaymentStatus::PAID->isPending());
        $this->assertFalse(PaymentStatus::FAILED->isPending());
    }

    public function testIsFailed(): void
    {
        $this->assertTrue(PaymentStatus::FAILED->isFailed());
        $this->assertTrue(PaymentStatus::DECLINED->isFailed());
        $this->assertTrue(PaymentStatus::REJECTED->isFailed());
        $this->assertTrue(PaymentStatus::ERROR->isFailed());
        
        $this->assertFalse(PaymentStatus::PAID->isFailed());
        $this->assertFalse(PaymentStatus::PENDING->isFailed());
    }

    public function testIsCancelled(): void
    {
        $this->assertTrue(PaymentStatus::CANCELLED->isCancelled());
        $this->assertTrue(PaymentStatus::CANCELED->isCancelled());
        $this->assertTrue(PaymentStatus::VOIDED->isCancelled());
        
        $this->assertFalse(PaymentStatus::PAID->isCancelled());
        $this->assertFalse(PaymentStatus::FAILED->isCancelled());
    }

    public function testIsRefunded(): void
    {
        $this->assertTrue(PaymentStatus::REFUNDED->isRefunded());
        
        $this->assertFalse(PaymentStatus::PAID->isRefunded());
        $this->assertFalse(PaymentStatus::CANCELLED->isRefunded());
    }

    public function testLabel(): void
    {
        $this->assertEquals('Aprovado', PaymentStatus::PAID->label());
        $this->assertEquals('Pendente', PaymentStatus::PENDING->label());
        $this->assertEquals('Recusado', PaymentStatus::FAILED->label());
        $this->assertEquals('Cancelado', PaymentStatus::CANCELLED->label());
        $this->assertEquals('Reembolsado', PaymentStatus::REFUNDED->label());
    }

    public function testColor(): void
    {
        $this->assertEquals('green', PaymentStatus::PAID->color());
        $this->assertEquals('yellow', PaymentStatus::PENDING->color());
        $this->assertEquals('red', PaymentStatus::FAILED->color());
        $this->assertEquals('gray', PaymentStatus::CANCELLED->color());
        $this->assertEquals('blue', PaymentStatus::REFUNDED->color());
    }

    public function testFromString(): void
    {
        $this->assertEquals(PaymentStatus::PAID, PaymentStatus::fromString('paid'));
        $this->assertEquals(PaymentStatus::PAID, PaymentStatus::fromString('PAID'));
        $this->assertEquals(PaymentStatus::PENDING, PaymentStatus::fromString('pending'));
    }

    public function testFromStringUnknownDefaultsToPending(): void
    {
        $status = PaymentStatus::fromString('unknown_status');
        
        $this->assertEquals(PaymentStatus::PENDING, $status);
    }

    public function testValue(): void
    {
        $this->assertEquals('paid', PaymentStatus::PAID->value);
        $this->assertEquals('pending', PaymentStatus::PENDING->value);
        $this->assertEquals('failed', PaymentStatus::FAILED->value);
    }
}