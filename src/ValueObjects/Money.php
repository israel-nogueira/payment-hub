<?php

namespace IsraelNogueira\PaymentHub\ValueObjects;

use IsraelNogueira\PaymentHub\Enums\Currency;
use IsraelNogueira\PaymentHub\Exceptions\InvalidAmountException;

final class Money
{
    private int $cents;
    private Currency $currency;

    private function __construct(int $cents, Currency $currency)
    {
        if ($cents < 0) {
            throw new InvalidAmountException("Amount cannot be negative");
        }
        
        $this->cents = $cents;
        $this->currency = $currency;
    }

    /**
     * Create from float amount
     */
    public static function from(float $amount, Currency|string $currency = Currency::BRL): self
    {
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }
        
        $cents = (int) round($amount * 100);
        
        return new self($cents, $currency);
    }

    /**
     * Create from cents
     */
    public static function fromCents(int $cents, Currency|string $currency = Currency::BRL): self
    {
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }
        
        return new self($cents, $currency);
    }

    /**
     * Create zero amount
     */
    public static function zero(Currency|string $currency = Currency::BRL): self
    {
        if (is_string($currency)) {
            $currency = Currency::fromString($currency);
        }
        
        return new self(0, $currency);
    }

    /**
     * Get amount in cents
     */
    public function cents(): int
    {
        return $this->cents;
    }

    /**
     * Get amount as float
     */
    public function amount(): float
    {
        return $this->cents / 100;
    }

    /**
     * Get currency
     */
    public function currency(): Currency
    {
        return $this->currency;
    }

    /**
     * Add another Money object
     */
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        
        return new self($this->cents + $other->cents, $this->currency);
    }

    /**
     * Subtract another Money object
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        
        return new self($this->cents - $other->cents, $this->currency);
    }

    /**
     * Multiply by a factor
     */
    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->cents * $multiplier), $this->currency);
    }

    /**
     * Divide by a divisor
     */
    public function divide(float $divisor): self
    {
        if ($divisor == 0) {
            throw new \InvalidArgumentException("Cannot divide by zero");
        }
        
        return new self((int) round($this->cents / $divisor), $this->currency);
    }

    /**
     * Calculate percentage
     */
    public function percentage(float $percentage): self
    {
        return $this->multiply($percentage / 100);
    }

    /**
     * Check if zero
     */
    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    /**
     * Check if positive
     */
    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    /**
     * Check if negative
     */
    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    /**
     * Check if greater than another Money object
     */
    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        
        return $this->cents > $other->cents;
    }

    /**
     * Check if less than another Money object
     */
    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        
        return $this->cents < $other->cents;
    }

    /**
     * Check if equal to another Money object
     */
    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents 
            && $this->currency === $other->currency;
    }

    /**
     * Split into N equal parts
     */
    public function split(int $parts): array
    {
        if ($parts <= 0) {
            throw new \InvalidArgumentException("Parts must be greater than zero");
        }

        $baseAmount = (int) floor($this->cents / $parts);
        $remainder = $this->cents % $parts;
        
        $result = [];
        for ($i = 0; $i < $parts; $i++) {
            $amount = $baseAmount + ($i < $remainder ? 1 : 0);
            $result[] = new self($amount, $this->currency);
        }
        
        return $result;
    }

    /**
     * Format as string with currency symbol
     */
    public function formatted(): string
    {
        return $this->currency->format($this->amount());
    }

    /**
     * Assert same currency
     */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency->value} and {$other->currency->value}"
            );
        }
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->formatted();
    }

    /**
     * For JSON serialization
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount(),
            'cents' => $this->cents,
            'currency' => $this->currency->value,
            'formatted' => $this->formatted(),
        ];
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount(),
            'currency' => $this->currency->value,
        ];
    }
}