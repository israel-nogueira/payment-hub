<?php

namespace IsraelNogueira\PaymentHub\ValueObjects;

use IsraelNogueira\PaymentHub\Exceptions\InvalidDocumentException;

final class CNPJ
{
    private string $value;

    private function __construct(string $value)
    {
        $cleaned = $this->clean($value);
        
        if (!$this->isValid($cleaned)) {
            throw new InvalidDocumentException("Invalid CNPJ: {$value}");
        }
        
        $this->value = $cleaned;
    }

    /**
     * Create from string
     */
    public static function fromString(string $cnpj): self
    {
        return new self($cnpj);
    }

    /**
     * Get raw CNPJ (only numbers)
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get formatted CNPJ (00.000.000/0000-00)
     */
    public function formatted(): string
    {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($this->value, 0, 2),
            substr($this->value, 2, 3),
            substr($this->value, 5, 3),
            substr($this->value, 8, 4),
            substr($this->value, 12, 2)
        );
    }

    /**
     * Get masked CNPJ (**.***.***/0000-00)
     */
    public function masked(): string
    {
        return sprintf(
            '**.***.**%s/%s-%s',
            substr($this->value, 7, 1),
            substr($this->value, 8, 4),
            substr($this->value, 12, 2)
        );
    }

    /**
     * Get partially masked CNPJ (**.***.***/**00-00)
     */
    public function partialMasked(): string
    {
        return sprintf(
            '**.***.**%s/**%s-%s',
            substr($this->value, 7, 1),
            substr($this->value, 10, 2),
            substr($this->value, 12, 2)
        );
    }

    /**
     * Clean CNPJ (remove dots, slashes, dashes, spaces)
     */
    private function clean(string $cnpj): string
    {
        $cleaned = preg_replace('/\D/', '', $cnpj);
        
        if (empty($cleaned)) {
            throw new InvalidDocumentException("CNPJ cannot be empty");
        }
        
        return $cleaned;
    }

    /**
     * Validate CNPJ
     */
    private function isValid(string $cnpj): bool
    {
        // Check length
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Check if all digits are the same (invalid CNPJ)
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Validate first check digit
        $sum = 0;
        $multiplier = 5;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$cnpj[$i] * $multiplier;
            $multiplier = ($multiplier === 2) ? 9 : $multiplier - 1;
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);

        if ((int)$cnpj[12] !== $digit1) {
            return false;
        }

        // Validate second check digit
        $sum = 0;
        $multiplier = 6;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int)$cnpj[$i] * $multiplier;
            $multiplier = ($multiplier === 2) ? 9 : $multiplier - 1;
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);

        return (int)$cnpj[13] === $digit2;
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
    public function jsonSerialize(): string
    {
        return $this->value();
    }

    /**
     * Check equality
     */
    public function equals(CNPJ $other): bool
    {
        return $this->value === $other->value;
    }
}