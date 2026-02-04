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

        if ($amount < 0) {
            throw new InvalidAmountException("Amount cannot be negative");
        }

        if ($amount > PHP_INT_MAX / 100) {
            throw new InvalidAmountException("Amount too large: exceeds maximum value");
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
        
        $newCents = $this->cents + $other->cents;
        
        if ($newCents < 0 || $newCents > PHP_INT_MAX) {
            throw new InvalidAmountException("Amount overflow: result exceeds maximum value");
        }
        
        return new self($newCents, $this->currency);
    }

    /**
     * Subtract another Money object
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        
        $newCents = $this->cents - $other->cents;
        
        if ($newCents < 0) {
            throw new InvalidAmountException("Cannot subtract: result would be negative");
        }
        
        return new self($newCents, $this->currency);
    }

    /**
     * Multiply by a factor
     */
    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidAmountException("Multiplier cannot be negative");
        }

        $newCents = (int) round($this->cents * $multiplier);
        
        if ($newCents > PHP_INT_MAX) {
            throw new InvalidAmountException("Amount overflow: result exceeds maximum value");
        }
        
        return new self($newCents, $this->currency);
    }

    /**
     * Divide by a divisor
     */
    public function divide(float $divisor): self
    {
        if ($divisor == 0) {
            throw new \InvalidArgumentException("Cannot divide by zero");
        }

        if ($divisor < 0) {
            throw new InvalidAmountException("Divisor cannot be negative");
        }
        
        return new self((int) round($this->cents / $divisor), $this->currency);
    }

    /**
     * Calculate percentage
     */
    public function percentage(float $percentage): self
    {
        if ($percentage < 0) {
            throw new InvalidAmountException("Percentage cannot be negative");
        }

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
     * Check if greater than or equal to another Money object
     */
    public function greaterThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        
        return $this->cents >= $other->cents;
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
     * Check if less than or equal to another Money object
     */
    public function lessThanOrEqual(Money $other): bool
    {
        $this->assertSameCurrency($other);
        
        return $this->cents <= $other->cents;
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
     * Allocate amount by ratios
     * @param array<int, int> $ratios
     * @return array<int, Money>
     */
    public function allocate(array $ratios): array
    {
        if (empty($ratios)) {
            throw new \InvalidArgumentException("Ratios cannot be empty");
        }

        $totalRatio = array_sum($ratios);
        
        if ($totalRatio <= 0) {
            throw new \InvalidArgumentException("Total ratio must be greater than zero");
        }

        $remainder = $this->cents;
        $results = [];

        foreach ($ratios as $ratio) {
            $amount = (int) floor($this->cents * $ratio / $totalRatio);
            $results[] = new self($amount, $this->currency);
            $remainder -= $amount;
        }

        // Distribute remainder
        for ($i = 0; $i < $remainder; $i++) {
            $results[$i] = new self($results[$i]->cents + 1, $this->currency);
        }

        return $results;
    }

    /**
     * Format as string with currency symbol
     */
    public function formatted(): string
    {
        return $this->currency->format($this->amount());
    }

    /**
     * Get absolute value
     */
    public function abs(): self
    {
        return new self(abs($this->cents), $this->currency);
    }

    /**
     * Get negative value
     */
    public function negate(): self
    {
        return new self(-$this->cents, $this->currency);
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